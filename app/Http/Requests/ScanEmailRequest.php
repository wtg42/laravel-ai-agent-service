<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ScanEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'content' => 'Email 內容',
        ];
    }

    protected function prepareForValidation(): void
    {
        $content = is_string($this->input('content'))
            ? trim(preg_replace('/\s+/u', ' ', $this->input('content')) ?? '')
            : $this->input('content');

        $this->merge([
            'content' => $content,
        ]);
    }
}
