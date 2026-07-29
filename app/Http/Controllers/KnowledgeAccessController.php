<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\KnowledgeCompanyRole;
use App\Services\KnowledgeAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeAccessController extends Controller
{
    public function index(
        Request $request,
        Company $company,
        KnowledgeAccessService $knowledgeAccess
    ): Response {
        $userId = $request->user()->id;

        if (!$knowledgeAccess->canManageUsers(
            $userId,
            $company->id
        )) {
            abort(
                403,
                'У вас нет права управлять доступом.'
            );
        }

        $employees = DB::table('company_user')
            ->join(
                'users',
                'users.id',
                '=',
                'company_user.user_id'
            )
            ->leftJoin(
                'knowledge_company_roles',
                function ($join) use ($company) {
                    $join
                        ->on(
                            'knowledge_company_roles.user_id',
                            '=',
                            'users.id'
                        )
                        ->where(
                            'knowledge_company_roles.company_id',
                            '=',
                            $company->id
                        );
                }
            )
            ->where(
                'company_user.company_id',
                $company->id
            )
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'knowledge_company_roles.role',
                'knowledge_company_roles.updated_at as role_updated_at',
            ])
            ->orderBy('users.name')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'role' => $employee->role ?? 'viewer',
                    'has_custom_role' => $employee->role !== null,
                    'role_updated_at' => $employee->role_updated_at,
                ];
            })
            ->values();

        return Inertia::render('Knowledge/Access', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo,
            ],

            'employees' => $employees,

            'roles' => [
                [
                    'value' => 'knowledge_manager',
                    'label' => 'Менеджер базы знаний',
                    'description' => 'Может управлять пользователями и контентом.',
                ],
                [
                    'value' => 'editor',
                    'label' => 'Редактор',
                    'description' => 'Может создавать и редактировать материалы.',
                ],
                [
                    'value' => 'viewer',
                    'label' => 'Просмотр',
                    'description' => 'Может только просматривать материалы.',
                ],
            ],
        ]);
    }

    public function update(
        Request $request,
        Company $company,
        int $user,
        KnowledgeAccessService $knowledgeAccess
    ): RedirectResponse {
        $currentUserId = $request->user()->id;

        if (!$knowledgeAccess->canManageUsers(
            $currentUserId,
            $company->id
        )) {
            abort(
                403,
                'У вас нет права управлять доступом.'
            );
        }

        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::in(
                    KnowledgeAccessService::ASSIGNABLE_ROLES
                ),
            ],
        ]);

        $isMember = DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $user)
            ->exists();

        if (!$isMember) {
            abort(
                422,
                'Пользователь не является сотрудником компании.'
            );
        }

        if (
            (int) $company->user_id ===
            (int) $user
        ) {
            abort(
                422,
                'Нельзя изменить роль владельца компании.'
            );
        }

        /*
         * Viewer является ролью по умолчанию.
         * Поэтому запись для viewer можно удалить.
         */
        if ($validated['role'] === 'viewer') {
            KnowledgeCompanyRole::query()
                ->where('company_id', $company->id)
                ->where('user_id', $user)
                ->delete();

            return back()->with(
                'success',
                'Пользователю назначена роль просмотра.'
            );
        }

        KnowledgeCompanyRole::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'user_id' => $user,
            ],
            [
                'role' => $validated['role'],
                'assigned_by' => $currentUserId,
            ]
        );

        return back()->with(
            'success',
            'Роль пользователя обновлена.'
        );
    }

    public function destroy(
        Request $request,
        Company $company,
        int $user,
        KnowledgeAccessService $knowledgeAccess
    ): RedirectResponse {
        $currentUserId = $request->user()->id;

        if (!$knowledgeAccess->canManageUsers(
            $currentUserId,
            $company->id
        )) {
            abort(
                403,
                'У вас нет права управлять доступом.'
            );
        }

        KnowledgeCompanyRole::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user)
            ->delete();

        return back()->with(
            'success',
            'Дополнительная роль снята. Пользователь теперь имеет роль просмотра.'
        );
    }
}