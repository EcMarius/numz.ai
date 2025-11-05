<?php

namespace App\Services\WarmupStrategies;

use App\Models\AccountWarmup;
use Wave\Plugins\EvenLeads\Services\AIReplyService;
use Wave\Plugins\EvenLeads\Services\RedditService;
use Illuminate\Support\Facades\Log;

class RedditWarmupStrategy extends BaseWarmupStrategy
{
    protected $redditService;
    protected $aiService;

    public function __construct()
    {
        $this->redditService = new RedditService();
    }

    public function performActivity(AccountWarmup $warmup): void
    {
        try {
            $phase = $warmup->current_phase ?? $this->getPhaseForDay($warmup->current_day);
            $limits = $this->getActivityLimits($phase);

            // Determine how many comments to post today
            $commentsToPost = rand($limits['comments_min'], $limits['comments_max']);
            $postsToCreate = rand($limits['posts_min'], $limits['posts_max']);

            // Perform comments
            for ($i = 0; $i < $commentsToPost; $i++) {
                $this->postComment($warmup, $phase);
                // Add small delay between actions
                sleep(rand(60, 180)); // 1-3 minutes
            }

            // Perform posts
            for ($i = 0; $i < $postsToCreate; $i++) {
                $this->createPost($warmup, $phase);
                sleep(rand(120, 300)); // 2-5 minutes
            }

            // Advance to next day
            $warmup->advanceDay();

            Log::info('Reddit warmup activity completed', [
                'warmup_id' => $warmup->id,
                'day' => $warmup->current_day,
                'phase' => $phase,
                'comments' => $commentsToPost,
                'posts' => $postsToCreate,
            ]);

        } catch (\Exception $e) {
            Log::error('Reddit warmup failed', [
                'warmup_id' => $warmup->id,
                'error' => $e->getMessage(),
            ]);

            $warmup->fail($e->getMessage());
        }
    }

