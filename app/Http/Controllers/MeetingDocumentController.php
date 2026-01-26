<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingDocumenttwo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MeetingDocumentController extends Controller
{
    // Загрузка файла
    public function store(Request $request, Meeting $meeting)
    {
        // Проверка: пользователь должен быть участником, создателем или ответственным
        $user = Auth::user();
        if (!$meeting->participants->contains($user->id) &&
            $user->id !== $meeting->creator_id &&
            $user->id !== $meeting->responsible_id) {
            abort(403, 'Только участники могут загружать документы.');
        }

        $request->validate([
            'file' => 'required|file|max:10240', // Макс 10 МБ
            'name' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $filename = $request->name ?: $file->getClientOriginalName();

        // Сохраняем в папку meetings/{id}
        $path = $file->storeAs("meetings/{$meeting->id}", $file->hashName(), 'public');

        $meeting->documents()->create([
            'user_id' => $user->id,
            'name' => $filename,
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('success', 'Документ загружен.');
    }

    // Скачивание
    public function download(Meeting $meeting, MeetingDocumenttwo $document)
    {
        // Проверка доступа к совещанию (аналогично store или show)
        // ... (можно вынести в Policy)

        if (!Storage::disk('public')->exists($document->path)) {
            abort(404);
        }

        return Storage::disk('public')->download($document->path, $document->name);
    }

    // Обновление (замена файла или переименование)
    public function update(Request $request, Meeting $meeting, MeetingDocumenttwo $document)
    {
        $user = Auth::user();

        // ПРОВЕРКА ПРАВ: Только тот, кто загрузил
        if ($document->user_id !== $user->id) {
            abort(403, 'Вы можете обновлять только свои документы.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'nullable|file|max:10240', // Файл необязателен, если меняем только имя
        ]);

        $data = ['name' => $request->name];

        // Если прислали новый файл - заменяем старый
        if ($request->hasFile('file')) {
            // Удаляем старый
            if (Storage::disk('public')->exists($document->path)) {
                Storage::disk('public')->delete($document->path);
            }
            // Грузим новый
            $file = $request->file('file');
            $path = $file->storeAs("meetings/{$meeting->id}", $file->hashName(), 'public');

            $data['path'] = $path;
            $data['mime_type'] = $file->getClientMimeType();
            $data['size'] = $file->getSize();
        }

        $document->update($data);

        return back()->with('success', 'Документ обновлен.');
    }

    // Удаление
    public function destroy(Meeting $meeting, MeetingDocumenttwo $document)
    {
        $user = Auth::user();

        // ПРОВЕРКА ПРАВ: Только тот, кто загрузил
        if ($document->user_id !== $user->id) {
            abort(403, 'Вы можете удалять только свои документы.');
        }

        // Удаляем файл с диска
        if (Storage::disk('public')->exists($document->path)) {
            Storage::disk('public')->delete($document->path);
        }

        // Удаляем из БД
        $document->delete();

        return back()->with('success', 'Документ удален.');
    }
}
