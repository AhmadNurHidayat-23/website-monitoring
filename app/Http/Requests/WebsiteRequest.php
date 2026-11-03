<?php

namespace App\Http\Requests;

use App\Services\Monitoring\PublicTarget;
use App\Services\Monitoring\UnsafeTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebsiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['url' => trim((string) $this->input('url')), 'name' => trim((string) $this->input('name'))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'url' => ['bail', 'required', 'string', 'max:2048', Rule::unique('websites')->ignore($this->route('website')), function ($attribute, $value, $fail) {
                try {
                    app(PublicTarget::class)->validate($value);
                } catch (UnsafeTarget $exception) {
                    $fail($exception->getMessage());
                }
            }],
            'interval_minutes' => ['required', 'integer', Rule::in([1, 5, 10, 15, 30, 60])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama website wajib diisi.', 'name.max' => 'Nama maksimal 100 karakter.',
            'url.required' => 'URL website wajib diisi.', 'url.unique' => 'URL ini sudah dipantau.',
            'url.max' => 'URL maksimal 2.048 karakter.',
            'interval_minutes.*' => 'Pilih interval pemeriksaan yang tersedia.',
        ];
    }
}
