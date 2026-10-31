<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    // POST /products/{slug}/reviews (logged-in customers only: the route is inside the login group)
    public function store(Request $request, string $slug)
    {
        // SECURITY: only a product that is visible in the shop can be reviewed
        $product = Product::visible()->where('slug', $slug)->firstOrFail();
        $back    = route('products.show', $product->slug) . '#reviews';

        // SPAM TRAP: people never see the field "website". A bot fills it in.
        // We pretend it worked, so the bot learns nothing.
        if (filled($request->input('website'))) {
            return $this->thanks($back);
        }

        $user = $request->user();

        // SECURITY: only a customer who has RECEIVED the product may review it
        abort_unless(ProductReview::canBeWrittenBy($user, $product->id), 403);

        // One review per product per customer
        if (ProductReview::where('product_id', $product->id)->where('user_id', $user->id)->exists()) {
            return redirect($back)
                ->with('error', 'You have already reviewed this product.')
                ->with('alert_title', 'Already reviewed');
        }

        // Only plain text is accepted (a trick like rating[]=x is ignored)
        $text = function (string $key) use ($request): string {
            $value = $request->input($key);

            // Text and plain numbers are accepted. Anything else (such as rating[]=x) counts as empty.
            return (is_string($value) || is_int($value) || is_float($value)) ? (string) $value : '';
        };

        $input = [
            'rating' => trim($text('rating')),
            'title'  => Str::squish($text('title')),
            'body'   => trim(str_replace("\r\n", "\n", $text('body'))),
        ];

        $check = Validator::make($input, [
            'rating' => ['required', 'integer', 'between:1,5'],
            'title'  => ['nullable', 'string', 'max:100'],
            'body'   => ['required', 'string', 'min:10', 'max:1500', function (string $attribute, mixed $value, \Closure $fail) {
                // Spam usually carries links
                if (preg_match_all('~https?://|www\.~i', $value) > 1) {
                    $fail('Please include at most one link in a review.');
                }
            }],
        ], [
            'rating.required' => 'Please choose a star rating.',
            'rating.between'  => 'Please choose a rating from 1 to 5 stars.',
            'rating.integer'  => 'Please choose a rating from 1 to 5 stars.',
            'body.min'        => 'Please write a little more (at least 10 characters).',
        ]);

        if ($check->fails()) {
            return redirect($back)->withErrors($check)->withInput($input);
        }

        // The status, product and author are set here, never taken from the form
        $review = new ProductReview([
            'rating' => (int) $input['rating'],
            'title'  => $input['title'] !== '' ? $input['title'] : null,
            'body'   => $input['body'],
        ]);
        $review->product_id = $product->id;
        $review->user_id    = $user->id;
        $review->status     = 'pending';

        try {
            $review->save();

            // Tell the owner a review is waiting. A mail problem never stops the review.
            \App\Services\AdminAlert::send(
                'New review waiting',
                'A new product review is waiting for approval',
                [
                    'Product: ' . $product->name,
                    'Rating: ' . $review->rating . ' out of 5',
                    'Review: ' . \Illuminate\Support\Str::limit($review->body, 300),
                ],
                route('admin.reviews.index', ['status' => 'pending']),
                'Review the review'
            );
        } catch (UniqueConstraintViolationException $e) {
            // Two clicks at the same moment: the first one already saved it
        }

        return $this->thanks($back);
    }

    private function thanks(string $back)
    {
        return redirect($back)
            ->with('success', 'Thank you! Your review will appear after it has been approved.')
            ->with('alert_title', 'Review received');
    }
}