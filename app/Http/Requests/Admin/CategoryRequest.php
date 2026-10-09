<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    // Only staff and admins may use this form (the route is protected as well)
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    // SECURITY: every value is checked on the server
    public function rules(): array
    {
        // When editing, the category's own slug is allowed to stay the same
        $category = $this->route('category');

        return [
            'name'         => ['required', 'string', 'max:100'],
            'slug'         => [
                'nullable',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($category)
            ],
            'description'  => ['nullable', 'string', 'max:500'],
            'sort_order'   => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'    => ['nullable', 'boolean'],
            'image'        => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'Use only lowercase letters, numbers and dashes, for example: wall-clocks.',
            'image.mimes' => 'The photo must be a JPG, PNG or WebP file.',
            'image.max'   => 'The photo must be smaller than 2 MB.',
        ];
    }
}
