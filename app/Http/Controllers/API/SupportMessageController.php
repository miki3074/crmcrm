<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Auth;
use App\Models\SupportReplyAttachment;

class SupportMessageController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $messages = SupportMessage::with([
        'replies:id,support_message_id,user_id,reply,created_at',
        'replies.user.roles:id,name',
         'replies.attachment',  
        'attachments:id,support_message_id,path,original_name,mime_type'
    ])
    ->where('user_id', $userId)
    ->latest()
    ->get(['id','message','status','last_read_at','created_at']);

$messages->append('has_unread');

return response()->json([
    'success' => true,
    'data' => $messages
]);

    }


public function markRead($id)
{
    $msg = SupportMessage::where('user_id', Auth::id())->findOrFail($id);
    $msg->update(['last_read_at' => now()]);

    return response()->json(['success' => true]);
}


public function markUserRead($id)
{
    $msg = SupportMessage::where('user_id', Auth::id())->findOrFail($id);
    $msg->update(['user_last_read' => now()]);

    return response()->json(['success' => true]);
}

public function markSupportRead($id)
{
    $msg = SupportMessage::findOrFail($id);

    if (!Auth::user()->hasRole('support')) {
        return response()->json(['message' => 'Доступ запрещён'], 403);
    }

    $msg->update(['support_last_read' => now()]);

    return response()->json(['success' => true]);
}



}

