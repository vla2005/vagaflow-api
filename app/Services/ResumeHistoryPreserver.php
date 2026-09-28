<?php

namespace App\Services;

use Illuminate\Support\Str;

class ResumeHistoryPreserver
{
    /**
     * @param  array<string, mixed>  $personalized
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    public function preserve(array $personalized, array $base): array
    {
        $personalized['identity'] = array_replace(
            is_array($personalized['identity'] ?? null) ? $personalized['identity'] : [],
            is_array($base['identity'] ?? null) ? $base['identity'] : [],
        );

        $personalized['experiences'] = $this->preserveRecords(
            $personalized['experiences'] ?? [],
            $base['experiences'] ?? [],
            ['organization', 'role', 'period'],
            'bullets',
        );
        $personalized['projects'] = $this->preserveRecords(
            $personalized['projects'] ?? [],
            $base['projects'] ?? [],
            ['name', 'period'],
            'bullets',
        );
        $personalized['education'] = $this->preserveRecords(
            $personalized['education'] ?? [],
            $base['education'] ?? [],
            ['institution', 'course', 'period'],
            'details',
        );
        $personalized['optional_sections'] = $this->preserveOptionalSections(
            $personalized['optional_sections'] ?? [],
            $base['optional_sections'] ?? [],
        );

        return $personalized;
    }

    /**
     * @param  list<string>  $identityFields
     * @return list<array<string, mixed>>
     */
    private function preserveRecords(mixed $personalized, mixed $base, array $identityFields, string $detailsField): array
    {
        $personalized = is_array($personalized) ? array_values($personalized) : [];
        $base = is_array($base) ? array_values($base) : [];
        $baseKeys = collect($base)
            ->filter(fn (mixed $record): bool => is_array($record))
            ->map(fn (array $record): string => $this->recordKey($record, $identityFields));
        $personalized = array_values(array_filter(
            $personalized,
            fn (mixed $record): bool => is_array($record)
                && $baseKeys->contains($this->recordKey($record, $identityFields)),
        ));

        foreach ($base as $baseRecord) {
            if (! is_array($baseRecord)) {
                continue;
            }

            $index = collect($personalized)->search(
                fn (mixed $record): bool => is_array($record)
                    && $this->recordKey($record, $identityFields) === $this->recordKey($baseRecord, $identityFields),
            );

            if ($index === false) {
                $personalized[] = $baseRecord;

                continue;
            }

            foreach ($identityFields as $field) {
                $personalized[$index][$field] = $baseRecord[$field] ?? null;
            }

            if (($personalized[$index][$detailsField] ?? []) === []) {
                $personalized[$index][$detailsField] = $baseRecord[$detailsField] ?? [];
            }
        }

        return $personalized;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function preserveOptionalSections(mixed $personalized, mixed $base): array
    {
        $personalized = is_array($personalized) ? array_values($personalized) : [];
        $base = is_array($base) ? array_values($base) : [];
        $baseKeys = collect($base)
            ->filter(fn (mixed $section): bool => is_array($section))
            ->map(fn (array $section): string => $this->recordKey($section, ['type', 'title']));
        $personalized = array_values(array_filter(
            $personalized,
            fn (mixed $section): bool => is_array($section)
                && $baseKeys->contains($this->recordKey($section, ['type', 'title'])),
        ));

        foreach ($base as $baseSection) {
            if (! is_array($baseSection)) {
                continue;
            }

            $index = collect($personalized)->search(fn (mixed $section): bool => is_array($section)
                && $this->recordKey($section, ['type', 'title']) === $this->recordKey($baseSection, ['type', 'title']));

            if ($index === false) {
                $personalized[] = $baseSection;

                continue;
            }

            $personalized[$index]['items'] = $this->preserveRecords(
                $personalized[$index]['items'] ?? [],
                $baseSection['items'] ?? [],
                ['heading', 'subheading', 'period'],
                'details',
            );
        }

        return $personalized;
    }

    /** @param list<string> $fields */
    private function recordKey(array $record, array $fields): string
    {
        return collect($fields)
            ->map(fn (string $field): string => Str::lower(Str::ascii(trim((string) ($record[$field] ?? '')))))
            ->implode('|');
    }
}
