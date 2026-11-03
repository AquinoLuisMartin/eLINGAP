<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Choose a profile photo.',
            'photo.image' => 'The profile photo must be a JPEG, PNG, or WebP image.',
            'photo.mimes' => 'The profile photo must be a JPEG, PNG, or WebP image.',
            'photo.max' => 'The profile photo must be 2MB or smaller.',
            'photo.dimensions' => 'The profile photo must be 4096 pixels or smaller on each side.',
        ];
    }
}
