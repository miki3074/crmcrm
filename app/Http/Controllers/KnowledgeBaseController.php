<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\KnowledgeFolder;
use App\Services\KnowledgeAccessService;
use App\Services\KnowledgeFolderAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function show(
        Request $request,
        Company $company,
        KnowledgeAccessService $knowledgeAccess,
        KnowledgeFolderAccessService $folderAccess
    ): Response {
        $userId = $request->user()->id;

        /*
         * Проверяем, является ли пользователь владельцем
         * или сотрудником компании и имеет ли право видеть
         * базу знаний.
         */
        abort_unless(
            $knowledgeAccess->canView(
                $userId,
                $company->id
            ),
            403,
            'У вас нет доступа к базе знаний этой компании.'
        );

        /*
         * Глобальная роль пользователя в базе знаний компании.
         */
        $currentUserRole = $knowledgeAccess->getRole(
            $userId,
            $company->id
        );

        /*
         * Сотрудники компании для назначения прав на папку.
         *
         * Владелец компании обычно отсутствует в company_user,
         * но выбирать его не требуется, поскольку он всегда
         * имеет полный доступ.
         */
        $members = DB::table('company_user')
            ->join(
                'users',
                'users.id',
                '=',
                'company_user.user_id'
            )
            ->where(
                'company_user.company_id',
                $company->id
            )
            ->select([
                'users.id',
                'users.name',
                'users.email',
            ])
            ->orderBy('users.name')
            ->get();

        /*
         * Получаем только корневые папки компании.
         *
         * После получения коллекции дополнительно фильтруем
         * папки через сервис доступа. Пользователь увидит
         * только те папки, которые ему разрешены.
         */
        $rootFolders = KnowledgeFolder::query()
            ->where('company_id', $company->id)
            ->whereNull('parent_id')
            ->withCount([
                'children',
                'articles',
                'files',
            ])
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->filter(function (KnowledgeFolder $folder) use (
                $folderAccess,
                $userId
            ) {
                return $folderAccess->canView(
                    $userId,
                    $folder
                );
            })
            ->values()
            ->map(function (KnowledgeFolder $folder) use (
                $folderAccess,
                $userId
            ) {
                $folderRole = $folderAccess->getEffectiveRole(
                    $userId,
                    $folder
                );

                return [
                    'id' => $folder->id,
                    'company_id' => $folder->company_id,
                    'parent_id' => $folder->parent_id,
                    'created_by' => $folder->created_by,
                    'name' => $folder->name,
                    'access_type' => $folder->access_type,
                    'position' => $folder->position,

                    'children_count' =>
                        $folder->children_count,

                    'articles_count' =>
                        $folder->articles_count,

                    'files_count' =>
                        $folder->files_count,

                    'effective_role' => $folderRole,

                    'effective_role_label' => match ($folderRole) {
                        'owner' => 'Владелец',
                        'admin' => 'Администратор',
                        'knowledge_manager' => 'Менеджер',
                        'editor' => 'Редактор',
                        default => 'Просмотр',
                    },

                    'created_at' => optional(
                        $folder->created_at
                    )->toISOString(),

                    'updated_at' => optional(
                        $folder->updated_at
                    )->toISOString(),
                ];
            });

        /*
         * Общие права пользователя на уровне компании.
         */
        $permissions = [
            ...$knowledgeAccess->permissions(
                $userId,
                $company->id
            ),

            'create_root_folder' =>
                $knowledgeAccess->canCreateRootFolder(
                    $userId,
                    $company->id
                ),
        ];

        return Inertia::render('Knowledge/Index', [
            'company' => [
                'id' => $company->id,
                'user_id' => $company->user_id,
                'name' => $company->name,
                'logo' => $company->logo,

                'created_at' => optional(
                    $company->created_at
                )->toISOString(),

                'updated_at' => optional(
                    $company->updated_at
                )->toISOString(),
            ],

            'currentUserRole' => $currentUserRole,

            'permissions' => $permissions,

            'members' => $members,

            'knowledge' => [
                'folders' => $rootFolders,
                'articles' => [],
                'files' => [],
            ],
        ]);
    }
}