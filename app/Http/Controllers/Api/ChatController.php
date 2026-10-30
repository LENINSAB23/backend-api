<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\GeminiService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        protected GeminiService $geminiService
    ) {}

    /**
     * List all conversations belonging to the authenticated user.
     */
    public function listConversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::where('user_id', $user->id)
            ->with(['latestMessage'])
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $conversations,
        ]);
    }

    /**
     * Create a new conversation for the authenticated user.
     */
    public function createConversation(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'nullable|string|max:100',
        ]);

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => $validated['title'] ?? 'Nueva conversación',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Conversación iniciada correctamente',
            'data' => $conversation,
        ], 201);
    }

    /**
     * Get all messages of a specific conversation.
     */
    public function getMessages(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversación no encontrada o acceso no autorizado.',
            ], 404);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'conversation' => $conversation,
            'data' => $messages,
        ]);
    }

    /**
     * Send a user message to Gemini, save both prompt and AI answer in DB.
     */
    public function sendMessage(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'message' => 'required_without:content|string|max:4000',
            'content' => 'required_without:message|string|max:4000',
        ]);

        $text = trim((string) ($validated['message'] ?? $validated['content']));

        if ($text === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'El mensaje no puede estar vacío.',
            ], 422);
        }

        $conversation = Conversation::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversación no encontrada o no autorizada.',
            ], 404);
        }

        // Store user message
        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $text,
        ]);

        // Auto-update conversation title if it's the first message or still default
        if ($conversation->title === 'Nueva conversación' || $conversation->messages()->count() <= 2) {
            $cleanedTitle = Str::limit(preg_replace('/\s+/', ' ', $text), 45, '...');
            $conversation->update(['title' => $cleanedTitle]);
        }

        try {
            $aiReply = $this->geminiService->chat($conversation, $text);

            $botMessage = $conversation->messages()->create([
                'role' => 'model',
                'content' => $aiReply,
            ]);

            $conversation->touch();

            return response()->json([
                'status' => 'success',
                'respuesta' => $aiReply,
                'conversation_id' => $conversation->id,
                'data' => [
                    'user_message' => $userMessage,
                    'bot_message' => $botMessage,
                ],
            ]);
        } catch (Exception $e) {
            // Delete the unfulfilled user message to avoid orphaned unanswered messages on failure
            $userMessage->delete();

            $statusCode = 502;
            $msg = $e->getMessage();
            if (str_contains($msg, '429') || str_contains(strtolower($msg), 'cuota')) {
                $statusCode = 429;
            } elseif (str_contains($msg, '401') || str_contains($msg, '403')) {
                $statusCode = 500;
            }

            return response()->json([
                'status' => 'error',
                'message' => $msg,
            ], $statusCode);
        }
    }

    /**
     * Delete a conversation.
     */
    public function deleteConversation(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Conversación no encontrada o no autorizada.',
            ], 404);
        }

        $conversation->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Conversación eliminada correctamente.',
        ]);
    }
}
