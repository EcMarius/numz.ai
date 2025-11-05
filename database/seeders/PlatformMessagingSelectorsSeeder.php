<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformMessagingSelectorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $messagingConfig = [
            'linkedin' => [
                'message_input_selectors' => [
                    'div.msg-form__contenteditable[contenteditable="true"][aria-multiline="true"]',
                    'div.msg-form__contenteditable[contenteditable="true"]',
                    '[aria-label="Write a message…"][contenteditable="true"]',
                    '[aria-label="Write a message"][contenteditable="true"]',
                ],
                'message_send_button_selectors' => [
                    'button.msg-form__send-button',
                    'button[type="submit"].msg-form__send-button',
                    'button[aria-label="Send"]',
                ],
                'supports_enter_to_send' => true,
            ],
            'reddit' => [
                'message_input_selectors' => [
                    'textarea[name="message"]',
                    'textarea.m-message-input',
                    'div[contenteditable="true"][role="textbox"]',
                ],
                'message_send_button_selectors' => [
                    'button[type="submit"]',
                    'button:has-text("Send")',
                ],
                'supports_enter_to_send' => true,
            ],
            'x' => [
                'message_input_selectors' => [
                    'div[data-testid="dmComposerTextInput"]',
                    'div[contenteditable="true"][role="textbox"]',
                    'div.DraftEditor-editorContainer',
                ],
                'message_send_button_selectors' => [
                    'button[data-testid="dmComposerSendButton"]',
                    'button[aria-label="Send"]',
                ],
                'supports_enter_to_send' => true,
            ],
            'facebook' => [
                'message_input_selectors' => [
                    'div[contenteditable="true"][role="textbox"]',
                    'div[aria-label="Message"]',
                    'div.notranslate',
                ],
                'message_send_button_selectors' => [
                    'button[type="submit"]',
                    'button[aria-label="Send"]',
                ],
                'supports_enter_to_send' => true,
            ],
        ];

        foreach ($messagingConfig as $platformName => $config) {
            $updated = DB::table('evenleads_platforms')
                ->where('name', $platformName)
                ->update([
                    'message_input_selectors' => json_encode($config['message_input_selectors']),
                    'message_send_button_selectors' => json_encode($config['message_send_button_selectors']),
                    'supports_enter_to_send' => $config['supports_enter_to_send'],
                ]);

            if ($updated) {
                $this->command->info("✅ Updated messaging selectors for: {$platformName}");
            } else {
                $this->command->warn("⚠️ Platform not found: {$platformName}");
            }
        }

        $this->command->info('✅ Platform messaging selectors seeded successfully!');
    }
}
