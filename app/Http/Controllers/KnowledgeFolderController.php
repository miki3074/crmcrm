<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeFolderRole;
use App\Services\KnowledgeAccessService;
use App\Services\KnowledgeFolderAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\KnowledgeFile;

class KnowledgeFolderController extends Controller
{
    public function store(
        Request $request,
        Company $company,
        KnowledgeAccessService $knowledgeAccess,
        KnowledgeFolderAccessService $folderAccess
    ): RedirectResponse {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'knowledge_folders',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'company_id',
                            $company->id
                        )
                ),
            ],

            'access_type' => [
                'required',
                Rule::in([
                    'private',
                    'company',
                ]),
            ],

            'users' => [
                'nullable',
                'array',
            ],

            'users.*.user_id' => [
                'required',
                'integer',
                'distinct',
            ],

            'users.*.role' => [
                'required',
                Rule::in(
                    KnowledgeFolderAccessService::ASSIGNABLE_ROLES
                ),
            ],
        ]);

        $parent = null;

        if (!empty($validated['parent_id'])) {
            $parent = KnowledgeFolder::query()
                ->where('company_id', $company->id)
                ->findOrFail($validated['parent_id']);

            if (!$folderAccess->canCreateContent(
                $userId,
                $parent
            )) {
                abort(
                    403,
                    'У вас нет права создавать папки здесь.'
                );
            }
        } elseif (
            !$knowledgeAccess->canCreateRootFolder(
                $userId,
                $company->id
            )
        ) {
            abort(
                403,
                'У вас нет права создавать корневые папки.'
            );
        }

        $selectedUsers = collect(
            $validated['users'] ?? []
        );

        /*
         * Проверяем, что выбранные пользователи действительно
         * состоят в компании.
         */
        $selectedUserIds = $selectedUsers
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($selectedUserIds->isNotEmpty()) {
            $validCount = DB::table('company_user')
                ->where('company_id', $company->id)
                ->whereIn('user_id', $selectedUserIds)
                ->count();

            if ($validCount !== $selectedUserIds->count()) {
                return back()->withErrors([
                    'users' => 'Один из пользователей не состоит в компании.',
                ]);
            }
        }

        DB::transaction(function () use (
            $validated,
            $company,
            $userId,
            $selectedUsers
        ) {
            $folder = KnowledgeFolder::query()->create([
                'company_id' => $company->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'created_by' => $userId,
                'name' => trim($validated['name']),
                'access_type' => $validated['access_type'],
            ]);

            /*
             * Создатель всегда admin.
             */
            KnowledgeFolderRole::query()->create([
                'folder_id' => $folder->id,
                'user_id' => $userId,
                'role' => 'admin',
                'assigned_by' => $userId,
            ]);

            /*
             * Если выбран режим company,
             * отдельные viewer-записи не нужны.
             */
            if ($validated['access_type'] === 'private') {
                foreach ($selectedUsers as $selectedUser) {
                    if (
                        (int) $selectedUser['user_id'] ===
                        (int) $userId
                    ) {
                        continue;
                    }

                    KnowledgeFolderRole::query()
                        ->updateOrCreate(
                            [
                                'folder_id' => $folder->id,
                                'user_id' =>
                                    $selectedUser['user_id'],
                            ],
                            [
                                'role' => $selectedUser['role'],
                                'assigned_by' => $userId,
                            ]
                        );
                }
            }
        });

        return back()->with(
            'success',
            'Папка создана.'
        );
    }


     public function show(
        Request $request,
        Company $company,
        KnowledgeFolder $folder,
        KnowledgeFolderAccessService $folderAccess,
    ): Response {
        abort_unless($folder->company_id === $company->id, 404);

        $userId = $request->user()->id;

        abort_unless(
            $folderAccess->canView($userId, $folder),
            403,
            'У вас нет доступа к этой папке.',
        );

        $children = $folder->children()
            ->withCount(['children', 'articles', 'files'])
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->filter(fn (KnowledgeFolder $child) =>
                $folderAccess->canView($userId, $child)
            )
            ->values()
            ->map(fn (KnowledgeFolder $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'access_type' => $child->access_type,
                'children_count' => $child->children_count,
                'articles_count' => $child->articles_count,
                'files_count' => $child->files_count,
            ]);

        $breadcrumbs = $this->breadcrumbs($folder);

        $members = DB::table('company_user')
            ->join('users', 'users.id', '=', 'company_user.user_id')
            ->where('company_user.company_id', $company->id)
            ->select(['users.id', 'users.name', 'users.email'])
            ->orderBy('users.name')
            ->get();

        $effectiveRole = $folderAccess->getEffectiveRole($userId, $folder);

        return Inertia::render('Knowledge/Folder', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo,
            ],

            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
                'access_type' => $folder->access_type,
                'effective_role' => $effectiveRole,
                'effective_role_label' => $this->roleLabel($effectiveRole),
            ],

            'breadcrumbs' => $breadcrumbs,
            'children' => $children,

            'articles' => $folder->articles()
                ->where('status', 'published')
                ->orderBy('position')
                ->orderBy('title')
                ->get(['id', 'title', 'content_text', 'updated_at']),

            'files' => $folder->files()
    ->whereNull('article_id')
    ->latest()
    ->get([
        'id',
        'company_id',
        'folder_id',
        'uploaded_by',
        'original_name',
        'mime_type',
        'extension',
        'category',
        'size',
        'created_at',
    ])
    ->map(fn ($file) => [
        'id' => $file->id,
        'name' => $file->original_name,
        'original_name' => $file->original_name,
        'mime_type' => $file->mime_type,
        'extension' => $file->extension,
        'category' => $file->category,
        'size' => $file->size,
        'uploaded_by' => $file->uploaded_by,
        'created_at' => $file->created_at?->toISOString(),

        'download_url' => route(
            'knowledge.files.download',
            [
                'company' => $company->id,
                'folder' => $folder->id,
                'file' => $file->id,
            ]
        ),

        'preview_url' => route(
    'knowledge.files.preview',
    [
        'company' => $company->id,
        'folder' => $folder->id,
        'file' => $file->id,
    ]
),

        'delete_url' => route(
            'knowledge.files.destroy',
            [
                'company' => $company->id,
                'folder' => $folder->id,
                'file' => $file->id,
            ]
        ),

        'can_delete' =>
            (int) $file->uploaded_by === (int) $userId
            || $folderAccess->canDeleteContent(
                $userId,
                $folder
            ),
    ]),

            'members' => $members,

            'permissions' => [
                'view' => true,
                'create_content' => $folderAccess->canCreateContent($userId, $folder),
                'manage_access' => $folderAccess->canManageAccess($userId, $folder),
                'delete_folder' => $folderAccess->canDeleteFolder($userId, $folder),
            ],
        ]);
    }

    private function breadcrumbs(KnowledgeFolder $folder): array
    {
        $breadcrumbs = [];
        $current = $folder;

        while ($current !== null) {
            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->name,
            ]);

            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    private function roleLabel(?string $role): string
    {
        return match ($role) {
            'owner' => 'Владелец',
            'admin' => 'Администратор папки',
            'knowledge_manager' => 'Менеджер базы знаний',
            'editor' => 'Редактор',
            default => 'Просмотр',
        };
    }

