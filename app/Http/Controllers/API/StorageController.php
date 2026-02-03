<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\StorageFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class StorageController extends Controller
{
    // Список компаний для "Хранилище" (пусть владелец видит свои; расширишь по желанию)
   public function companies(Request $request)
{
    $user = $request->user();

    $ownedIds   = \App\Models\Company::where('user_id', $user->id)->pluck('id');
    $managedIds = $user->storageManagedCompanies()->pluck('companies.id'); // если сделал обратную связь

    $visibleByFiles = \App\Models\Company::where(function ($q) use ($user) {
        // company_all и он сотрудник этой компании
        $q->whereHas('storageFiles', function ($q1) use ($user) {
            $q1->where('visibility', 'company_all')
               ->whereColumn('storage_files.company_id', 'companies.id')
               ->where('company_id', $user->company_id);
        })
        // точечный доступ
        ->orWhereHas('storageFiles.allowedUsers', function ($q2) use ($user) {
            $q2->where('users.id', $user->id);
        })
        // он сам загрузчик
        ->orWhereHas('storageFiles', function ($q3) use ($user) {
            $q3->where('uploader_id', $user->id);
        });
    })->pluck('id');

    $ids = $ownedIds->merge($managedIds)->merge($visibleByFiles)->unique()->values();

    $companies = \App\Models\Company::whereIn('id', $ids)
        ->withCount('storageFiles')
        ->orderBy('name')
        ->get();

    return response()->json($companies);
}

    // Детали компании + менеджеры + файлы
    // app/Http/Controllers/API/StorageController.php (замени метод company)
public function company(Request $request, \App\Models\Company $company)
{
    $user = $request->user();

    $isOwner   = $company->user_id === $user->id;
    $isManager = $company->storageManagers()->where('users.id', $user->id)->exists();

    $baseFiles = $company->storageFiles()->with(['uploader:id,name']);

    if ($isOwner || $isManager) {
        $filesQuery = clone $baseFiles; // все файлы
    } else {
        // только доступные ему
        $filesQuery = (clone $baseFiles)->where(function ($q) use ($company, $user) {
            $q->where('uploader_id', $user->id)                              // сам загрузил
              ->orWhere(function ($q2) use ($company, $user) {                // company_all + он сотрудник компании
                  $q2->where('visibility', 'company_all')
                     ->where('company_id', $company->id);
              })
              ->orWhereHas('allowedUsers', function ($q3) use ($user) {       // точечный доступ
                  $q3->where('users.id', $user->id);
              });
        });

        // важный момент: company_all разрешаем только сотрудникам этой компании
        if ($user->company_id !== $company->id) {
            $filesQuery = (clone $baseFiles)->where(function ($q) use ($user) {
                $q->where('uploader_id', $user->id)
                  ->orWhereHas('allowedUsers', function ($q3) use ($user) {
                      $q3->where('users.id', $user->id);
                  });
            });
        }
    }

    $files = $filesQuery->latest()->get();

    if (!$isOwner && !$isManager && $files->isEmpty()) {
        abort(403);
    }

    $company->load(['storageManagers:id,name']);

    return response()->json([
        'company' => $company,
        'files'   => $files,
    ]);
}


    // Назначение менеджеров (только владелец)
    public function saveManagers(Request $request, Company $company)
    {
        $user = $request->user();
        abort_unless($company->user_id === $user->id, 403);

        $data = $request->validate([
            'user_ids'   => 'array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $company->storageManagers()->sync($data['user_ids'] ?? []);
        return response()->json(['ok' => true]);
    }

    // Загрузка файлов
    // Загрузка файлов в компанию
    public function upload(Request $request, Company $company)
    {
        $this->authorize('create', [StorageFile::class, $company]);

        $data = $request->validate([
            'visibility'       => 'required|in:company_all,selected',
            'files.*'          => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:51200', // до 50MB
            'allowed_user_ids' => 'array',
            'allowed_user_ids.*'=> 'integer|exists:users,id',
        ], [
            'files.*.max' => 'Файл не должен превышать 50 МБ',
            'files.*.mimes' => 'Разрешены форматы: pdf, doc, docx, xls, xlsx, ppt, pptx, zip, rar',
        ]);

        $saved = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                // Генерируем уникальное имя для хранения
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('storage_files', $filename, 'public');

                $item = StorageFile::create([
                    'company_id'    => $company->id,
                    'uploader_id'   => $request->user()->id,
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'size'          => $file->getSize(),
                    'visibility'    => $data['visibility'],
                ]);

                // Привязка к выбранным пользователям
                if ($data['visibility'] === 'selected' && !empty($data['allowed_user_ids'])) {
                    $item->allowedUsers()->sync($data['allowed_user_ids']);
                }

                $saved[] = $item->load('uploader:id,name');
            }
        }

        return response()->json(['files' => $saved], 201);
    }


    // Скачать файл
    public function download(Request $request, StorageFile $file)
    {
        $this->authorize('view', $file);
        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    // Удалить файл
    public function destroy(Request $request, StorageFile $file)
    {
        $this->authorize('delete', $file);

        Storage::disk('public')->delete($file->path);
        $file->allowedUsers()->detach();
        $file->delete();

        return response()->json(['ok' => true]);
    }
}
