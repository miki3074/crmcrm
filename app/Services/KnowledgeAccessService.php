<?php

namespace App\Services;

use App\Models\Company;
use App\Models\KnowledgeCompanyRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class KnowledgeAccessService
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_MANAGER = 'knowledge_manager';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_VIEWER = 'viewer';

    public const ASSIGNABLE_ROLES = [
        self::ROLE_MANAGER,
        self::ROLE_EDITOR,
        self::ROLE_VIEWER,
    ];

    /**
     * Проверяет, является ли пользователь владельцем компании.
     */
    public function isOwner(
        int $userId,
        int $companyId
    ): bool {
        return DB::table('companies')
            ->where('id', $companyId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Проверяет, является ли пользователь сотрудником компании.
     */
    public function isCompanyMember(
        int $userId,
        int $companyId
    ): bool {
        if ($this->isOwner($userId, $companyId)) {
            return true;
        }

        return DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Получает роль пользователя в базе знаний.
     *
     * Владелец всегда owner.
     * Если отдельной роли нет — viewer.
     */
    public function getRole(
        int $userId,
        int $companyId
    ): ?string {
        if (!$this->isCompanyMember($userId, $companyId)) {
            return null;
        }

        if ($this->isOwner($userId, $companyId)) {
            return self::ROLE_OWNER;
        }

        return KnowledgeCompanyRole::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->value('role')
            ?? self::ROLE_VIEWER;
    }

    /**
     * Может ли пользователь открыть базу знаний.
     */
    public function canView(
        int $userId,
        int $companyId
    ): bool {
        return $this->isCompanyMember(
            $userId,
            $companyId
        );
    }

    /**
     * Может ли создавать и редактировать контент.
     */
    public function canEdit(
        int $userId,
        int $companyId
    ): bool {
        return in_array(
            $this->getRole($userId, $companyId),
            [
                self::ROLE_OWNER,
                self::ROLE_MANAGER,
                self::ROLE_EDITOR,
            ],
            true
        );
    }

    /**
     * Может ли создавать папки, статьи и загружать файлы.
     */
    public function canCreate(
        int $userId,
        int $companyId
    ): bool {
        return $this->canEdit(
            $userId,
            $companyId
        );
    }

    /**
     * Может ли удалять контент.
     */
    public function canDelete(
        int $userId,
        int $companyId
    ): bool {
        return in_array(
            $this->getRole($userId, $companyId),
            [
                self::ROLE_OWNER,
                self::ROLE_MANAGER,
                self::ROLE_EDITOR,
            ],
            true
        );
    }

    /**
     * Может ли управлять ролями пользователей.
     */
    public function canManageUsers(
        int $userId,
        int $companyId
    ): bool {
        return in_array(
            $this->getRole($userId, $companyId),
            [
                self::ROLE_OWNER,
                self::ROLE_MANAGER,
            ],
            true
        );
    }

    /**
     * Может ли открыть настройки базы знаний.
     *
     * Пока настройки доступны только владельцу.
     */
    public function canManageSettings(
        int $userId,
        int $companyId
    ): bool {
        return $this->isOwner(
            $userId,
            $companyId
        );
    }

    /**
     * Готовый набор разрешений для Vue.
     */
    public function permissions(
        int $userId,
        int $companyId
    ): array {
        return [
            'view' => $this->canView(
                $userId,
                $companyId
            ),

            'create' => $this->canCreate(
                $userId,
                $companyId
            ),

            'edit' => $this->canEdit(
                $userId,
                $companyId
            ),

            'delete' => $this->canDelete(
                $userId,
                $companyId
            ),

            'manage_users' => $this->canManageUsers(
                $userId,
                $companyId
            ),

            'manage_settings' => $this->canManageSettings(
                $userId,
                $companyId
            ),
        ];
    }

    public function canCreateRootFolder(
    int $userId,
    int $companyId
): bool {
    return in_array(
        $this->getRole($userId, $companyId),
        [
            self::ROLE_OWNER,
            self::ROLE_MANAGER,
            self::ROLE_EDITOR,
        ],
        true
    );
}

public function canDeleteFolder(
    int $userId,
    KnowledgeFolder $folder
): bool {
    /*
     * Создатель папки может удалить её всегда,
     * пока он состоит в компании.
     */
    if ((int) $folder->created_by === $userId) {
        return $this->companyAccess->getRole(
            $userId,
            $folder->company_id
        ) !== null;
    }

    $companyRole = $this->companyAccess->getRole(
        $userId,
        $folder->company_id
    );

    /*
     * Удалять также могут:
     * - владелец компании;
     * - менеджер базы знаний компании.
     */
    return in_array(
        $companyRole,
        [
            self::ROLE_OWNER,
            self::ROLE_MANAGER,
        ],
        true
    );
}


}