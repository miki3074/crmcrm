<?php

namespace App\Http\Controllers;
use App\Http\Controllers\KnowledgeFileController;

use App\Models\Company;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeFile;
use App\Models\KnowledgeFolder;
use App\Services\KnowledgeFolderAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KnowledgeFileController extends Controller
{
    public function storeInFolder(
        Request $request,
        Company $company,
        KnowledgeFolder $folder,
        KnowledgeFolderAccessService $folderAccess
    ): RedirectResponse {
        abort_unless(
            (int) $folder->company_id === (int) $company->id,
            404
        );

        abort_unless(
            $folderAccess->canCreateContent(
                $request->user()->id,
                $folder
            ),
            403
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
                'max:51200',
            ],
        ]);

        foreach ($validated['files'] as $uploadedFile) {
            $this->saveFile(
                uploadedFile: $uploadedFile,
                company: $company,
                folder: $folder,
                userId: $request->user()->id,
                article: null
            );
        }

        return back()->with(
            'success',
            'Файлы добавлены в папку.'
        );
    }

    public function storeInArticle(
        Request $request,
        Company $company,
        KnowledgeFolder $folder,
        KnowledgeArticle $article,
        KnowledgeFolderAccessService $folderAccess
    ): RedirectResponse {
        abort_unless(
            (int) $folder->company_id === (int) $company->id,
            404
        );

        abort_unless(
            (int) $article->folder_id === (int) $folder->id,
            404
        );

        abort_unless(
            $folderAccess->canEditContent(
                $request->user()->id,
                $folder
            ),
            403
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
                'max:51200',
            ],
        ]);

        foreach ($validated['files'] as $uploadedFile) {
            $this->saveFile(
                uploadedFile: $uploadedFile,
                company: $company,
                folder: $folder,
                userId: $request->user()->id,
                article: $article
            );
        }

        return back()->with(
            'success',
            'Файлы добавлены к статье.'
        );
    }

    public function download(
        Request $request,
        Company $company,
        KnowledgeFolder $folder,
        KnowledgeFile $file,
        KnowledgeFolderAccessService $folderAccess
    ): StreamedResponse {
        abort_unless(
            (int) $folder->company_id === (int) $company->id,
            404
        );

        abort_unless(
            (int) $file->company_id === (int) $company->id
            && (int) $file->folder_id === (int) $folder->id,
            404
        );

        abort_unless(
            $folderAccess->canView(
                $request->user()->id,
                $folder
            ),
            403
        );

        abort_unless(
            Storage::disk($file->disk)->exists($file->path),
            404
        );

        return Storage::disk($file->disk)->download(
            $file->path,
            $file->original_name
        );
    }

    public function destroy(
        Request $request,
        Company $company,
        KnowledgeFolder $folder,
        KnowledgeFile $file,
        KnowledgeFolderAccessService $folderAccess
    ): RedirectResponse {
        abort_unless(
            (int) $folder->company_id === (int) $company->id,
            404
        );

        abort_unless(
            (int) $file->company_id === (int) $company->id
            && (int) $file->folder_id === (int) $folder->id,
            404
        );

        $canDelete =
            (int) $file->uploaded_by === (int) $request->user()->id
            || $folderAccess->canDeleteContent(
                $request->user()->id,
                $folder
            );

        abort_unless($canDelete, 403);

        DB::transaction(function () use ($file) {
            $disk = $file->disk;
            $path = $file->path;

            $file->delete();

            DB::afterCommit(function () use ($disk, $path) {
                Storage::disk($disk)->delete($path);
            });
        });

        return back()->with(
            'success',
            'Файл удалён.'
        );
    }

    private function saveFile(
        mixed $uploadedFile,
        Company $company,
        KnowledgeFolder $folder,
        int $userId,
        ?KnowledgeArticle $article
    ): KnowledgeFile {
        $originalName = $uploadedFile->getClientOriginalName();
        $extension = strtolower(
            $uploadedFile->getClientOriginalExtension()
        );

        $storedName = Str::uuid()->toString()
            . ($extension !== '' ? ".{$extension}" : '');

        $directory = implode('/', [
            'knowledge',
            $company->id,
            'folders',
            $folder->id,
        ]);

        $path = $uploadedFile->storeAs(
            $directory,
            $storedName,
            'local'
        );

        try {
            return KnowledgeFile::query()->create([
                'company_id' => $company->id,
                'folder_id' => $folder->id,
                'article_id' => $article?->id,
                'uploaded_by' => $userId,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'mime_type' => $uploadedFile->getMimeType(),
                'extension' => $extension ?: null,
                'size' => $uploadedFile->getSize() ?: 0,
                'category' => $this->detectCategory(
                    $uploadedFile->getMimeType()
                ),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }

    private function detectCategory(?string $mimeType): string
    {
        if (!$mimeType) {
            return 'other';
        }

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        if (
            str_contains($mimeType, 'zip')
            || str_contains($mimeType, 'rar')
            || str_contains($mimeType, '7z')
            || str_contains($mimeType, 'tar')
        ) {
            return 'archive';
        }

        if (
            str_contains($mimeType, 'pdf')
            || str_contains($mimeType, 'word')
            || str_contains($mimeType, 'excel')
            || str_contains($mimeType, 'spreadsheet')
            || str_contains($mimeType, 'presentation')
            || str_starts_with($mimeType, 'text/')
        ) {
            return 'document';
        }

        return 'other';
    }


public function preview(
    Request $request,
    Company $company,
    KnowledgeFolder $folder,
    KnowledgeFile $file,
    KnowledgeFolderAccessService $folderAccess
): StreamedResponse {
    abort_unless(
        (int) $folder->company_id === (int) $company->id,
        404
    );

    abort_unless(
        (int) $file->company_id === (int) $company->id
        && (int) $file->folder_id === (int) $folder->id,
        404
    );

    abort_unless(
        $folderAccess->canView(
            $request->user()->id,
            $folder
        ),
        403
    );

    $disk = Storage::disk($file->disk);

    abort_unless(
        $disk->exists($file->path),
        404
    );

    return $disk->response(
        $file->path,
        $file->original_name,
        [
            'Content-Type' => $file->mime_type
                ?: 'application/octet-stream',

            'Content-Disposition' => sprintf(
                'inline; filename="%s"',
                str_replace('"', '', $file->original_name)
            ),

            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]
    );
}



}