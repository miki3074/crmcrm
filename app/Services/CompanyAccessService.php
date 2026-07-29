<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CompanyAccessService
{
    /**
     * Проверяет, имеет ли пользователь доступ к компании.
     */
    public function userHasAccess(int $userId, int $companyId): bool
    {
        $isOwner = DB::table('companies')
            ->where('id', $companyId)
            ->where('user_id', $userId)
            ->exists();

        if ($isOwner) {
            return true;
        }

        return DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Возвращает роль пользователя внутри компании.
     */
    public function getUserRole(int $userId, int $companyId): ?string
    {
        $isOwner = DB::table('companies')
            ->where('id', $companyId)
            ->where('user_id', $userId)
            ->exists();

        if ($isOwner) {
            return 'owner';
        }

        return DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->value('role');
    }
}