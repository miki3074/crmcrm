<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Company;
use App\Models\Producer;
use App\Models\Task;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class ProducerBuyerController extends Controller
{
// 🔍 ВСЕ производители
    public function producers(Request $request)
    {
        $user = $request->user();

        // все компании, к которым он имеет отношение (владелец или участник)
        $accessibleCompanyIds = Company::query()
            ->where('user_id', $user->id) // мои компании
            ->orWhereIn('id', function ($q) use ($user) {
                $q->select('company_id')
                    ->from('company_user')
                    ->where('user_id', $user->id);
            })
            ->pluck('id');

        return Producer::with('company:id,name')
            ->where(function ($q) use ($user, $accessibleCompanyIds) {
                // либо сам создал
                $q->where('created_by', $user->id)
                    // либо производитель принадлежит компании, где я владелец/участник
                    ->orWhereIn('company_id', $accessibleCompanyIds);
            })
            ->when($request->search, fn($q) =>
            $q->where('name', 'like', "%{$request->search}%")
            )
            ->when($request->company_id, fn($q) =>
            $q->where('company_id', $request->company_id)
            )
            ->orderBy('name')
            ->get();
    }

// 🔍 ВСЕ покупатели
    public function buyers(Request $request)
    {
        $user = $request->user();

        $accessibleCompanyIds = Company::query()
            ->where('user_id', $user->id)
            ->orWhereIn('id', function ($q) use ($user) {
                $q->select('company_id')
                    ->from('company_user')
                    ->where('user_id', $user->id);
            })
            ->pluck('id');

        return Buyer::with('company:id,name')
            ->where(function ($q) use ($user, $accessibleCompanyIds) {
                $q->where('created_by', $user->id)
                    ->orWhereIn('company_id', $accessibleCompanyIds);
            })
            ->when($request->search, fn($q) =>
            $q->where('name', 'like', "%{$request->search}%")
            )
            ->when($request->company_id, fn($q) =>
            $q->where('company_id', $request->company_id)
            )
            ->orderBy('name')
            ->get();
    }



    // ➕ Создать производителя
    public function storeProducer(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
        ]);

        $data['created_by'] = auth()->id();

        return Producer::create($data);
    }

    // ➕ Создать покупателя
    public function storeBuyer(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
        ]);

        $data['created_by'] = auth()->id();

        return Buyer::create($data);
    }

    // ✏ Редактировать производителя
    public function updateProducer(Request $request, Producer $producer)
    {
        abort_unless($producer->created_by === auth()->id(), 403);
        $producer->update($request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
        ]));
        return $producer;
    }

    // ✏ Редактировать покупателя
    public function updateBuyer(Request $request, Buyer $buyer)
    {
        abort_unless($buyer->created_by === auth()->id(), 403);

        $buyer->update($request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
        ]));
        return $buyer;
    }

    // 🗑 Удалить производителя
    public function deleteProducer(Producer $producer)
    {
        abort_unless($producer->created_by === auth()->id(), 403);
        $producer->delete();
        return response()->json(['success'=>true]);
    }

    // 🗑 Удалить покупателя
    public function deleteBuyer(Buyer $buyer)
    {
        abort_unless($buyer->created_by === auth()->id(), 403);
        $buyer->delete();
        return response()->json(['success'=>true]);
    }

    // 🔗 ПРИВЯЗАТЬ производителя к задаче
    public function attachProducerToTask(Task $task, Producer $producer)
    {
        $task->producers()->syncWithoutDetaching([$producer->id]);
        return $task->load('producers');
    }

    // 🔗 ПРИВЯЗАТЬ покупателя к задаче
    public function attachBuyerToTask(Task $task, Buyer $buyer)
    {
        $task->buyers()->syncWithoutDetaching([$buyer->id]);
        return $task->load('buyers');
    }

    public function producersByCompany(Request $request, Company $company)
    {
        $user = $request->user();

        // Проверяем доступ пользователя к компании
        $hasAccess =
            $company->user_id === $user->id ||
            DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->exists();

        abort_unless($hasAccess, 403);

        return Producer::where('company_id', $company->id)
            ->orderBy('name')
            ->get();
    }

    public function buyersByCompany(Request $request, Company $company)
    {
        $user = $request->user();

        $hasAccess =
            $company->user_id === $user->id ||
            DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->exists();

        abort_unless($hasAccess, 403);

        return Buyer::where('company_id', $company->id)
            ->orderBy('name')
            ->get();
    }


}

