<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use App\Models\ContractFile;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        // Можно сделать только свои: ->where('created_by', auth()->id())
        $contracts = Contract::with('creator:id,name', 'files')
            ->where('created_by', auth()->id())   // ← фильтруем по владельцу
            ->orderByDesc('created_at')
            ->get();

        return response()->json($contracts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'counterparty' => 'nullable|string|max:255',
            'amount'       => 'nullable|numeric',
            'signed_at'    => 'nullable|date',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',

        ]);
        // статус не приходит из формы — задаём вручную
        $data['status'] = 'new';

        $data['created_by'] = auth()->id();

        // файл договора
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('contracts', 'public');

            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
        }

        $contract = Contract::create($data);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('contracts', 'public');

                $contract->files()->create([
                    'file_path'  => $path,
                    'file_name'  => $file->getClientOriginalName(),
                    'mime_type'  => $file->getMimeType(),
                    'uploaded_by'=> auth()->id(),
                ]);
            }
        }


        return response()->json($contract->load('creator:id,name'), 201);
    }

    public function update(Request $request, Contract $contract)
    {
        abort_unless($contract->created_by === auth()->id(), 403);

        $data = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'counterparty' => 'nullable|string|max:255',
            'amount'       => 'nullable|numeric',
            'status'       => 'required|in:new,negotiation,signed,rejected',
            'signed_at'    => 'nullable|date',

            // несколько файлов
            'files.*'      => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        // 🔥 Обновляем основные поля договора (кроме файлов!)
        $contract->update($data);

        // 🔥 Если есть новые файлы — добавляем их
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('contracts', 'public');

                $contract->files()->create([
                    'file_path'   => $path,
                    'file_name'   => $file->getClientOriginalName(),
                    'mime_type'   => $file->getMimeType(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        return $contract->fresh()->load(['creator:id,name', 'files']);
    }


    public function destroy(Contract $contract)
    {
         abort_unless($contract->created_by === auth()->id(), 403);

        if ($contract->file_path) {
            Storage::disk('public')->delete($contract->file_path);
        }

        $contract->delete();

        return response()->json(['message' => 'Договор удалён']);
    }

    public function move(Request $request, Contract $contract)
    {
        $request->validate([
            'status' => 'required|in:new,negotiation,signed,rejected',
        ]);

        $contract->update([
            'status' => $request->status,
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteFile(ContractFile $file)
    {
        // защита: удалять файлы может только создатель договора
        $contract = $file->contract;

         abort_unless($contract->created_by === auth()->id(), 403);

        // удаляем файл с диска
        if ($file->file_path) {
            Storage::disk('public')->delete($file->file_path);
        }

        // удаляем запись
        $file->delete();

        return response()->json(['success' => true]);
    }

    public function downloadFile(ContractFile $file)
    {
        // Проверка: скачивать может только владелец договора
        $contract = $file->contract;

        abort_unless($contract->created_by === auth()->id(), 403);

        $path = storage_path('app/public/' . $file->file_path);

        if (!file_exists($path)) {
            abort(404, "Файл не найден");
        }

        return response()->download($path, $file->file_name);
    }




}
