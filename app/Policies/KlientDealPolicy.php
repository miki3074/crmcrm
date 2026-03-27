<?php

namespace App\Policies;

use App\Models\KlientDeal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KlientDealPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the deal.
     */
    public function view(User $user, KlientDeal $deal): bool
    {
        // Проверка на владельца компании, если есть company_id у клиента
        if ($deal->klient && $deal->klient->company_id) {
            $klient = $deal->klient;

            // Загружаем компанию, если еще не загружена
            if (!$klient->relationLoaded('company')) {
                $klient->load('company');
            }

            $isCompanyOwner = $klient->company->user_id === $user->id;
            if ($isCompanyOwner) {
                return true;
            }
        }

        // Проверяем, является ли пользователь создателем сделки
        if ($deal->creator_id === $user->id) {
            return true;
        }

        // Проверяем, есть ли пользователь в таблице ответственных
        return $deal->responsibles()
            ->where('user_id', $user->id)
            ->exists();
    }
}
