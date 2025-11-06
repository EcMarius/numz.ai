@extends('installer.layout')

@section('title', 'System Requirements')

@section('content')
<div class="fade-in">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-check-circle text-green-500 mr-2"></i>System Requirements
    </h2>

    <!-- PHP Requirements -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">PHP Requirements</h3>
        <div class="space-y-2">
            @foreach($requirements as $req)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-700">{{ $req['name'] }}</span>
                    <div class="flex items-center">
                        @if(isset($req['current']))
                            <span class="text-sm text-gray-500 mr-3">{{ $req['current'] }}</span>
                        @endif
                        @if($req['check'])
                            <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        @else
                            <i class="fas fa-times-circle text-red-500 text-xl"></i>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Permissions -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Directory Permissions</h3>
        <div class="space-y-2">
            @foreach($permissions as $perm)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-700">{{ $perm['name'] }}</span>
                    @if($perm['check'])
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    @else
                        <i class="fas fa-times-circle text-red-500 text-xl"></i>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('installer.index') }}" 
           class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
        
        @if($allPassed)
            <a href="{{ route('installer.license') }}" 
               class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all">
                Continue <i class="fas fa-arrow-right ml-2"></i>
            </a>
        @else
            <button disabled 
                    class="px-6 py-3 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed">
                Fix Requirements First
            </button>
        @endif
    </div>
</div>
@endsection
