<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CompanyAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyKnowledgeController extends Controller
{
    public function show(
        Request $request,
        int $company,
        CompanyAccessService $companyAccess
    ): JsonResponse {
        $userId = $request->user()->id;

        if (!$companyAccess->userHasAccess($userId, $company)) {
            abort(403, 'У вас нет доступа к этой компании.');
        }

        $companyData = DB::table('companies')
            ->where('id', $company)
            ->select([
                'id',
                'user_id',
                'name',
                'logo',
                'created_at',
                'updated_at',
            ])
            ->first();

        if (!$companyData) {
            abort(404, 'Компания не найдена.');
        }

        return response()->json([
            'data' => [
                'company' => $companyData,
                'current_user_role' => $companyAccess->getUserRole(
                    $userId,
                    $company
                ),

                /*
                 * Позже здесь будут:
                 * folders
                 * articles
                 * files
                 * permissions
                 */
                'knowledge' => [
                    'folders' => [],
                    'articles' => [],
                    'files' => [],
                ],
            ],
        ]);
    }
}