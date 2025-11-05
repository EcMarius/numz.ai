<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileUploadController extends Controller
{
    /**
     * Handle Livewire file upload WITHOUT signature validation
     * This bypasses the broken signed URL system
     */
    public function handle(Request $request)
    {
        // Validate files
        $validator = Validator::make(['files' => $request->file('files')], [
            'files.*' => ['required', 'file', 'max:12288'], // 12MB default
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $disk = config('livewire.temporary_file_upload.disk', 'public');
        $directory = config('livewire.temporary_file_upload.directory', 'livewire-tmp');

        $filePaths = collect($request->file('files'))->map(function ($file) use ($disk, $directory) {
            // Generate filename like Livewire does
            $filename = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($file);

            // Store the file
            $path = $file->storeAs($directory, $filename, ['disk' => $disk]);

            // Return just the filename (not full path)
            return str_replace($directory . '/', '', $path);
        });

        return response()->json(['paths' => $filePaths->toArray()]);
    }
}
