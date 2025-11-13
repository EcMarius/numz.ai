<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiDocumentationController extends Controller
{
    /**
     * Display API documentation
     */
    public function index()
    {
        $apiUrl = url('/api/v1');

        return view('api.docs', compact('apiUrl'));
    }
}