    protected function postComment(AccountWarmup $warmup, string $phase): void
    {
        $subreddit = $this->selectRandomTarget($warmup);

        if (!$subreddit) {
            Log::warning('No subreddit configured for warmup', ['warmup_id' => $warmup->id]);
            return;
        }

        // Find a suitable post to comment on (2-3 hours old, has engagement)
        $posts = $this->findSuitablePostsForComment($subreddit);

        if (empty($posts)) {
            Log::warning('No suitable posts found for comment', [
                'warmup_id' => $warmup->id,
                'subreddit' => $subreddit,
            ]);
            return;
        }

        $post = $posts[array_rand($posts)];

        // Generate comment content
        $commentContent = $this->shouldUseAI($warmup)
            ? $this->generateAIComment($warmup, $post, $phase)
            : $this->generateTemplateComment($warmup, $post, $phase);

        // Post comment via Reddit API (placeholder - actual implementation depends on Reddit service)
        try {
            // Note: Actual Reddit posting would go here via RedditService
            // For now, we'll log it

            Log::info('Warmup comment posted', [
                'warmup_id' => $warmup->id,
                'subreddit' => $subreddit,
                'post_id' => $post['id'] ?? 'unknown',
                'phase' => $phase,
            ]);

            $warmup->recordActivity('comment', [
                'subreddit' => $subreddit,
                'post_id' => $post['id'] ?? null,
                'content_length' => strlen($commentContent),
                'phase' => $phase,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to post warmup comment', [
                'warmup_id' => $warmup->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function createPost(AccountWarmup $warmup, string $phase): void
    {
        $subreddit = $this->selectRandomTarget($warmup);

        if (!$subreddit) {
            return;
        }

        // Generate post content
        $postData = $this->shouldUseAI($warmup)
            ? $this->generateAIPost($warmup, $subreddit, $phase)
            : $this->generateTemplatePost($warmup, $subreddit, $phase);

        // Create post via Reddit API (placeholder)
        try {
            Log::info('Warmup post created', [
                'warmup_id' => $warmup->id,
                'subreddit' => $subreddit,
                'phase' => $phase,
                'title' => $postData['title'] ?? '',
            ]);

            $warmup->recordActivity('post', [
                'subreddit' => $subreddit,
                'title' => $postData['title'] ?? '',
                'content_length' => strlen($postData['content'] ?? ''),
                'phase' => $phase,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create warmup post', [
                'warmup_id' => $warmup->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function findSuitablePostsForComment(string $subreddit): array
    {
        // Placeholder: Find posts that are 2-3 hours old with some engagement
        // Actual implementation would query Reddit API for recent popular posts
        return [];
    }

    protected function generateAIComment(AccountWarmup $warmup, array $post, string $phase): string
    {
        $this->aiService = new AIReplyService($warmup->user_id);

        $postTitle = $post['title'] ?? 'Discussion';
        $postContent = $post['content'] ?? '';

        $prompt = $this->buildCommentPrompt($postTitle, $postContent, $phase);

        try {
            $response = $this->aiService->generateCustom($prompt, [
                'max_tokens' => 150,
                'temperature' => 0.8,
            ]);

            return $response['reply'];
        } catch (\Exception $e) {
            Log::error('AI comment generation failed, using template fallback', [
                'error' => $e->getMessage()
            ]);

            return $this->generateTemplateComment($warmup, $post, $phase);
        }
    }

    protected function generateTemplateComment(AccountWarmup $warmup, array $post, string $phase): string
    {
        $settings = $warmup->settings ?? [];
        $templates = $settings['comment_templates'] ?? $this->getDefaultCommentTemplates();

        $phaseTemplates = $templates[$phase] ?? $templates['introduction'];
        $template = $phaseTemplates[array_rand($phaseTemplates)];

        // Simple variable substitution
        $variables = [
            '{topic}' => $post['title'] ?? 'this',
            '{industry}' => $settings['industry'] ?? 'tech',
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    protected function generateAIPost(AccountWarmup $warmup, string $subreddit, string $phase): array
    {
        $this->aiService = new AIReplyService($warmup->user_id);

        $prompt = $this->buildPostPrompt($subreddit, $phase);

        try {
            $response = $this->aiService->generateCustom($prompt, [
                'max_tokens' => 200,
                'temperature' => 0.8,
            ]);

            // Parse title and content from AI response
            $lines = explode("\n", $response['reply'], 2);
            $title = trim($lines[0]);
            $content = isset($lines[1]) ? trim($lines[1]) : '';

            return [
                'title' => $title,
                'content' => $content,
            ];
        } catch (\Exception $e) {
            Log::error('AI post generation failed, using template fallback', [
                'error' => $e->getMessage()
            ]);

            return $this->generateTemplatePost($warmup, $subreddit, $phase);
        }
    }

    protected function generateTemplatePost(AccountWarmup $warmup, string $subreddit, string $phase): array
    {
        $settings = $warmup->settings ?? [];
        $templates = $settings['post_templates'] ?? $this->getDefaultPostTemplates();

        $phaseTemplates = $templates[$phase] ?? $templates['engagement'];
        $template = $phaseTemplates[array_rand($phaseTemplates)];

        return [
            'title' => $template['title'],
            'content' => $template['content'] ?? '',
        ];
    }

    protected function buildCommentPrompt(string $postTitle, string $postContent, string $phase): string
    {
        $guidance = match($phase) {
            'introduction' => 'Be very helpful and genuine. Focus on adding value. NO self-promotion whatsoever.',
            'engagement' => 'Be helpful and share relevant experience. Still no promotion, but you can mention general expertise.',
            'reputation' => 'Be helpful and subtly mention relevant experience. Very mild expertise demonstration is okay.',
            default => 'Be helpful and genuine.'
        };

        return <<<PROMPT
You are commenting on a Reddit post to build account reputation naturally. This is for account warmup, NOT lead generation.

Post Title: {$postTitle}
Post Content: {$postContent}

Phase: {$phase}
Guidance: {$guidance}

Write a genuine, helpful comment (30-80 words). Sound like a real person having a conversation. Use casual language, contractions, and natural speech patterns. Do NOT sound like ChatGPT.

CRITICAL:
- Be genuinely helpful, not salesy
- Use casual language (I'm, you're, that's, etc.)
- Keep it brief (30-80 words)
- No em-dashes (—), use commas or regular dashes (-)
- Sound like texting, not writing a formal response

Generate the comment:
PROMPT;
    }

    protected function buildPostPrompt(string $subreddit, string $phase): string
    {
        $postType = match($phase) {
            'engagement' => 'a genuine question or discussion starter',
            'reputation' => 'a helpful discussion or resource sharing',
            default => 'a genuine question'
        };

        return <<<PROMPT
You are creating a Reddit post in r/{$subreddit} to build account warmup. Create {$postType} that follows Reddit community rules.

Phase: {$phase}

The post should:
- Be genuine and add value to the community
- NOT be self-promotional
- Spark discussion or ask for advice
- Be 1-2 sentences for title, 2-4 sentences for content (if needed)

Format:
Title: [your title here]
Content: [optional content - can be empty for questions]

Generate the post:
PROMPT;
    }

    public function getActivityLimits(string $phase): array
    {
        return match($phase) {
            'introduction' => [
                'comments_min' => 1,
                'comments_max' => 2,
                'posts_min' => 0,
                'posts_max' => 0,
            ],
            'engagement' => [
                'comments_min' => 2,
                'comments_max' => 3,
                'posts_min' => 0,
                'posts_max' => 1,
            ],
            'reputation' => [
                'comments_min' => 3,
                'comments_max' => 4,
                'posts_min' => 1,
                'posts_max' => 1,
            ],
            default => [
                'comments_min' => 1,
                'comments_max' => 2,
                'posts_min' => 0,
                'posts_max' => 0,
            ]
        };
    }

    protected function getDefaultCommentTemplates(): array
    {
        return [
            'introduction' => [
                'That\'s interesting! I\'ve been looking into {topic} too.',
                'Thanks for sharing this. Really helpful perspective.',
                'Great question! I\'d love to hear what others think about this.',
            ],
            'engagement' => [
                'I\'ve dealt with something similar. What worked for me was staying consistent.',
                'Good point about {topic}. I found that approach helpful too.',
                'Interesting take! I\'ve had similar experiences with this.',
            ],
            'reputation' => [
                'I\'ve worked on {topic} projects before. Happy to help if you have questions.',
                'That matches what I\'ve seen in the {industry} space. Good insights.',
                'I\'ve built similar things. The key is starting simple and iterating.',
            ],
        ];
    }

    protected function getDefaultPostTemplates(): array
    {
        return [
            'engagement' => [
                ['title' => 'What tools do you recommend for beginners?', 'content' => 'Looking to learn more and would love to hear your recommendations.'],
                ['title' => 'Anyone else dealing with this challenge?', 'content' => 'Curious if others have found good solutions.'],
            ],
            'reputation' => [
                ['title' => 'Quick tip that helped me with this', 'content' => 'Thought I\'d share something that made things easier for me.'],
                ['title' => 'What\'s your experience with this approach?', 'content' => 'I\'ve tried a few different methods. Curious what has worked for others.'],
            ],
        ];
    }
}
