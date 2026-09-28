<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resume' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'resume.required' => 'Selecione um currículo em PDF.',
            'resume.mimes' => 'O currículo deve ser um arquivo PDF.',
            'resume.mimetypes' => 'O arquivo enviado não foi reconhecido como PDF.',
            'resume.max' => 'O currículo deve ter no máximo 5 MB.',
        ];
    }
}
