<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NUMZ.AI Installer - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .spinner {
            animation: spin 1s linear infinite;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8 fade-in">
            <h1 class="text-5xl font-bold text-white mb-2">
                <i class="fas fa-rocket mr-3"></i>NUMZ.AI
            </h1>
            <p class="text-white text-opacity-90 text-lg">The First AI-Powered Hosting Billing Software</p>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden fade-in">
            <!-- Progress Steps -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-8 py-6">
                <div class="flex justify-between items-center">
                    @php
                        $steps = [
                            ['name' => 'Welcome', 'route' => 'installer.index'],
                            ['name' => 'Requirements', 'route' => 'installer.requirements'],
                            ['name' => 'License', 'route' => 'installer.license'],
                            ['name' => 'Database', 'route' => 'installer.database'],
                            ['name' => 'Admin', 'route' => 'installer.admin'],
                        ];
                        $currentStep = request()->route()->getName();
                    @endphp

                    @foreach($steps as $index => $step)
                        <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold
                                    {{ $currentStep === $step['route'] ? 'bg-white text-purple-600' : 'bg-white bg-opacity-30 text-white' }}">
                                    {{ $index + 1 }}
                                </div>
                                <span class="text-xs text-white mt-2">{{ $step['name'] }}</span>
                            </div>
                            @if(!$loop->last)
                                <div class="flex-1 h-0.5 bg-white bg-opacity-30 mx-2"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Content -->
            <div class="p-8">
                @yield('content')
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-white text-opacity-75 fade-in">
            <p>© {{ date('Y') }} NUMZ.AI - Powered by Innovation</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @stack('scripts')
</body>
</html>
