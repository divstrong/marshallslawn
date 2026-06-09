<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * The foreman side of the foreman <-> office chat. Each foreman has a
 * single thread keyed by their employee id; the office replies from the
 * Dispatch screen.
 */
class ChatController extends Controller
{
    /**
     * GET /api/chat — the thread, newest first. Opening it marks the
     * office's messages as read.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Employee $employee */
        $employee = $request->user();

        ChatMessage::query()
            ->where('employee_id', $employee->id)
            ->where('sender', ChatMessage::SENDER_OFFICE)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = ChatMessage::query()
            ->with(['senderUser:id,name', 'employee:id,name,first_name,last_name'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return ChatMessageResource::collection($messages);
    }

    /**
     * GET /api/chat/unread — count of unread office messages (tab badge).
     */
    public function unread(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $count = ChatMessage::query()
            ->where('employee_id', $employee->id)
            ->where('sender', ChatMessage::SENDER_OFFICE)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * POST /api/chat — send a message (text and/or one attachment: photo, video, or file).
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:51200'],
        ]);

        $file = $request->file('attachment');
        $body = trim((string) $request->input('body'));

        if ($body === '' && ! $file) {
            throw ValidationException::withMessages([
                'body' => ['Enter a message or attach a file.'],
            ]);
        }

        $attributes = [
            'employee_id' => $employee->id,
            'sender' => ChatMessage::SENDER_FOREMAN,
            'body' => $body !== '' ? $body : null,
        ];

        if ($file) {
            $mime = (string) $file->getMimeType();
            $type = str_starts_with($mime, 'video/')
                ? 'video'
                : (str_starts_with($mime, 'image/') ? 'photo' : 'file');

            $path = $file->store('chat-media', 'public');
            $attributes += [
                'attachment_type' => $type,
                'attachment_disk' => 'public',
                'attachment_path' => $path,
                'attachment_name' => $file->getClientOriginalName() ?: basename($path),
                'attachment_mime' => $mime,
                'attachment_size' => $file->getSize(),
            ];
        }

        $message = ChatMessage::create($attributes);
        $message->load('employee:id,name,first_name,last_name');

        return response()->json(['data' => new ChatMessageResource($message)], 201);
    }
}
