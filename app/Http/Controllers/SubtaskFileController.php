<?php

namespace App\Http\Controllers;

use App\Models\SubtaskFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubtaskFileController extends Controller
{
    public function preview(
        Request $request,
        SubtaskFile $subtaskFile
    ) {
        /*
         * Используйте вашу существующую проверку доступа.
         *
         * Например:
         *
         * $this->authorize('view', $subtaskFile);
         */

        $disk = Storage::disk('public');

        abort_unless(
            $disk->exists($subtaskFile->path),
            404,
            'Файл не найден.'
        );

        $filename = $subtaskFile->filename
            ?: basename($subtaskFile->path);

        $mimeType = $disk->mimeType($subtaskFile->path)
            ?: 'application/octet-stream';

        return $disk->response(
            $subtaskFile->path,
            $filename,
            [
                'Content-Type' => $mimeType,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=3600',
            ],
            'inline'
        );
    }

    public function download(
        Request $request,
        SubtaskFile $subtaskFile
    ) {
        /*
         * Используйте вашу существующую проверку доступа.
         *
         * $this->authorize('view', $subtaskFile);
         */

        $disk = Storage::disk('public');

        abort_unless(
            $disk->exists($subtaskFile->path),
            404,
            'Файл не найден.'
        );

        return $disk->download(
            $subtaskFile->path,
            $subtaskFile->filename
                ?: basename($subtaskFile->path)
        );
    }
}