<?php

namespace App\Http\Controllers;

use App\Models\TaskFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskFileController extends Controller
{
    public function show(Request $request, TaskFile $file)
    {
        // При необходимости добавьте проверку доступа.
        // $this->authorize('view', $file);

        $disk = Storage::disk('public');

        abort_unless(
            $disk->exists($file->file_path),
            404,
            'Файл не найден'
        );

        $fileName = $file->file_name
            ?: basename($file->file_path);

        $mimeType = $disk->mimeType($file->file_path)
            ?: 'application/octet-stream';

        if ($request->boolean('download')) {
            return $disk->download(
                $file->file_path,
                $fileName,
                [
                    'Content-Type' => $mimeType
                ]
            );
        }

        return response()->file(
            $disk->path($file->file_path),
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' =>
                    'inline; filename="' . $fileName . '"',
                'X-Content-Type-Options' => 'nosniff'
            ]
        );
    }
}
