<?php

namespace App\Services;

use App\Models\Job;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

class JobService
{
    public function __construct(
        private ResumePdf $resumes,
        private PushSubscriptionService $subscriptions,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Job>
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $query = Job::query()
            ->where('user_id', $user->id)
            ->where('analysis_state', 'accepted');

        $query->when(isset($filters['level']), fn (Builder $query): Builder => $query->where('level', $filters['level']));
        $query->when(
            isset($filters['status']),
            fn (Builder $query): Builder => $query->where('status', $filters['status']),
            fn (Builder $query): Builder => $query->where('status', '!=', 'ignored'),
        );
        $query->when(isset($filters['min_score']), fn (Builder $query): Builder => $query->where('score', '>=', $filters['min_score']));
        $query->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
            $term = '%'.addcslashes($filters['search'], '%_\\').'%';
            $query->where(fn (Builder $query): Builder => $query
                ->where('title', 'like', $term)
                ->orWhere('company', 'like', $term));
        });

        return $query->orderByDesc('created_at')->paginate($filters['per_page'] ?? 20);
    }

    public function accessibleTo(User $user, Job $job): Job
    {
        abort_unless($job->user_id === $user->id && $job->analysis_state === 'accepted', 404);

        return $job;
    }

    public function view(User $user, Job $job): Job
    {
        $job = $this->accessibleTo($user, $job);

        if (! $job->viewed_at) {
            $job->update(['viewed_at' => now()]);
        }

        return $job->refresh();
    }

    public function unreadCount(User $user): int
    {
        return $this->subscriptions->unreadCount($user);
    }

    public function updateStatus(User $user, Job $job, string $status): Job
    {
        $job = $this->accessibleTo($user, $job);
        $job->update(['status' => $status]);

        return $job->refresh();
    }

    public function downloadResume(User $user, Job $job): Response
    {
        $job = $this->accessibleTo($user, $job);
        $legacyResume = $job->payload['analise_ia']['curriculo_personalizado'] ?? null;
        abort_unless(filled($job->resume_latex) || is_array($legacyResume), 404);

        return response($this->resumes->generate($job), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="curriculo-personalizado.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
