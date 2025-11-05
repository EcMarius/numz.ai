<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * SECURITY: Never expose SQL errors to users, even in development
     */
    public function render($request, Throwable $e): JsonResponse|RedirectResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        // Check if user is authenticated and is admin
        $isAdmin = auth()->check() && auth()->user()->hasRole('admin');

        // Handle API requests differently
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiException($request, $e, $isAdmin);
        }

        // For web requests, use the secure helper
        if ($this->shouldHandleSecurely($e)) {
            $errorMessage = ExceptionHelper::handleException($e, 'web request', $isAdmin);

            // Flash error message and redirect back
            return back()->with('error', $errorMessage);
        }

        // Let Laravel handle all other exceptions normally
        return parent::render($request, $e);
    }

    /**
     * Render exception for API requests
     */
    protected function renderApiException(Request $request, Throwable $e, bool $isAdmin): JsonResponse
    {
        // Use secure helper to get safe error message
        $errorMessage = ExceptionHelper::handleException($e, 'API request', $isAdmin);

        // Determine HTTP status code
        $statusCode = $this->getStatusCode($e);

        return response()->json([
            'success' => false,
            'error' => [
                'message' => $errorMessage,
                'type' => class_basename($e),
            ],
        ], $statusCode);
    }

    /**
     * Determine if this exception should be handled securely
     */
    protected function shouldHandleSecurely(Throwable $e): bool
    {
        // Always handle SQL/Database errors securely
        if ($e instanceof \Illuminate\Database\QueryException || $e instanceof \PDOException) {
            return true;
        }

        // Check if error message contains SQL-related content
        $message = strtolower($e->getMessage());
        $sqlIndicators = ['sqlstate', 'sql', 'query', 'database', 'table', 'column'];

        foreach ($sqlIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get appropriate HTTP status code for exception
     */
    protected function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return 422;
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return 401;
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403;
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return 404;
        }

        if ($e instanceof \Illuminate\Database\QueryException || $e instanceof \PDOException) {
            return 500;
        }

        return 500;
    }
}
