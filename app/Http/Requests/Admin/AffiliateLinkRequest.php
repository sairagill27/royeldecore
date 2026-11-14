<?php

namespace App\Http\Requests\Admin;

use App\Models\AffiliateLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AffiliateLinkRequest extends FormRequest
{
    // Only staff and admins may use this form (the route is protected as well)
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    // Plain text only, with the spaces around it tidied (a trick like name[]=x counts as empty)
    protected function prepareForValidation(): void
    {
        $tidy = [];
        foreach (['name', 'merchant', 'slug', 'url', 'description', 'section'] as $key) {
            $value      = $this->input($key);
            $tidy[$key] = is_string($value) ? trim($value) : null;
        }
        $this->merge($tidy);
    }

    // SECURITY: every value is checked on the server
    public function rules(): array
    {
        $link = $this->route('link');

        return [
            'name'        => ['required', 'string', 'max:120'],
            'merchant'    => ['required', 'string', 'max:60'],
            'slug'        => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                              Rule::unique('affiliate_links', 'slug')->ignore($link)],
            'url'         => ['required', 'string', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) {
                if (! AffiliateLink::safeUrl($value)) {
                    $fail('Please enter the full https:// address of another store, exactly as your affiliate program gives it.');
                }
            }],
            'description' => ['nullable', 'string', 'max:200'],

            // A section from the list. A link that already has an older value may keep it.
            'section'     => ['nullable', 'string', 'max:60', function (string $attribute, mixed $value, \Closure $fail) use ($link) {
                if ($value !== null && $value !== '' && ! in_array($value, (array) config('affiliate.sections', []), true) && $value !== $link?->section) {
                    $fail('Please choose a section from the list.');
                }
            }],

            'status'           => ['required', Rule::in(array_keys(AffiliateLink::STATUSES))],
            'expires_at'       => ['nullable', 'date_format:Y-m-d'],
            'is_featured'      => ['nullable', 'boolean'],
            'is_direct'        => ['nullable', 'boolean'],
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],

            // Photo: the real file type, the file extension and the size are all checked
            'image'        => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp', 'mimes:jpg,jpeg,png,webp',
                               'mimetypes:image/jpeg,image/png,image/webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'             => 'Use only lowercase letters, numbers and dashes, for example: cordless-drill.',
            'slug.unique'            => 'Another link already uses this short name.',
            'expires_at.date_format' => 'Please choose a valid date.',
            'image.extensions'       => 'The photo must be a JPG, PNG or WebP file.',
            'image.mimes'            => 'The photo must be a JPG, PNG or WebP file.',
            'image.mimetypes'        => 'The photo must be a JPG, PNG or WebP file.',
            'image.max'              => 'The photo must be smaller than 2 MB.',
            'image.dimensions'       => 'The photo is too large. Please use one up to 6000 pixels wide or tall.',
        ];
    }
}