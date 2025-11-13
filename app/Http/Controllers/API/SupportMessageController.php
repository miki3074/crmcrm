<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Auth;

class SupportMessageController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $messages = SupportMessage::with([
    'replies.user.roles:id,name', // <- добавляем роли
    'attachments:id,support_message_id,path,original_name,mime_type'
])
->where('user_id', $userId)
->latest()
->get(['id', 'message', 'page_url', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }




}