public function destroy(
    Request $request,
    Company $company,
    KnowledgeFolder $folder,
    KnowledgeFolderAccessService $folderAccess
): RedirectResponse {
    abort_unless(
        (int) $folder->company_id === (int) $company->id,
        404
    );

    $userId = $request->user()->id;

    abort_unless(
        $folderAccess->canDeleteFolder(
            $userId,
            $folder
        ),
        403,
        'У вас нет права удалять эту папку.'
    );

    /*
     * После удаления вложенной папки возвращаемся
     * в её родительскую папку.
     *
     * После удаления корневой папки возвращаемся
     * на главную страницу базы знаний.
     */
    $parentId = $folder->parent_id;
    $folderName = $folder->name;

    DB::transaction(function () use ($folder) {
        $folder->delete();
    });

    if ($parentId !== null) {
        return redirect()
            ->route('knowledge.folders.show', [
                'company' => $company->id,
                'folder' => $parentId,
            ])
            ->with(
                'success',
                "Папка «{$folderName}» удалена."
            );
    }

    return redirect()
        ->to("/companies/{$company->id}/knowledge")
        ->with(
            'success',
            "Папка «{$folderName}» удалена."
        );
}


private function deleteFolderTree(
    KnowledgeFolder $folder
): void {
    $folder->load([
        'children',
        'files',
        'articles.files',
    ]);

    foreach ($folder->children as $child) {
        $this->deleteFolderTree($child);
    }

    /*
     * Файлы, прикреплённые непосредственно к папке.
     */
    foreach ($folder->files as $file) {
        if ($file->path) {
            Storage::disk($file->disk ?? 'local')
                ->delete($file->path);
        }

        $file->delete();
    }

    /*
     * Файлы, прикреплённые к статьям папки.
     */
    foreach ($folder->articles as $article) {
        foreach ($article->files as $file) {
            if ($file->path) {
                Storage::disk($file->disk ?? 'local')
                    ->delete($file->path);
            }

            $file->delete();
        }

        $article->delete();
    }

    $folder->delete();
}



}