<?php

namespace App\Services;

use App\Models\AutomationProfile;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AutomationProfileService
{
    public function getOrCreate(User $user): AutomationProfile
    {
        return AutomationProfile::query()->firstOrCreate(['user_id' => $user->id]);
    }

    /** @param array<string, mixed> $input */
    public function update(User $user, array $input): AutomationProfile
    {
        foreach ([
            'job_titles',
            'areas',
            'seniorities',
            'technologies',
            'excluded_keywords',
            'work_modes',
            'platforms',
            'employment_types',
            'locations',
        ] as $field) {
            $input[$field] = $this->normalizeList($input[$field] ?? []);
        }

        if ($input['job_titles'] === [] && $input['technologies'] === []) {
            throw ValidationException::withMessages([
                'job_titles' => 'Informe pelo menos um cargo ou uma tecnologia de interesse.',
            ]);
        }

        $profile = $this->getOrCreate($user);
        $profile->fill($input);

        if ($profile->is_active && ! $profile->isReady()) {
            throw ValidationException::withMessages([
                'is_active' => 'Envie e revise um currículo processado antes de ativar a automação.',
            ]);
        }

        $profile->save();

        return $profile;
    }

    /**
     * @param  array<mixed>  $values
     * @return array<int, string>
     */
    private function normalizeList(array $values): array
    {
        return collect($values)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->unique(fn (string $value): string => mb_strtolower($value))
            ->values()
            ->all();
    }
}
