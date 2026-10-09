<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    // Only staff and admins may use this form (the route is protected as well)
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    // SECURITY: every value is checked on the server
    public function rules(): array
    {
        // When editing, the product's own slug and SKU are allowed to stay the same
        $product = $this->route('product');

        return [
            'name'             => ['required', 'string', 'max:150'],
            // lowercase words joined with dashes, e.g. acrylic-islamic-wall-art
            'slug'             => [
                'nullable',
                'string',
                'max:160',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($product)
            ],
            'sku'              => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product)],
            'category_id'      => ['required', 'integer', 'exists:categories,id'],
            'description'      => ['required', 'string', 'max:5000'],
            'price'            => ['required', 'numeric', 'min:0', 'max:99999999'],
            'stock'            => ['required', 'integer', 'min:0', 'max:100000'],
            'status'           => ['required', Rule::in(['active', 'draft'])],
            'is_featured'      => ['nullable', 'boolean'],
            'seo_title'        => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            // Photo: only these real image types, up to 2 MB (the type is checked from the file content)
            'image'            => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image'     => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'   => 'Use only lowercase letters, numbers and dashes, for example: wall-clock-gold.',
            'image.mimes'  => 'The photo must be a JPG, PNG or WebP file.',
            'image.max'    => 'The photo must be smaller than 2 MB.',
        ];
    }
}
