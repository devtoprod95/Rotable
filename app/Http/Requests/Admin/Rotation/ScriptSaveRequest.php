<?php

namespace App\Http\Requests\Admin\Rotation;

use Illuminate\Validation\Rule;

/**
 * 호스트 발표 멘트 저장 — 유형별 섹션 구조.
 */
class ScriptSaveRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type'               => ['required', Rule::in(['wine', 'solo'])],
            'title'              => 'nullable|string|max:255',
            'sections'           => 'present|array',
            'sections.*.no'      => 'nullable|string|max:20',
            'sections.*.heading' => 'nullable|string|max:255',
            'sections.*.lines'   => 'present|array',
            'sections.*.lines.*' => 'nullable|string|max:2000',
        ];
    }

    /**
     * 정리된 섹션: 빈 줄은 빼고, 제목도 줄도 없는 섹션은 버린다.
     *
     * @return list<array{no:string, heading:string, lines:list<string>}>
     */
    public function cleanedSections(): array
    {
        return collect($this->validated('sections', []))
            ->map(fn (array $section): array => [
                'no'      => trim((string) ($section['no'] ?? '')),
                'heading' => trim((string) ($section['heading'] ?? '')),
                'lines'   => collect($section['lines'] ?? [])
                    ->map(fn ($line): string => trim((string) $line))
                    ->filter(fn (string $line): bool => $line !== '')
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $section): bool => $section['heading'] !== '' || $section['lines'] !== [])
            ->values()
            ->all();
    }
}
