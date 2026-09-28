<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexJobRequest extends FormRequest
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
            'level' => ['sometimes', Rule::in(['estagio', 'junior', 'pleno', 'senior'])],
            'status' => ['sometimes', Rule::in(['new', 'saved', 'applied', 'interview', 'rejected', 'ignored'])],
            'min_score' => ['sometimes', 'integer', 'between:0,100'],
            'search' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ];
    }
}
