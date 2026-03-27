<?php

namespace App\Policies;

use App\Models\Klient;
use App\Models\User;

class KlientPolicy
{
    public function view(User $user, Klient $klient): bool
    {
        // 1. Если пользователь — создатель клиента
        if ($klient->user_id === $user->id) {
            return true;
        }

        if ($klient->allowedUsers()->where('users.id', $user->id)->exists()) {
            return true;
        }

        // 3. Доступ через компанию (если пользователь владелец компании)
        if ($klient->company_id) {
            $isCompanyOwner = $klient->company->user_id === $user->id;
            if ($isCompanyOwner) return true;
        }

        // 2. Доступ через компанию
//        if ($klient->company_id) {
//            $isCompanyMember = $klient->company->user_id === $user->id ||
//                $klient->company->users()->where('users.id', $user->id)->exists();
//            if ($isCompanyMember) return true;
//        }
//
//        // 3. Доступ через проект
//        if ($klient->project_id) {
//            $isProjectMember = $klient->project->initiator_id === $user->id ||
//                $klient->project->executors()->where('users.id', $user->id)->exists() ||
//                $klient->project->project_users()->where('users.id', $user->id)->exists();
//            if ($isProjectMember) return true;
//        }
//
//        // 4. Доступ через задачу
//        if ($klient->task_id) {
//            $isTaskMember = $klient->task->creator_id === $user->id ||
//                $klient->task->executors()->where('users.id', $user->id)->exists() ||
//                $klient->task->responsibles()->where('users.id', $user->id)->exists();
//            if ($isTaskMember) return true;
//        }

        return false;
    }

    public function viewDeal(User $user, Klient $klient, $deal = null)
    {
        // Базовый доступ к клиенту
        if (!$this->view($user, $klient)) {
            return false;
        }

        // Если сделка передана, проверяем доступ к конкретной сделке
        if ($deal) {
            // Владелец компании имеет доступ ко всем сделкам
            if ($klient->company_id && $klient->company->user_id === $user->id) {
                return true;
            }

            // Проверяем, является ли пользователь создателем или ответственным в сделке
            if ($deal->creator_id === $user->id) {
                return true;
            }

            // Проверяем, есть ли пользователь в списке ответственных
            if ($deal->responsibles()->where('users.id', $user->id)->exists()) {
                return true;
            }

            return false;
        }

        return true;
    }

    // Аналогично можно добавить метод update, чтобы редактировать мог только создатель
}
