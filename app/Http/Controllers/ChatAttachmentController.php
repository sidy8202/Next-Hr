<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChatAttachmentController
{
    public function download(ChatMessage $message): BinaryFileResponse
    {
        abort_unless(auth()->user()?->canDo('read', 'team_chat'), 403);
        abort_unless((int) $message->company_id === (int) auth()->user()->company_id, 404);
        abort_unless($message->attachment_path, 404);
        abort_unless($message->conversation()->whereHas('participants', fn ($query) => $query->where('user_id', auth()->id()))->exists(), 404);
        abort_unless(Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name ?: 'piece-jointe');
    }
}
