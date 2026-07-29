<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeFile;
use App\Services\KnowledgeFolderAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Throwable;

class KnowledgeArticleFileController extends Controller
{
    public function store(
        Request $request,
        Company $company,
        KnowledgeArticle $article,
        KnowledgeFolderAccessService $folderAccess
    ): RedirectResponse {
        abort_unless(
            $article->company_id === $company->id,
            404
        );

        $article->loadMissing('folder');

        abort_unless(
            $article->folder !== null,
            404,
            'Папка статьи не найдена.'
        );

        abort_unless(
            $folderAccess->canEditContent(
                $request->user()->id,
                $article->folder
            ),
            403,
            'У вас нет права загружать файлы в эту статью.'
        );

        $validated = $request->validate([
    'files' => [
        'required',
        'array',
        'min:1',
        'max:10',
    ],

    'files.*' => [
        'required',
        'file',
        'max:102400',

        'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,rtf,odt,ods,odp,jpg,jpeg,png,webp,gif,svg,mp3,wav,ogg,m4a,aac,flac,mp4,mov,avi,mkv,webm,m4v,zip,rar,7z',
    ],
], [
    'files.required' => 'Выберите хотя бы один файл.',
    'files.array' => 'Некорректный список файлов.',
    'files.max' => 'За один раз можно загрузить не больше 10 файлов.',

    'files.*.required' => 'Не удалось прочитать выбранный файл.',
    'files.*.file' => 'Загружаемый объект должен быть файлом.',
    'files.*.max' => 'Размер одного файла не должен превышать 100 МБ.',
    'files.*.extensions' => 'Файл имеет неподдерживаемое расширение.',
]);

        $storedPaths = [];

        try {
            DB::transaction(function () use (
                $validated,
                $company,
                $article,
                $request,
                &$storedPaths
            ) {
                foreach ($validated['files'] as $uploadedFile) {
                    $extension = strtolower(
                        $uploadedFile->getClientOriginalExtension()
                    );

                    $storedName = Str::uuid()->toString()
                        . ($extension ? ".{$extension}" : '');

                    $directory = sprintf(
                        'knowledge/companies/%d/articles/%d',
                        $company->id,
                        $article->id
                    );

                    $path = $uploadedFile->storeAs(
                        $directory,
                        $storedName,
                        'local'
                    );

                    $storedPaths[] = $path;

                    KnowledgeFile::query()->create([
                        'company_id' => $company->id,
                        'article_id' => $article->id,
                        'folder_id' => $article->folder_id,
                        'uploaded_by' => $request->user()->id,
                        'original_name' => $uploadedFile
                            ->getClientOriginalName(),
                        'stored_name' => $storedName,
                        'path' => $path,
                        'disk' => 'local',
                        'mime_type' => $uploadedFile->getMimeType(),
                        'extension' => $extension ?: null,
                        'size' => $uploadedFile->getSize(),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            report($exception);

            return back()->withErrors([
                'files' => 'Не удалось сохранить файлы.',
            ]);
        }

        return back()->with(
            'success',
            count($validated['files']) === 1
                ? 'Файл успешно загружен.'
                : 'Файлы успешно загружены.'
        );
    }


public function destroy(
    Request $request,
    Company $company,
    KnowledgeArticle $article,
    KnowledgeFile $file,
    KnowledgeFolderAccessService $folderAccess
): RedirectResponse {
    abort_unless(
        $article->company_id === $company->id,
        404
    );

    abort_unless(
        $file->article_id === $article->id,
        404
    );

    $article->loadMissing('folder');

    abort_unless(
        $folderAccess->canEditContent(
            $request->user()->id,
            $article->folder
        ),
        403,
        'У вас нет права удалять файлы.'
    );

    DB::transaction(function () use ($file) {

        if ($file->path) {
            Storage::disk($file->disk ?? 'local')
                ->delete($file->path);
        }

        $file->delete();
    });

    return back()->with(
        'success',
        'Файл удалён.'
    );
}


}