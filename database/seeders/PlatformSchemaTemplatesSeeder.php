<?php

namespace Database\Seeders;

use App\Models\PlatformSchema;
use Illuminate\Database\Seeder;

class PlatformSchemaTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // LinkedIn Post Schema
        $this->createLinkedInPostSchema();

        // Reddit Post Schema
        $this->createRedditPostSchema();

        // X (Twitter) Post Schema
        $this->createXPostSchema();

        // LinkedIn Person Schema
        $this->createLinkedInPersonSchema();

        // Reddit Person Schema
        $this->createRedditPersonSchema();
    }

    private function createLinkedInPostSchema(): void
    {
        $schemas = [
            [
                'platform' => 'linkedin',
                'page_type' => 'search_list',
                'element_type' => 'post_wrapper',
                'css_selector' => 'div[data-urn]',
                'xpath_selector' => '//div[@data-urn]',
                'is_required' => true,
                'multiple' => true,
                'is_wrapper' => true,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Main post container with data-urn attribute',
                'order' => 0,
            ],
            [
                'platform' => 'linkedin',
                'page_type' => 'search_list',
                'element_type' => 'post_title',
                'css_selector' => '.update-components-actor__title',
                'xpath_selector' => './/span[contains(@class, "update-components-actor__title")]',
                'is_required' => false,
                'parent_element' => 'post_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Post author title/headline',
                'order' => 1,
            ],
            [
                'platform' => 'linkedin',
                'page_type' => 'search_list',
                'element_type' => 'post_content',
                'css_selector' => '.feed-shared-text',
                'xpath_selector' => './/div[contains(@class, "feed-shared-text")]',
                'is_required' => true,
                'parent_element' => 'post_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Main post content text',
                'order' => 2,
            ],
            [
                'platform' => 'linkedin',
                'page_type' => 'search_list',
                'element_type' => 'author_name',
                'css_selector' => '.update-components-actor__name',
                'xpath_selector' => './/span[contains(@class, "update-components-actor__name")]',
                'is_required' => false,
                'parent_element' => 'post_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Post author name',
                'order' => 3,
            ],
            [
                'platform' => 'linkedin',
                'page_type' => 'search_list',
                'element_type' => 'post_url',
                'css_selector' => 'a[data-control-name="feed-post"]',
                'xpath_selector' => './/a[@data-control-name="feed-post"]/@href',
                'is_required' => false,
                'parent_element' => 'post_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Link to the post',
                'order' => 4,
            ],
        ];

        foreach ($schemas as $schema) {
            PlatformSchema::create($schema);
        }
    }

    private function createRedditPostSchema(): void
    {
        $schemas = [
            [
                'platform' => 'reddit',
                'page_type' => 'search_list',
                'element_type' => 'post_wrapper',
                'css_selector' => 'shreddit-post',
                'xpath_selector' => '//shreddit-post',
                'is_required' => true,
                'multiple' => true,
                'is_wrapper' => true,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Reddit post custom element (new design)',
                'order' => 0,
            ],
            [
                'platform' => 'reddit',
                'page_type' => 'search_list',
                'element_type' => 'post_title',
                'css_selector' => 'h3, [slot="title"]',
                'xpath_selector' => './/h3 | .//*[@slot="title"]',
                'is_required' => true,
                'parent_element' => 'post_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Post title element',
                'order' => 1,
            ],
            [
                'platform' => 'reddit',
                'page_type' => 'search_list',
                'element_type' => 'post_content',
                'css_selector' => 'div[slot="text-body"]',
                'xpath_selector' => './/div[@slot="text-body"]',
                'is_required' => false,
                'parent_element' => 'post_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Post content/body',
                'order' => 2,
            ],
            [
                'platform' => 'reddit',
                'page_type' => 'search_list',
                'element_type' => 'author_name',
                'css_selector' => 'a[slot="author-link"]',
                'xpath_selector' => './/a[@slot="author-link"]',
                'is_required' => false,
                'parent_element' => 'post_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Post author username',
                'order' => 3,
            ],
        ];

        foreach ($schemas as $schema) {
            PlatformSchema::create($schema);
        }
    }

    private function createXPostSchema(): void
    {
        $schemas = [
            [
                'platform' => 'x',
                'page_type' => 'search_list',
                'element_type' => 'post_wrapper',
                'css_selector' => 'article[data-testid="tweet"]',
                'xpath_selector' => '//article[@data-testid="tweet"]',
                'is_required' => true,
                'multiple' => true,
                'is_wrapper' => true,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'X/Twitter tweet container',
                'order' => 0,
            ],
            [
                'platform' => 'x',
                'page_type' => 'search_list',
                'element_type' => 'post_content',
                'css_selector' => '[data-testid="tweetText"]',
                'xpath_selector' => './/*[@data-testid="tweetText"]',
                'is_required' => true,
                'parent_element' => 'post_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Tweet text content',
                'order' => 1,
            ],
            [
                'platform' => 'x',
                'page_type' => 'search_list',
                'element_type' => 'author_name',
                'css_selector' => '[data-testid="User-Name"] a',
                'xpath_selector' => './/*[@data-testid="User-Name"]//a',
                'is_required' => false,
                'parent_element' => 'post_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Tweet author display name',
                'order' => 2,
            ],
        ];

        foreach ($schemas as $schema) {
            PlatformSchema::create($schema);
        }
    }

    private function createLinkedInPersonSchema(): void
    {
        $schemas = [
            [
                'platform' => 'linkedin',
                'page_type' => 'profile',
                'element_type' => 'person_wrapper',
                'css_selector' => 'div.scaffold-layout__main',
                'xpath_selector' => '//div[contains(@class, "scaffold-layout__main")]',
                'is_required' => true,
                'multiple' => false,
                'is_wrapper' => true,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Main profile container',
                'order' => 0,
            ],
            [
                'platform' => 'linkedin',
                'page_type' => 'profile',
                'element_type' => 'person_name',
                'css_selector' => 'h1',
                'xpath_selector' => './/h1',
                'is_required' => true,
                'parent_element' => 'person_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Person full name',
                'order' => 1,
            ],
            [
                'platform' => 'linkedin',
                'page_type' => 'profile',
                'element_type' => 'person_headline',
                'css_selector' => 'div.text-body-medium',
                'xpath_selector' => './/div[contains(@class, "text-body-medium")]',
                'is_required' => false,
                'parent_element' => 'person_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Person headline/title',
                'order' => 2,
            ],
        ];

        foreach ($schemas as $schema) {
            PlatformSchema::create($schema);
        }
    }

    private function createRedditPersonSchema(): void
    {
        $schemas = [
            [
                'platform' => 'reddit',
                'page_type' => 'profile',
                'element_type' => 'person_wrapper',
                'css_selector' => 'shreddit-profile-header',
                'xpath_selector' => '//shreddit-profile-header',
                'is_required' => true,
                'multiple' => false,
                'is_wrapper' => true,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Reddit profile header element',
                'order' => 0,
            ],
            [
                'platform' => 'reddit',
                'page_type' => 'profile',
                'element_type' => 'person_name',
                'css_selector' => 'h1',
                'xpath_selector' => './/h1',
                'is_required' => true,
                'parent_element' => 'person_wrapper',
                'multiple' => false,
                'version' => '1.0.0',
                'is_active' => true,
                'description' => 'Reddit username',
                'order' => 1,
            ],
        ];

        foreach ($schemas as $schema) {
            PlatformSchema::create($schema);
        }
    }
}
