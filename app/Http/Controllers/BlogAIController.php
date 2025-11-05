<?php

namespace App\Http\Controllers;

use App\Services\BlogAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlogAIController extends Controller
{
    protected $aiService;

    public function __construct(BlogAIService $aiService)
    {
        $this->middleware('auth');
        $this->aiService = $aiService;
    }

    /**
     * Get available AI models for the current user
     */
    public function getModels()
    {
        try {
            $models = $this->aiService->getAvailableModels();
            $default = $this->aiService->getDefaultModel();

            return response()->json([
                'success' => true,
                'models' => $models,
                'default' => $default
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate blog content
     */
    public function generateContent(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
            'model' => 'nullable|string',
            'context' => 'nullable|array'
        ]);

        try {
            $content = $this->aiService->generateContent(
                $request->prompt,
                $request->model,
                $request->context ?? []
            );

            return response()->json([
                'success' => true,
                'content' => $content
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit selected text
     */
    public function editText(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'instruction' => 'required|string',
            'model' => 'nullable|string',
            'context' => 'nullable|array'
        ]);

        try {
            $edited = $this->aiService->editText(
                $request->text,
                $request->instruction,
                $request->model,
                $request->context ?? []
            );

            return response()->json([
                'success' => true,
                'content' => $edited
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick action: Make shorter
     */
    public function makeShorter(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'model' => 'nullable|string'
        ]);

        try {
            $edited = $this->aiService->makeShorter($request->text, $request->model);

            return response()->json([
                'success' => true,
                'content' => $edited
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick action: Make longer
     */
    public function makeLonger(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'model' => 'nullable|string'
        ]);

        try {
            $edited = $this->aiService->makeLonger($request->text, $request->model);

            return response()->json([
                'success' => true,
                'content' => $edited
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick action: Optimize for SEO
     */
    public function optimizeForSEO(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'model' => 'nullable|string',
            'context' => 'nullable|array'
        ]);

        try {
            $edited = $this->aiService->optimizeForSEO(
                $request->text,
                $request->model,
                $request->context ?? []
            );

            return response()->json([
                'success' => true,
                'content' => $edited
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick action: Reword
     */
    public function reword(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'model' => 'nullable|string'
        ]);

        try {
            $edited = $this->aiService->reword($request->text, $request->model);

            return response()->json([
                'success' => true,
                'content' => $edited
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
