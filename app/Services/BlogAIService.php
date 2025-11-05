<?php

namespace App\Services;

use Wave\Plugins\EvenLeads\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class BlogAIService
{
    protected $apiKey;
    protected $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId ?? Auth::id();
        $this->apiKey = Setting::getValue('openai_api_key');
    }

    /**
     * Get available models for the user based on their plan
     */
    public function getAvailableModels(): array
    {
        $user = \App\Models\User::find($this->userId);

        if (!$user || !$user->subscription('default')) {
            $defaultModel = Setting::getValue('openai_model', 'gpt-4o-mini');
            return [$defaultModel];
        }

        $plan = $user->subscription('default')->plan;

        if (!$plan || empty($plan->openai_models)) {
            $defaultModel = Setting::getValue('openai_model', 'gpt-4o-mini');
            return [$defaultModel];
        }

        return is_array($plan->openai_models) ? $plan->openai_models : [$plan->openai_models];
    }

    /**
     * Get the default model for the user
     */
    public function getDefaultModel(): string
    {
        $models = $this->getAvailableModels();
        return $models[0] ?? 'gpt-4o-mini';
    }

    /**
     * Generate blog content based on prompt
     */
    public function generateContent(string $prompt, string $model = null, array $context = []): string
    {
        if (!$model) {
            $model = $this->getDefaultModel();
        }

        $systemPrompt = "You are an expert content writer specializing in SEO-optimized blog posts for B2B SaaS companies, specifically for lead generation platforms. Write in a professional yet conversational tone. Include HTML formatting (h2, h3, p, ul, li, blockquote, figure, img tags). Make content comprehensive, actionable, and SEO-friendly.";

        if (!empty($context['title'])) {
            $systemPrompt .= "\n\nBlog Post Title: " . $context['title'];
        }

        if (!empty($context['excerpt'])) {
            $systemPrompt .= "\n\nExcerpt: " . $context['excerpt'];
        }

        if (!empty($context['keywords'])) {
            $systemPrompt .= "\n\nTarget Keywords: " . $context['keywords'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 4000,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content', '');
            }

            throw new \Exception('AI API request failed: ' . $response->body());
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate content: ' . $e->getMessage());
        }
    }

    /**
     * Edit selected text based on instruction
     */
    public function editText(string $text, string $instruction, string $model = null, array $context = []): string
    {
        if (!$model) {
            $model = $this->getDefaultModel();
        }

        $systemPrompt = "You are a professional content editor. Edit the provided text according to the user's instruction. Return ONLY the edited text, no explanations or additional commentary. Maintain HTML formatting if present.";

        if (!empty($context['post_title'])) {
            $systemPrompt .= "\n\nContext: This is content from a blog post titled '" . $context['post_title'] . "'";
        }

        $userPrompt = "Text to edit:\n\n" . $text . "\n\nInstruction: " . $instruction;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content', '');
            }

            throw new \Exception('AI API request failed: ' . $response->body());
        } catch (\Exception $e) {
            throw new \Exception('Failed to edit text: ' . $e->getMessage());
        }
    }

    /**
     * Make text shorter
     */
    public function makeShorter(string $text, string $model = null): string
    {
        return $this->editText($text, "Make this text more concise while preserving key information and maintaining the same tone.", $model);
    }

    /**
     * Make text longer
     */
    public function makeLonger(string $text, string $model = null): string
    {
        return $this->editText($text, "Expand this text with more details, examples, and explanation while maintaining the same tone and message.", $model);
    }

    /**
     * Optimize for SEO
     */
    public function optimizeForSEO(string $text, string $model = null, array $context = []): string
    {
        $instruction = "Optimize this text for SEO while keeping it natural and readable. Add relevant keywords naturally, improve structure with headings if appropriate, and ensure it's compelling for both search engines and readers.";

        if (!empty($context['keywords'])) {
            $instruction .= " Target keywords: " . $context['keywords'];
        }

        return $this->editText($text, $instruction, $model, $context);
    }

    /**
     * Reword text
     */
    public function reword(string $text, string $model = null): string
    {
        return $this->editText($text, "Rewrite this text using different words and sentence structures while keeping the exact same meaning and tone.", $model);
    }
}
