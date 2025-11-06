<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseCategory;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseArticleComment;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    /**
     * Show knowledge base home page
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        // Get root categories with article counts
        $categories = KnowledgeBaseCategory::active()
            ->root()
            ->with(['children' => function($q) {
                $q->active();
            }])
            ->get();

        // Get featured articles
        $featuredArticles = KnowledgeBaseArticle::published()
            ->featured()
            ->with('category')
            ->orderBy('order')
            ->limit(6)
            ->get();

        // Search results if searching
        $searchResults = null;
        if ($search) {
            $searchResults = KnowledgeBaseArticle::published()
                ->search($search)
                ->with('category')
                ->orderByRaw("CASE WHEN title LIKE ? THEN 1 ELSE 2 END", ["%{$search}%"])
                ->orderBy('view_count', 'desc')
                ->paginate(15);
        }

        // Popular articles
        $popularArticles = KnowledgeBaseArticle::published()
            ->with('category')
            ->orderBy('view_count', 'desc')
            ->limit(5)
            ->get();

        return view('client.knowledge-base.index', compact(
            'categories',
            'featuredArticles',
            'searchResults',
            'popularArticles',
            'search'
        ));
    }

    /**
     * Show category with articles
     */
    public function category($slug)
    {
        $category = KnowledgeBaseCategory::active()
            ->where('slug', $slug)
            ->with(['children' => function($q) {
                $q->active();
            }])
            ->firstOrFail();

        // Get articles in this category and subcategories
        $categoryIds = [$category->id];
        if ($category->children->count() > 0) {
            $categoryIds = array_merge($categoryIds, $category->children->pluck('id')->toArray());
        }

        $articles = KnowledgeBaseArticle::published()
            ->whereIn('category_id', $categoryIds)
            ->orderBy('order')
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        return view('client.knowledge-base.category', compact('category', 'articles'));
    }

    /**
     * Show single article
     */
    public function article($categorySlug, $articleSlug)
    {
        $article = KnowledgeBaseArticle::published()
            ->where('slug', $articleSlug)
            ->with(['category', 'author', 'attachments'])
            ->firstOrFail();

        // Increment view count
        $article->incrementViewCount();

        // Get related articles from same category
        $relatedArticles = KnowledgeBaseArticle::published()
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->orderBy('view_count', 'desc')
            ->limit(5)
            ->get();

        // Get approved comments
        $comments = $article->comments()
            ->approved()
            ->with('user')
            ->latest()
            ->get();

        // Check if user has voted
        $userVote = null;
        if (auth()->check()) {
            $userVote = $article->votes()
                ->where('user_id', auth()->id())
                ->first();
        } else {
            $userVote = $article->votes()
                ->where('ip_address', request()->ip())
                ->first();
        }

        return view('client.knowledge-base.article', compact(
            'article',
            'relatedArticles',
            'comments',
            'userVote'
        ));
    }

    /**
     * Vote on article (helpful/not helpful)
     */
    public function vote(Request $request, $articleId)
    {
        $request->validate([
            'is_helpful' => 'required|boolean',
        ]);

        $article = KnowledgeBaseArticle::published()->findOrFail($articleId);

        $user = auth()->user();
        $ipAddress = request()->ip();

        if ($request->is_helpful) {
            $article->markAsHelpful($user, $ipAddress);
        } else {
            $article->markAsNotHelpful($user, $ipAddress);
        }

        return response()->json([
            'success' => true,
            'helpful_count' => $article->helpful_count,
            'not_helpful_count' => $article->not_helpful_count,
            'helpfulness_percentage' => $article->helpfulness_percentage,
        ]);
    }

    /**
     * Post comment on article
     */
    public function comment(Request $request, $articleId)
    {
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Please login to post a comment');
        }

        $article = KnowledgeBaseArticle::published()->findOrFail($articleId);

        if (!$article->allow_comments) {
            return back()->with('error', 'Comments are disabled for this article');
        }

        $request->validate([
            'comment' => 'required|string|min:10|max:1000',
        ]);

        KnowledgeBaseArticleComment::create([
            'article_id' => $article->id,
            'user_id' => auth()->id(),
            'comment' => $request->comment,
            'is_staff_reply' => false,
            'status' => 'pending', // Requires moderation
        ]);

        return back()->with('success', 'Your comment has been submitted and is pending moderation');
    }

    /**
     * Download article attachment
     */
    public function downloadAttachment($articleId, $attachmentId)
    {
        $article = KnowledgeBaseArticle::published()->findOrFail($articleId);

        $attachment = $article->attachments()->findOrFail($attachmentId);

        return Storage::download($attachment->storage_path, $attachment->original_filename);
    }
}
