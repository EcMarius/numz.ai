<x-filament-panels::page>
    <!-- Upload Section -->
    <div class="w-full mb-6" x-data="{ showUpload: false }">
        <x-filament::section class="w-full">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Upload Plugin</h2>
                <button @click="showUpload = !showUpload" type="button"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span x-text="showUpload ? 'Hide' : 'Add Plugin'"></span>
                </button>
            </div>

            <div class="space-y-4" x-show="showUpload" x-transition x-cloak>
            <div
                x-data="{
                    uploading: false,
                    installing: false,
                    progress: 0,
                    error: null,
                    errorDetails: null,
                    success: null,
                    fileName: '',

                    async handleDrop(e) {
                        this.error = null;
                        this.errorDetails = null;
                        this.success = null;
                        let files = e.dataTransfer.files;
                        if (files.length > 0) {
                            await this.uploadFile(files[0]);
                        }
                    },

                    async handleFileSelect(e) {
                        this.error = null;
                        this.errorDetails = null;
                        this.success = null;
                        let files = e.target.files;
                        if (files.length > 0) {
                            await this.uploadFile(files[0]);
                        }
                    },

                    async uploadFile(file) {
                        if (!file.name.endsWith('.zip')) {
                            this.error = 'Please upload a ZIP file';
                            return;
                        }

                        this.uploading = true;
                        this.installing = true;
                        this.progress = 0;
                        this.fileName = file.name;

                        const formData = new FormData();
                        formData.append('plugin', file);
                        formData.append('_token', document.querySelector('meta[name=csrf-token]').content);

                        try {
                            const xhr = new XMLHttpRequest();

                            xhr.upload.addEventListener('progress', (e) => {
                                if (e.lengthComputable) {
                                    this.progress = Math.round((e.loaded / e.total) * 100);
                                }
                            });

                            xhr.addEventListener('load', () => {
                                if (xhr.status === 200) {
                                    try {
                                        const response = JSON.parse(xhr.responseText);
                                        if (response.success) {
                                            this.success = response.message;
                                            this.installing = false; // Hide loading immediately
                                            this.uploading = false;

                                            // Refresh plugin list after showing success
                                            setTimeout(() => {
                                                window.location.reload();
                                            }, 2000);
                                        } else {
                                            this.error = response.message || 'Installation failed';
                                            this.installing = false;
                                            this.uploading = false;
                                        }
                                    } catch (e) {
                                        this.error = 'Invalid response from server';
                                        this.installing = false;
                                        this.uploading = false;
                                    }
                                } else {
                                    try {
                                        const response = JSON.parse(xhr.responseText);
                                        this.error = response.message || response.error || 'Upload failed';
                                        // Store additional error details
                                        if (response.trace) {
                                            this.errorDetails = response.trace;
                                        } else if (response.errors) {
                                            this.errorDetails = JSON.stringify(response.errors, null, 2);
                                        }
                                    } catch (e) {
                                        this.error = `Server error: ${xhr.status} - ${xhr.statusText}`;
                                        this.errorDetails = xhr.responseText;
                                    }
                                    this.installing = false;
                                    this.uploading = false;
                                }
                            });

                            xhr.addEventListener('error', () => {
                                this.error = 'Network error occurred';
                                this.installing = false;
                                this.uploading = false;
                            });

                            xhr.open('POST', '/admin/plugins/upload', true);
                            xhr.send(formData);

                        } catch (error) {
                            this.error = 'Upload failed: ' + error.message;
                            this.installing = false;
                            this.uploading = false;
                        }
                    }
                }"
                class="border-2 border-dashed rounded-lg p-8 text-center transition-all duration-300"
                :class="{
                    'border-primary-500 bg-primary-50 dark:bg-primary-900/10 scale-105': uploading && !installing,
                    'border-gray-300 dark:border-gray-700': !uploading,
                    'border-success-500 bg-success-50 dark:bg-success-900/10': success,
                    'border-danger-500 bg-danger-50 dark:bg-danger-900/10': error
                }"
                @dragover.prevent="uploading = true"
                @dragleave.prevent="uploading = false"
                @drop.prevent="handleDrop($event); uploading = false"
            >
                <div class="space-y-4">
                    <!-- Upload Icon / Animation -->
                    <div class="flex justify-center">
                        <div x-show="!installing && !success && !error" class="transition-all duration-300" :class="uploading ? 'scale-110' : ''">
                            <x-phosphor-cloud-arrow-up class="w-12 h-12 text-gray-400" />
                        </div>

                        <!-- Installing Spinner -->
                        <div x-show="installing && !error" class="relative">
                            <svg class="animate-spin h-12 w-12 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <!-- Success Icon -->
                        <div x-show="success" x-transition class="text-success-600">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>

                        <!-- Error Icon -->
                        <div x-show="error" x-transition class="text-danger-600">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div>
                        <div x-show="!installing && !success && !error">
                            <p class="text-lg font-medium">Drop your plugin ZIP file here</p>
                            <p class="text-sm text-gray-500">or click to browse</p>
                        </div>

                        <div x-show="installing && !error" x-transition>
                            <p class="text-lg font-medium text-primary-600">Installing Plugin...</p>
                            <p class="text-sm text-gray-500" x-text="fileName"></p>
                        </div>

                        <div x-show="success" x-transition>
                            <p class="text-lg font-medium text-success-600">Plugin Installed Successfully!</p>
                            <p class="text-sm text-gray-500" x-text="success"></p>
                        </div>

                        <div x-show="error" x-transition>
                            <p class="text-lg font-medium text-danger-600">Installation Failed</p>
                            <p class="text-sm text-danger-600 mt-2 font-semibold" x-text="error"></p>

                            <!-- Error Details (expandable) -->
                            <div x-show="errorDetails" class="mt-3">
                                <details class="text-left">
                                    <summary class="cursor-pointer text-xs text-danger-500 hover:text-danger-700 font-medium">
                                        View Error Details
                                    </summary>
                                    <div class="mt-2 p-3 bg-danger-50 dark:bg-danger-900/20 rounded border border-danger-200 dark:border-danger-800">
                                        <pre class="text-xs text-danger-700 dark:text-danger-300 overflow-x-auto whitespace-pre-wrap break-words" x-text="errorDetails"></pre>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div x-show="installing && progress > 0" x-transition class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-300" :style="`width: ${progress}%`"></div>
                    </div>

                    <!-- Browse Button -->
                    <div x-show="!installing">
                        <input
                            type="file"
                            accept=".zip"
                            class="hidden"
                            id="plugin-upload-input"
                            @change="handleFileSelect($event)"
                        >
                        <label for="plugin-upload-input">
                            <span class="cursor-pointer inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                                <x-phosphor-folder-open class="w-5 h-5 mr-2" />
                                Browse Files
                            </span>
                        </label>
                    </div>

                    <!-- Retry Button -->
                    <div x-show="error" x-transition>
                        <button @click="error = null; document.getElementById('plugin-upload-input').click()"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Try Again
                        </button>
                    </div>
                </div>
            </div>
            </div>
        </x-filament::section>
    </div>

    <!-- Installed Plugins Section -->
    <x-filament::section class="w-full">
        <x-slot name="heading">
            Installed Plugins
        </x-slot>

        <x-slot name="headerEnd">
            <button
                wire:click="clearCache"
                type="button"
                class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
            >
                <x-phosphor-trash class="w-4 h-4 mr-1" />
                Clear Cache
            </button>
        </x-slot>

        <div class="relative w-full">
            @if(count($plugins) < 1)
                <x-empty-state description="No plugins found in your plugins folder" />
            @endif

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3 md:grid-cols-2">
                @foreach($plugins as $pluginFolder => $plugin)
                    <div class="overflow-hidden border rounded-md border-neutral-200 dark:border-neutral-700">
                        <img class="relative" src="{{ url('wave/plugin/image' ) }}/{{ $pluginFolder }}">
                        <div class="flex items-center justify-between flex-shrink-0 w-full p-4 border-b border-neutral-200 dark:border-neutral-700">
                            <div class="relative flex flex-col pr-3">
                                <h4 class="font-semibold">{{ $plugin['name'] }}</h4>
                                <p class="text-xs text-zinc-500">{{ $plugin['description'] }}</p>
                                <p class="text-xs text-zinc-500">{{ 'Version ' . ($plugin['version']['version'] ?? '') }}</p>
                            </div>
                            <div class="relative flex items-center space-x-1">
                                <button wire:click="deletePlugin('{{ $pluginFolder }}')" wire:confirm="Are you sure you want to delete {{ $plugin['name'] }}?" class="flex items-center justify-center w-8 h-8 border rounded-md border-zinc-200 dark:border-zinc-700 dark:hover:bg-zinc-800 hover:bg-zinc-200">
                                    <x-phosphor-trash-bold class="w-4 h-4 text-red-500" />
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center w-full p-4 space-x-2">
                            @if($plugin['active'])
                                <div class="flex justify-center items-center px-2 py-1.5 space-x-1.5 w-full text-sm text-center text-white bg-blue-500 rounded">
                                    <x-phosphor-check-bold class="w-4 h-4 text-white" />
                                    <span>Active</span>
                                </div>
                                <button wire:click="deactivate('{{ $pluginFolder }}')" class="flex justify-center items-center px-2 py-1.5 space-x-1.5 w-full text-sm text-red-500 hover:bg-red-500 rounded border border-neutral-200 dark:border-neutral-700 hover:text-white hover:border-red-600">
                                    <x-phosphor-power-bold class="w-4 h-4" />
                                    <span>Deactivate</span>
                                </button>
                            @else
                                <button wire:click="activate('{{ $pluginFolder }}')" class="flex justify-center items-center px-2 py-1.5 space-x-1.5 w-full text-sm text-blue-500 rounded border border-neutral-200 dark:border-neutral-700 hover:text-white hover:bg-blue-500 hover:border-blue-600">
                                    <x-phosphor-power-bold class="w-4 h-4" />
                                    <span>Activate</span>
                                </button>
                            @endif
                        </div>
                        @if($this->hasMigrations($pluginFolder))
                            <div class="flex items-center w-full px-4 pb-4">
                                <button wire:click="refreshDatabaseTables('{{ $pluginFolder }}')"
                                        wire:confirm="This will refresh all database tables for this plugin. Are you sure?"
                                        class="flex justify-center items-center px-2 py-1.5 space-x-1.5 w-full text-sm text-gray-700 hover:bg-gray-100 rounded border border-neutral-200 dark:border-neutral-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                    <x-phosphor-arrow-clockwise-bold class="w-4 h-4" />
                                    <span>Refresh Database Tables</span>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>