<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Support\Facades\URL;

class PostPreviewLinkController extends Controller
{
    // Staff only (the route is inside the admin group).
    // Sends you to a signed link that works for 24 hours, even for a draft.
    public function __invoke(BlogPost $post)
    {
        return redirect()->to(
            URL::temporarySignedRoute('blog.preview', now()->addHours(24), ['post' => $post->id])
        );
    }
}