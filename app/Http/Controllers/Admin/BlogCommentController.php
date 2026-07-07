<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BlogCommentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:blog-comments.view', only: ['index']),
            new Middleware('can:blog-comments.moderate', only: ['approve']),
            new Middleware('can:blog-comments.reply', only: ['reply']),
            new Middleware('can:blog-comments.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString(); // '', 'pending', 'approved'

        $comments = BlogComment::query()
            ->topLevel()
            ->with(['post:id,title,slug', 'replies' => fn ($q) => $q->oldest()])
            ->when($status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($status === 'approved', fn ($q) => $q->where('is_approved', true))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.blog.comments.index', [
            'comments' => $comments,
            'filters' => ['status' => $status],
            'stats' => [
                'total' => BlogComment::topLevel()->count(),
                'pending' => BlogComment::topLevel()->where('is_approved', false)->count(),
                'approved' => BlogComment::topLevel()->where('is_approved', true)->count(),
            ],
        ]);
    }

    /** Toggle a comment's approved state. */
    public function approve(BlogComment $comment): RedirectResponse
    {
        $comment->update(['is_approved' => ! $comment->is_approved]);

        return back()->with('status', $comment->is_approved ? 'Comment approved.' : 'Comment hidden (unapproved).');
    }

    /** Post a staff reply to a comment (auto-approved, shown on the post). */
    public function reply(Request $request, BlogComment $comment): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        // Replies attach to the top-level comment even if someone replies from a reply row.
        $parent = $comment->parent_id ? $comment->parent : $comment;

        $parent->replies()->create([
            'post_id' => $parent->post_id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'body' => $data['body'],
            'is_admin' => true,
            'is_approved' => true,
        ]);

        return back()->with('status', 'Reply posted.');
    }

    public function destroy(BlogComment $comment): RedirectResponse
    {
        $comment->delete(); // cascades to replies

        return back()->with('status', 'Comment deleted.');
    }
}
