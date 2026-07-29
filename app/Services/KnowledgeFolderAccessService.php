<?php

namespace App\Services;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeFolderRole;
use Illuminate\Support\Facades\DB;

class KnowledgeFolderAccessService
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'knowledge_manager';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_VIEWER = 'viewer';
    public const ROLE_OWNER = 'owner';

    public const ASSIGNABLE_ROLES = [
        self::ROLE_MANAGER,
        self::ROLE_EDITOR,
        self::ROLE_VIEWER,
    ];

    private const WEIGHTS = [
        self::ROLE_VIEWER => 10,
        self::ROLE_EDITOR => 20,
        self::ROLE_MANAGER => 30,
        self::ROLE_ADMIN => 40,
        'owner' => 50,
    ];

    public function __construct(
        private KnowledgeAccessService $companyAccess
    ) {
    }

public function getEffectiveRole(
    int $userId,
    KnowledgeFolder $folder
): ?string {
    $companyRole = $this->companyAccess->getRole(
        $userId,
        $folder->company_id
    );

    /*
     * Пользователь не состоит в компании.
     */
    if ($companyRole === null) {
        return null;
    }

    /*
     * Владелец компании всегда имеет полный доступ.
     */
    if ($companyRole === self::ROLE_OWNER) {
        return self::ROLE_OWNER;
    }

    /*
     * Сначала проверяем роль непосредственно
     * в текущей папке.
     */
    $directFolderRole = KnowledgeFolderRole::query()
        ->where('folder_id', $folder->id)
        ->where('user_id', $userId)
        ->value('role');

    if ($directFolderRole !== null) {
        return $directFolderRole;
    }

    /*
     * Для private-папки обязательна явная роль
     * именно в текущей папке.
     *
     * Роли родителей и роль компании
     * здесь не наследуются.
     */
    if ($folder->access_type === 'private') {
        return null;
    }

    /*
     * Для общей папки можно искать роль
     * в родительских папках.
     */
    $parent = $folder->parent_id
        ? KnowledgeFolder::query()
            ->select([
                'id',
                'company_id',
                'parent_id',
                'access_type',
            ])
            ->find($folder->parent_id)
        : null;

    while ($parent !== null) {
        $parentRole = KnowledgeFolderRole::query()
            ->where('folder_id', $parent->id)
            ->where('user_id', $userId)
            ->value('role');

        if ($parentRole !== null) {
            return $parentRole;
        }

        /*
         * Приватный родитель останавливает наследование.
         */
        if ($parent->access_type === 'private') {
            return null;
        }

        $parent = $parent->parent_id
            ? KnowledgeFolder::query()
                ->select([
                    'id',
                    'company_id',
                    'parent_id',
                    'access_type',
                ])
                ->find($parent->parent_id)
            : null;
    }

    /*
     * Для общей папки используется роль компании.
     */
    if ($folder->access_type === 'company') {
        return $this->normalizeCompanyRole($companyRole);
    }

    return null;
}

    public function canView(
        int $userId,
        KnowledgeFolder $folder
    ): bool {
        return $this->getEffectiveRole(
            $userId,
            $folder
        ) !== null;
    }

    public function canCreateContent(
        int $userId,
        KnowledgeFolder $folder
    ): bool {
        return $this->hasMinimumRole(
            $userId,
            $folder,
            self::ROLE_EDITOR
        );
    }

    public function canEditContent(
        int $userId,
        KnowledgeFolder $folder
    ): bool {
        return $this->hasMinimumRole(
            $userId,
            $folder,
            self::ROLE_EDITOR
        );
    }

    public function canManageAccess(
        int $userId,
        KnowledgeFolder $folder
    ): bool {
        return $this->hasMinimumRole(
            $userId,
            $folder,
            self::ROLE_MANAGER
        );
    }

    public function canDeleteFolder(
        int $userId,
        KnowledgeFolder $folder
    ): bool {
        return $this->hasMinimumRole(
            $userId,
            $folder,
            self::ROLE_ADMIN
        );
    }

    private function hasMinimumRole(
        int $userId,
        KnowledgeFolder $folder,
        string $minimumRole
    ): bool {
        $role = $this->getEffectiveRole(
            $userId,
            $folder
        );

        if (!$role) {
            return false;
        }

        return (
            self::WEIGHTS[$role] ?? 0
        ) >= (
            self::WEIGHTS[$minimumRole] ?? PHP_INT_MAX
        );
    }

    /**
     * Возвращает папку, родителя, родителя родителя и т.д.
     */
    private function getFolderAndAncestors(
        KnowledgeFolder $folder
    ): array {
        $folders = [];
        $current = $folder;

        /*
         * Предохранитель от повреждённого циклического дерева.
         */
        $visited = [];

        while ($current) {
            if (isset($visited[$current->id])) {
                break;
            }

            $visited[$current->id] = true;
            $folders[] = $current;

            if (!$current->parent_id) {
                break;
            }

            $current = KnowledgeFolder::query()
                ->select([
                    'id',
                    'company_id',
                    'parent_id',
                    'access_type',
                ])
                ->find($current->parent_id);
        }

        return $folders;
    }

public function canDeleteContent(
    int $userId,
    KnowledgeFolder $folder
): bool {
    return $this->hasMinimumRole(
        $userId,
        $folder,
        self::ROLE_MANAGER
    );
}


private function normalizeCompanyRole(
    string $companyRole
): ?string {
    return match ($companyRole) {
        self::ROLE_ADMIN => self::ROLE_ADMIN,
        self::ROLE_MANAGER => self::ROLE_MANAGER,
        self::ROLE_EDITOR => self::ROLE_EDITOR,
        self::ROLE_VIEWER => self::ROLE_VIEWER,
        self::ROLE_OWNER => self::ROLE_OWNER,
        default => null,
    };
}

}