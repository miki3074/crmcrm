<?php

namespace App\Http\Controllers;

use App\Models\TaskFile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function viewFile($id)
    {
        $file = TaskFile::findOrFail($id);

        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'Файл не найден');
        }

        $fileName = $file->file_name ?? basename($file->file_path);

        // 🔥 Определяем тип файла и выбираем способ просмотра
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Форматы, которые можно открыть напрямую в iframe
        $directViewFormats = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'txt'];

        // Форматы, которые можно открыть через Яндекс Документы
        $yandexFormats = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf'];

        if (in_array($ext, $directViewFormats)) {
            // Прямой просмотр
            $fileUrl = route('file.stream', ['id' => $id]);
            $viewType = 'direct';
        } elseif (in_array($ext, $yandexFormats)) {
            // Через Яндекс Документы
            $fileUrl = route('file.stream', ['id' => $id]);
            $viewType = 'yandex';
        } else {
            // Неподдерживаемый формат
            $fileUrl = route('file.stream', ['id' => $id]);
            $viewType = 'unsupported';
        }

        return view('file-viewer', [
            'fileName' => $fileName,
            'fileUrl' => $fileUrl,
            'fileId' => $id,
            'viewType' => $viewType,
            'fileExt' => $ext
        ]);
    }

    /**
     * Отдача файла для просмотра
     */
    public function streamFile($id)
    {
        $file = TaskFile::findOrFail($id);

        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'Файл не найден');
        }

        $filePath = Storage::disk('public')->path($file->file_path);
        $fileName = $file->file_name ?? basename($file->file_path);
        $mimeType = Storage::disk('public')->mimeType($file->file_path) ?: 'application/octet-stream';

        // Отдаем файл для просмотра (inline)
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
