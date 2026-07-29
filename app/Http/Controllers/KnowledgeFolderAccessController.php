<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeFolderRole;
use App\Models\User;
use App\Services\KnowledgeFolderAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeFolderAccessController extends Controller
{
    public function index(
        Request $request,
        Company $company,
        KnowledgeFolder $folder,
        KnowledgeFolderAccessService $folderAccess
    ): Response {
        $this->validateFolderCompany(
            $company,
            $folder
        );

        abort_unless(
            $folderAccess->canManageAccess(
                $request->user()->id,
                $folder
            ),
            403
        );

        $folderUsers = KnowledgeFolderRole::query()
            ->where('folder_id', $folder->id)
            ->with('user:id,name,email')
            ->orderBy('role')
            ->get()
            ->map(fn (KnowledgeFolderRole $role) => [
                'id' => $role->user->id,
                'name' => $role->user->name,
                'email' => $role->user->email,
                'role' => $role->role,
            ]);

        $companyUsers = $company->users()
            ->select([
                'users.id',
                'users.name',
                'users.email',
            ])
            ->orderBy('users.name')
            ->get();

        return Inertia::render(
            'Knowledge/Folders/Access',
            [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],

                'folder' => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'access_type' => $folder->access_type,
                ],

                'folderUsers' => $folderUsers,
                'companyUsers' => $companyUsers,

                'roles' => [
                    'viewer',
                    'editor',
                    'knowledge_manager',
                    'admin',
                ],
            ]
        );
    }

public function updateMode(
    Request $request,
    Company $company,
    KnowledgeFolder $folder,
    KnowledgeFolderAccessService $folderAccess
): RedirectResponse {
    abort_unless(
        $folder->company_id === $company->id,
        404
    );

    abort_unless(
        $folderAccess->canManageAccess(
            $request->user()->id,
            $folder
        ),
        403
    );

    $validated = $request->validate([
        'access_type' => [
            'required',
            'string',
            'in:company,private',
        ],
    ]);

    $folder->update([
        'access_type' => $validated['access_type'],
    ]);

    return back()->with(
        'success',
        $validated['access_type'] === 'private'
            ? 'Папка стала ограниченной.'
            : 'Папка доступна всей компании.'
    );
}

    public function storeUser(
        Request $request,
        Company $company,
        KnowledgeFolder $folder,
        KnowledgeFolderAccessService $folderAccess
    ): RedirectResponse {
        $this->validateFolderCompany(
            $company,
            $folder
        );

        abort_unless(
            $folderAccess->canManageAccess(
                $request->user()->id,
                $folder
            ),
            403
        );

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'role' => [
                'required',
                'in:viewer,editor,knowledge_manager,admin',
            ],
        ]);

        abort_unless(
            $company->users()
                ->where('users.id', $validated['user_id'])
                ->exists(),
            422,
            'Пользователь не входит в компанию.'
        );

        KnowledgeFolderRole::query()->updateOrCreate(
            [
                'folder_id' => $folder->id,
                'user_id' => $validated['user_id'],
            ],
            [
                'role' => $validated['role'],
            ]
        );

        return back()->with(
            'success',
            'Пользователь добавлен в папку.'
        );
    }

    public function updateUser(
        Request $request,
        Company $company,
        KnowledgeFolder $folder,
        User $user,
        KnowledgeFolderAccessService $folderAccess
    ): RedirectResponse {
        $this->validateFolderCompany(
            $company,
            $folder
        );

        abort_unless(
            $folderAccess->canManageAccess(
                $request->user()->id,
                $folder
            ),
            403
        );

        $validated = $request->validate([
            'role' => [
                'required',
                'in:viewer,editor,knowledge_manager,admin',
            ],
        ]);

        KnowledgeFolderRole::query()
            ->where('folder_id', $folder->id)
            ->where('user_id', $user->id)
            ->update([
                'role' => $validated['role'],
            ]);

        return back()->with(
            'success',
            'Роль пользователя изменена.'
        );
    }

    public function destroyUser(
        Request $request,
        Company $company,
        KnowledgeFolder $folder,
        User $user,
        KnowledgeFolderAccessService $folderAccess
    ): RedirectResponse {
        $this->validateFolderCompany(
            $company,
            $folder
        );

        abort_unless(
            $folderAccess->canManageAccess(
                $request->user()->id,
                $folder
            ),
            403
        );

        abort_if(
            $folder->created_by === $user->id,
            422,
            'Нельзя удалить создателя папки.'
        );

        KnowledgeFolderRole::query()
            ->where('folder_id', $folder->id)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with(
            'success',
            'Пользователь удалён из папки.'
        );
    }

    private function validateFolderCompany(
        Company $company,
        KnowledgeFolder $folder
    ): void {
        abort_unless(
            $folder->company_id === $company->id,
            404
        );
    }
}