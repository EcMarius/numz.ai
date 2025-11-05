<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestFileUpload extends Command
{
    protected $signature = 'test:upload';
    protected $description = 'Test file upload configuration';

    public function handle()
    {
        $this->info('🔍 Testing File Upload Configuration...');
        $this->newLine();

        // 1. Check storage paths
        $this->info('📁 Storage Paths:');
        $this->line('  Public disk root: ' . Storage::disk('public')->path(''));
        $this->line('  Storage app/public: ' . storage_path('app/public'));
        $this->newLine();

        // 2. Check if public disk is writable
        $this->info('✍️ Write Permission Tests:');
        try {
            Storage::disk('public')->put('test.txt', 'test');
            $this->line('  ✅ Can write to public disk');
            Storage::disk('public')->delete('test.txt');
        } catch (\Exception $e) {
            $this->error('  ❌ Cannot write to public disk: ' . $e->getMessage());
        }
        $this->newLine();

        // 3. Check symlink
        $this->info('🔗 Symlink Check:');
        $publicStorage = public_path('storage');
        if (is_link($publicStorage)) {
            $this->line('  ✅ Storage symlink exists');
            $this->line('  Links to: ' . readlink($publicStorage));
        } else {
            $this->error('  ❌ Storage symlink missing! Run: php artisan storage:link');
        }
        $this->newLine();

        // 4. Check livewire temp directory
        $this->info('📂 Livewire Temp Directory:');
        $livewireTmp = storage_path('app/public/livewire-tmp');
        if (file_exists($livewireTmp)) {
            $this->line('  ✅ Directory exists: ' . $livewireTmp);
            $this->line('  Writable: ' . (is_writable($livewireTmp) ? 'Yes' : 'No'));
        } else {
            $this->warn('  ⚠️  Directory does not exist, will be created on first upload');
        }
        $this->newLine();

        // 5. Check settings directory
        $this->info('📂 Settings Directory:');
        $settingsDir = storage_path('app/public/settings');
        if (file_exists($settingsDir)) {
            $this->line('  ✅ Directory exists: ' . $settingsDir);
            $this->line('  Writable: ' . (is_writable($settingsDir) ? 'Yes' : 'No'));
        } else {
            $this->warn('  ⚠️  Directory does not exist, creating...');
            try {
                Storage::disk('public')->makeDirectory('settings');
                $this->line('  ✅ Created successfully');
            } catch (\Exception $e) {
                $this->error('  ❌ Failed to create: ' . $e->getMessage());
            }
        }
        $this->newLine();

        // 6. Check PHP upload settings
        $this->info('⚙️ PHP Upload Configuration:');
        $this->line('  upload_max_filesize: ' . ini_get('upload_max_filesize'));
        $this->line('  post_max_size: ' . ini_get('post_max_size'));
        $this->line('  max_file_uploads: ' . ini_get('max_file_uploads'));
        $this->line('  memory_limit: ' . ini_get('memory_limit'));
        $this->newLine();

        // 7. Check Livewire config
        $this->info('🔧 Livewire Upload Config:');
        $this->line('  Disk: ' . (config('livewire.temporary_file_upload.disk') ?? 'default'));
        $this->line('  Directory: ' . (config('livewire.temporary_file_upload.directory') ?? 'livewire-tmp'));
        $this->line('  Max upload time: ' . config('livewire.temporary_file_upload.max_upload_time') . ' minutes');
        $this->newLine();

        // 8. Check CSRF exemptions
        $this->info('🛡️ CSRF Exemptions (bootstrap/app.php):');
        $this->line('  Check bootstrap/app.php for livewire/upload-file exemptions');
        $this->newLine();

        $this->info('✅ Diagnostic complete!');
        $this->newLine();
        $this->comment('If uploads still fail, check:');
        $this->line('  1. Server logs (nginx/apache error logs)');
        $this->line('  2. Laravel logs (storage/logs/laravel.log)');
        $this->line('  3. Browser console for JavaScript errors');
        $this->line('  4. Network tab in browser dev tools for the actual request/response');

        return 0;
    }
}
