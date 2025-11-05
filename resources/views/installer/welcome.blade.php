@extends('installer.layout')

@section('title', 'Welcome')

@section('content')
<div class="text-center fade-in">
    <div class="mb-8">
        <i class="fas fa-rocket text-6xl text-purple-600 pulse"></i>
    </div>
    
    <h2 class="text-3xl font-bold text-gray-800 mb-4">
        Welcome to NUMZ.AI Installation
    </h2>
    
    <p class="text-gray-600 mb-8 text-lg max-w-2xl mx-auto">
        Thank you for choosing NUMZ.AI - The world's first AI-powered hosting billing software.
        This wizard will guide you through the installation process.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="card-hover bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl">
            <i class="fas fa-bolt text-3xl text-blue-600 mb-3"></i>
            <h3 class="font-semibold text-gray-800 mb-2">Fast Setup</h3>
            <p class="text-sm text-gray-600">Quick 5-minute installation process</p>
        </div>
        
        <div class="card-hover bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl">
            <i class="fas fa-shield-alt text-3xl text-purple-600 mb-3"></i>
            <h3 class="font-semibold text-gray-800 mb-2">Secure</h3>
            <p class="text-sm text-gray-600">Enterprise-grade security features</p>
        </div>
        
        <div class="card-hover bg-gradient-to-br from-pink-50 to-pink-100 p-6 rounded-xl">
            <i class="fas fa-rocket text-3xl text-pink-600 mb-3"></i>
            <h3 class="font-semibold text-gray-800 mb-2">AI-Powered</h3>
            <p class="text-sm text-gray-600">Intelligent automation included</p>
        </div>
    </div>

    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8 text-left">
        <div class="flex">
            <i class="fas fa-exclamation-triangle text-yellow-400 mt-1 mr-3"></i>
            <div>
                <p class="text-sm text-yellow-700">
                    <strong>Before you begin:</strong> Make sure you have your database credentials and license key ready.
                </p>
            </div>
        </div>
    </div>

    <a href="{{ route('installer.requirements') }}" 
       class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:shadow-lg transition-all duration-300 transform hover:scale-105">
        Get Started <i class="fas fa-arrow-right ml-2"></i>
    </a>
</div>
@endsection
