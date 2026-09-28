<?php

namespace App\Services;

use App\Jobs\ParseResumeProfile;
use App\Models\AutomationProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ResumeService
{
    public function __construct(
        private PdfTextExtractor $extractor,
        private ResumeProfileValidator $validator,
    ) {}

    public function store(User $user, UploadedFile $resume): AutomationProfile
    {
        try {
            $text = $this->extractor->extract($resume->getRealPath());
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['resume' => $exception->getMessage()]);
        }

        $profile = AutomationProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $profile->update([
            'is_active' => false,
            'resume_text' => $text,
            'resume_data' => null,
            'resume_parse_status' => 'pending',
            'resume_parse_error' => null,
            'resume_updated_at' => now(),
            'resume_parsed_at' => null,
        ]);

        ParseResumeProfile::dispatch($profile->id);

        return $profile->refresh();
    }

    public function getProfile(User $user): AutomationProfile
    {
        return AutomationProfile::query()->firstOrCreate(['user_id' => $user->id]);
    }

    /** @param array<string, mixed> $resumeData */
    public function updateProfile(User $user, array $resumeData): AutomationProfile
    {
        $profile = $this->getProfile($user);

        if (! filled($profile->resume_text)) {
            throw ValidationException::withMessages([
                'resume' => 'Envie um currículo em PDF antes de editar os dados extraídos.',
            ]);
        }

        $profile->update([
            'resume_data' => $this->validator->validate($resumeData),
            'resume_parse_status' => 'ready',
            'resume_parse_error' => null,
            'resume_parsed_at' => now(),
        ]);

        return $profile->refresh();
    }

    public function retryParsing(User $user): AutomationProfile
    {
        $profile = $this->getProfile($user);

        if (! filled($profile->resume_text)) {
            throw ValidationException::withMessages([
                'resume' => 'Envie um currículo antes de solicitar uma nova leitura.',
            ]);
        }

        $profile->update([
            'is_active' => false,
            'resume_parse_status' => 'pending',
            'resume_parse_error' => null,
        ]);
        ParseResumeProfile::dispatch($profile->id);

        return $profile->refresh();
    }

    public function remove(User $user): void
    {
        AutomationProfile::query()
            ->where('user_id', $user->id)
            ->update([
                'is_active' => false,
                'resume_text' => null,
                'resume_data' => null,
                'resume_parse_status' => 'missing',
                'resume_parse_error' => null,
                'resume_updated_at' => null,
                'resume_parsed_at' => null,
            ]);
    }
}
