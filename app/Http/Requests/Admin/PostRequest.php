<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    // Only staff and admins may use this form (the route is protected as well)
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    // SECURITY: every value is checked on the server
    public function rules(): array
    {
        // When editing, the post's own slug is allowed to stay the same
        $post = $this->route('post');

        return [
            'title'            => ['required', 'string', 'max:200'],
            'slug'             => [
                'nullable',
                'string',
                'max:220',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blog_posts', 'slug')->ignore($post)
            ],
            'excerpt'          => ['nullable', 'string', 'max:400'],
            'content'          => ['required', 'string', 'max:60000'],
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],

            // Tags are typed with commas: at most 10, each up to 40 characters
            'tags'             => ['nullable', 'string', 'max:600', function (string $attribute, mixed $value, \Closure $fail) {
                $names = array_filter(array_map('trim', explode(',', (string) $value)));

                if (count($names) > 10) {
                    $fail('Please use no more than 10 tags.');
                }
                foreach ($names as $name) {
                    if (mb_strlen($name) > 40) {
                        $fail('Each tag can be up to 40 characters long.');
                        break;
                    }
                }
            }],

            'status'           => ['required', Rule::in(['draft', 'published'])],
            // The format a browser date and time box sends, e.g. 2026-10-20T14:30
            'published_at'     => ['nullable', 'date_format:Y-m-d\TH:i'],

            // Photo: only these real image types, up to 2 MB (checked from the file content)
            'featured_image'     => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'featured_image_alt' => ['nullable', 'string', 'max:160', 'required_with:featured_image'],
            'remove_image'       => ['nullable', 'boolean'],

            // Search engine fields
            'seo_title'        => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'focus_keyword'    => ['nullable', 'string', 'max:80'],
            'noindex'          => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'                        => 'Use only lowercase letters, numbers and dashes, for example: small-bedroom-ideas.',
            'featured_image.mimes'              => 'The photo must be a JPG, PNG or WebP file.',
            'featured_image.max'                => 'The photo must be smaller than 2 MB.',
            'featured_image_alt.required_with'  => 'Please describe the photo in a few words. Screen readers and search engines use it.',
            'published_at.date_format'          => 'Please choose a valid date and time.',
            'focus_keyword.max'                 => 'The focus keyword can be up to 80 characters.',
        ];
    }
}
