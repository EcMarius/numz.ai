<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class ExceptionHelper
{
    /**
     * Handle exceptions securely without exposing sensitive information
     *
     * @param \Throwable $e The exception to handle
     * @param string $context Description of where the error occurred
     * @param bool $isAdmin Whether the user is an admin (still won't show SQL details)
     * @return string User-friendly error message
     */
    public static function handleException(\Throwable $e, string $context = 'operation', bool $isAdmin = false): string
    {
        // Generate unique error ID for tracking
        $errorId = uniqid('ERR_');

        // Log full error details for developers (never shown to users)
        Log::error("Error in {$context} [{$errorId}]", [
            'error_id' => $errorId,
            'exception_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
            'trace' => $e->getTraceAsString(),
            'previous' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null,
        ]);

        // CRITICAL SECURITY: NEVER expose SQL/Database errors
        // Even to admins, as errors can leak in network responses
        if (self::isSQLError($e)) {
            return self::handleSQLError($errorId, $isAdmin);
        }

        // Handle validation errors (safe to show)
        if ($e instanceof ValidationException) {
            return 'Please check the form for errors and try again.';
        }

        // Handle specific known exceptions
        if (strpos($e->getMessage(), 'exceeded') !== false && strpos($e->getMessage(), 'limit') !== false) {
            return 'You have reached a limit for your current plan. Please upgrade or contact support.';
        }

        if (strpos($e->getMessage(), 'unauthorized') !== false || strpos($e->getMessage(), 'Unauthorized') !== false) {
            return 'You do not have permission to perform this action.';
        }

        if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'Not Found') !== false) {
            return 'The requested resource was not found.';
        }

        // Generic error message (safe)
        $message = 'An unexpected error occurred. Please try again.';

        if ($isAdmin) {
            $message .= " (Error ID: {$errorId} - check logs for details)";
        } else {
            $message .= ' If the problem persists, please contact support.';
        }

        return $message;
    }

    /**
     * Check if the exception is SQL/Database related
     */
    protected static function isSQLError(\Throwable $e): bool
    {
        // Check exception type
        if ($e instanceof QueryException || $e instanceof \PDOException) {
            return true;
        }

        // Check error message content (case-insensitive)
        $message = strtolower($e->getMessage());
        $sqlIndicators = [
            'sqlstate',
            'sql',
            'query',
            'database',
            'duplicate entry',
            'foreign key constraint',
            'integrity constraint',
            'syntax error',
            'table',
            'column',
            'connection refused',
            'access denied',
            'pdo',
            'mysql',
            'postgresql',
            'sqlite',
        ];

        foreach ($sqlIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle SQL errors with extra security
     */
    protected static function handleSQLError(string $errorId, bool $isAdmin): string
    {
        // Log additional security warning
        Log::warning("SQL error exposed to user (safely handled)", [
            'error_id' => $errorId,
            'is_admin' => $isAdmin,
        ]);

        $message = 'A database error occurred. Our team has been notified.';

        if ($isAdmin) {
            $message .= " (Error ID: {$errorId} - check application logs for SQL details. NEVER expose SQL errors to users)";
        } else {
            $message .= ' Please try again or contact support if the issue persists.';
        }

        return $message;
    }

    /**
     * Get a user-friendly error notification for Filament
     *
     * @return array ['title' => string, 'body' => string, 'type' => string]
     */
    public static function getNotificationData(\Throwable $e, string $context = 'operation', bool $isAdmin = false): array
    {
        $message = self::handleException($e, $context, $isAdmin);

        // Determine notification type based on exception
        if ($e instanceof ValidationException) {
            $type = 'warning';
            $title = 'Validation Error';
        } elseif (self::isSQLError($e)) {
            $type = 'danger';
            $title = 'Database Error';
        } else {
            $type = 'danger';
            $title = 'Error';
        }

        return [
            'title' => $title,
            'body' => $message,
            'type' => $type,
        ];
    }
}
