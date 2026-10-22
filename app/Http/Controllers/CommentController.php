<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    public function store(Request $request, string $slug)
    {
        // SECURITY: you can only comment on a live article (drafts and scheduled posts show 404)
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        $back = route('blog.show', $post->slug) . '#comments';

        // SPAM TRAP: the field "website" is hidden from people. A bot fills it in.
        // We pretend it worked, so the bot learns nothing.
        if (filled($request->input('website'))) {
            return $this->thanks($back, true);
        }

        $user = $request->user();

        // Only plain text is accepted (a trick like name[]=x is ignored)
        $text = fn (string $key): string => is_string($request->input($key)) ? $request->input($key) : '';

        // A logged-in customer's name and email always come from their account
        $input = [
            'name'  => $user ? $user->name : Str::squish($text('name')),
            'email' => $user ? $user->email : Str::lower(trim($text('email'))),
            'body'  => trim(str_replace("\r\n", "\n", $text('body'))),
        ];

        $check = Validator::make($input, [
            'name'  => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'body'  => ['required', 'string', 'min:3', 'max:1500', function (string $attribute, mixed $value, \Closure $fail) {
                // Spam usually carries links
                if (preg_match_all('~https?://|www\.~i', $value) > 1) {
                    $fail('Please include at most one link in a comment.');
                }
            }],
        ]);

        if ($check->fails()) {
            return redirect($back)->withErrors($check)->withInput($input);
        }

        // The same comment twice on the same article: ignore it quietly
        $duplicate = Comment::where('blog_post_id', $post->id)
            ->where('email', $input['email'])
            ->where('body', $input['body'])
            ->exists();
        if ($duplicate) {
            return $this->thanks($back, true);
        }

        // Too many comments from one person still waiting for approval
        if (Comment::where('email', $input['email'])->where('status', 'pending')->count() >= 5) {
            return redirect($back)->withInput($input)
                ->with('error', 'You already have several comments waiting for approval. Please wait until they are reviewed.')
                ->with('alert_title', 'Please wait');
        }

        // status, article and author are set here, never taken from the form
        $comment = new Comment($input);
        $comment->blog_post_id = $post->id;
        $comment->user_id      = $user?->id;
        $comment->status       = ($user && $user->isStaff()) ? 'approved' : 'pending';
        $comment->save();

        return $this->thanks($back, $comment->status === 'pending');
    }

    private function thanks(string $back, bool $pending)
    {
        return redirect($back)
            ->with('success', $pending
                ? 'Thank you! Your comment will appear after it has been approved.'
                : 'Your comment was published.')
            ->with('alert_title', $pending ? 'Comment received' : 'Comment published');
    }
}