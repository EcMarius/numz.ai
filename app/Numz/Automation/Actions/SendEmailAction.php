<?php

namespace App\Numz\Automation\Actions;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendEmailAction implements ActionInterface
{
    public function execute(array $params, array $data): array
    {
        try {
            $to = $params['to'] ?? $data['user_email'] ?? null;
            $templateSlug = $params['template'] ?? null;
            $subject = $params['subject'] ?? null;
            $variables = $params['variables'] ?? [];

            if (!$to) {
                throw new \Exception('Recipient email address is required');
            }

            // If template is specified, use it
            if ($templateSlug) {
                $template = EmailTemplate::where('slug', $templateSlug)
                    ->where('is_active', true)
                    ->first();

                if (!$template) {
                    throw new \Exception("Email template '{$templateSlug}' not found or inactive");
                }

                // Merge trigger data with variables
                $allVariables = array_merge($data, $variables);

                // Render template
                $subject = $template->renderSubject($allVariables);
                $body = $template->render($allVariables);

                Mail::send([], [], function ($message) use ($to, $subject, $body, $template) {
                    $message->to($to)
                        ->subject($subject)
                        ->html($body);

                    if ($template->from_email) {
                        $message->from($template->from_email, $template->from_name ?? config('mail.from.name'));
                    }

                    if ($template->reply_to) {
                        $message->replyTo($template->reply_to);
                    }
                });
            } else {
                // Send custom email
                $body = $params['body'] ?? '';

                if (!$subject || !$body) {
                    throw new \Exception('Subject and body are required when not using a template');
                }

                Mail::send([], [], function ($message) use ($to, $subject, $body) {
                    $message->to($to)
                        ->subject($subject)
                        ->html($body);
                });
            }

            return [
                'success' => true,
                'action' => 'send_email',
                'message' => "Email sent to {$to}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'action' => 'send_email',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getName(): string
    {
        return 'Send Email';
    }

    public function getDescription(): string
    {
        return 'Send an email using a template or custom content';
    }

    public function getRequiredParams(): array
    {
        return [
            'to' => 'Recipient email address',
            'template' => 'Email template slug (optional if using custom content)',
            'subject' => 'Email subject (required if not using template)',
            'body' => 'Email body (required if not using template)',
            'variables' => 'Template variables (optional)',
        ];
    }
}
