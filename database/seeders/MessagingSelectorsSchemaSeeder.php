<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlatformSchema;

class MessagingSelectorsSchemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Profile message button selectors for each platform
        $profileMessageButtonSelectors = [
            'linkedin' => [
                [
                    'css_selector' => 'button[aria-label*="Message"]',
                    'xpath_selector' => '//button[contains(@aria-label, "Message")]',
                    'description' => 'Message button on profile via aria-label',
                    'notes' => 'Primary selector using aria-label attribute',
                ],
                [
                    'css_selector' => '.artdeco-button__text',
                    'description' => 'Message button text element (fallback)',
                    'notes' => 'Requires text matching: .textContent.includes("Message")',
                ],
            ],
            'reddit' => [
                [
                    'css_selector' => 'button:has-text("Send Message")',
                    'description' => 'Send Message button on profile',
                ],
                [
                    'css_selector' => 'a[href*="/message/compose"]',
                    'description' => 'Message compose link (fallback)',
                ],
            ],
            'x' => [
                [
                    'css_selector' => 'a[data-testid="sendDMFromProfile"]',
                    'description' => 'Send DM button on profile',
                ],
                [
                    'css_selector' => 'a[aria-label="Message"]',
                    'description' => 'Message button (fallback)',
                ],
            ],
            'facebook' => [
                [
                    'css_selector' => 'a[aria-label="Message"]',
                    'description' => 'Message button on profile',
                ],
                [
                    'css_selector' => 'div[role="button"]:has-text("Message")',
                    'description' => 'Message div button (fallback)',
                ],
            ],
        ];

        foreach ($profileMessageButtonSelectors as $platformName => $selectors) {
            foreach ($selectors as $order => $selectorData) {
                PlatformSchema::create([
                    'platform' => $platformName,
                    'page_type' => 'profile',
                    'element_type' => 'profile_message_button',
                    'css_selector' => $selectorData['css_selector'],
                    'xpath_selector' => null,
                    'is_required' => $order === 0,
                    'fallback_value' => null,
                    'parent_element' => null,
                    'multiple' => false,
                    'is_wrapper' => false,
                    'version' => '1.0.0',
                    'is_active' => true,
                    'description' => $selectorData['description'],
                    'notes' => $selectorData['notes'] ?? 'Profile message button selector',
                    'order' => $order,
                ]);

                $this->command->info("✅ Created profile_message_button selector for {$platformName} (order: {$order})");
            }
        }

        // Message input selectors for messaging page
        $messageInputSelectors = [
            'linkedin' => [
                [
                    'css_selector' => '.msg-form__contenteditable',
                    'xpath_selector' => '//div[contains(@class, "msg-form__contenteditable")]',
                    'description' => 'Message input field in messaging interface',
                ],
                [
                    'css_selector' => '[aria-label*="Write a message"]',
                    'description' => 'Message input via aria-label (fallback)',
                ],
            ],
        ];

        // Message send button selectors for messaging page
        $messageSendButtonSelectors = [
            'linkedin' => [
                [
                    'css_selector' => '.msg-form__send-button',
                    'description' => 'Send button in messaging form',
                ],
                [
                    'css_selector' => 'button[type="submit"].msg-form__send-button',
                    'xpath_selector' => '//button[@type="submit" and contains(@class, "msg-form__send-button")]',
                    'description' => 'Send button (specific)',
                ],
            ],
        ];

        // Create message_input selectors
        foreach ($messageInputSelectors as $platformName => $selectors) {
            foreach ($selectors as $order => $selectorData) {
                PlatformSchema::create([
                    'platform' => $platformName,
                    'page_type' => 'messaging',
                    'element_type' => 'message_input',
                    'css_selector' => $selectorData['css_selector'],
                    'xpath_selector' => $selectorData['xpath_selector'] ?? null,
                    'is_required' => $order === 0,
                    'fallback_value' => null,
                    'parent_element' => null,
                    'multiple' => false,
                    'is_wrapper' => false,
                    'version' => '1.0.0',
                    'is_active' => true,
                    'description' => $selectorData['description'],
                    'notes' => 'Message input field selector',
                    'order' => $order,
                ]);

                $this->command->info("✅ Created message_input selector for {$platformName} (order: {$order})");
            }
        }

        // Create message_send_button selectors
        foreach ($messageSendButtonSelectors as $platformName => $selectors) {
            foreach ($selectors as $order => $selectorData) {
                PlatformSchema::create([
                    'platform' => $platformName,
                    'page_type' => 'messaging',
                    'element_type' => 'message_send_button',
                    'css_selector' => $selectorData['css_selector'],
                    'xpath_selector' => $selectorData['xpath_selector'] ?? null,
                    'is_required' => $order === 0,
                    'fallback_value' => null,
                    'parent_element' => null,
                    'multiple' => false,
                    'is_wrapper' => false,
                    'version' => '1.0.0',
                    'is_active' => true,
                    'description' => $selectorData['description'],
                    'notes' => 'Message send button selector',
                    'order' => $order,
                ]);

                $this->command->info("✅ Created message_send_button selector for {$platformName} (order: {$order})");
            }
        }

        $this->command->info('✅ Messaging selectors schema seeded successfully!');
    }
}
