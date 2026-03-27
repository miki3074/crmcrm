<?php

namespace App\Http\Controllers;

use App\Models\Klient;
use App\Models\KlientFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class KlientFileController extends Controller
{
    /**
     * Проверка: имеет ли пользователь доступ к клиенту (создатель или через klient_access)
     */
    private function checkKlientAccess(Klient $klient)
    {
        $userId = Auth::id();
        $hasAccess = $klient->user_id === $userId ||
            $klient->allowedUsers()->where('users.id', $userId)->exists();

        if (!$hasAccess) {
            abort(403, 'У вас нет доступа к этому клиенту');
        }
    }

    /**
     * Загрузка файла
     */
    public function store(Request $request, Klient $klient)
    {
        $this->checkKlientAccess($klient);

        $request->validate([
            'file' => 'required|file|max:10240', // макс 10МБ
        ]);

        $file = $request->file('file');
        // Сохраняем в приватную папку storage/app/klient_files
        $path = $file->store('klient_files/' . $klient->id);

        $klient->files()->create([
            'user_id' => Auth::id(),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'Файл успешно загружен');
    }

    /**
     * Скачивание файла
     */
    public function download(KlientFile $file)
    {
        $klient = Klient::findOrFail($file->klient_id);
        $this->checkKlientAccess($klient);

        if (!Storage::exists($file->file_path)) {
            abort(404, 'Файл не найден на сервере');
        }

        return Storage::download($file->file_path, $file->original_name);
    }

    /**
     * Удаление файла
     */
    public function destroy(KlientFile $file)
    {
        // Проверка: удалить может только тот, кто загрузил
        if ($file->user_id !== Auth::id()) {
            abort(403, 'Вы можете удалять только собственные файлы');
        }

        // Удаляем физически
        if (Storage::exists($file->file_path)) {
            Storage::delete($file->file_path);
        }

        // Удаляем запись из БД
        $file->delete();

        return back()->with('success', 'Файл удален');
    }
}
