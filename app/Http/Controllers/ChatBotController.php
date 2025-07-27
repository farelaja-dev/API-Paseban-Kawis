<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ChatLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/chat/start",
     *     summary="Mulai sesi chat baru",
     *     tags={"Chatbot"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Response(
     *         response=200,
     *         description="Session ID berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="session_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function startSession(Request $request)
    {
        $session = ChatSession::create([
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['session_id' => $session->id]);
    }

    /**
     * @OA\Post(
     *     path="/api/chat/send",
     *     summary="Kirim pesan ke chatbot dan dapatkan balasan",
     *     tags={"Chatbot"},
     *     security={{ "sanctum":{ }}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id", "prompt"},
     *             @OA\Property(property="session_id", type="integer"),
     *             @OA\Property(property="prompt", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Balasan dari chatbot",
     *         @OA\JsonContent(
     *             @OA\Property(property="reply", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:chat_sessions,id',
            'prompt' => 'required|string',
        ]);

        $prompt = $request->input('prompt');
        $sessionId = $request->input('session_id');

        // Call to OpenRouter (DeepSeek)
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'deepseek/deepseek-chat-v3-0324:free',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an educational assistant.'],
                ['role' => 'user', 'content' => $prompt],
            ]
        ]);

        Log::info('OpenRouter response:', [$response->json()]);

        $botReply = $response->json('choices.0.message.content') ?? 'Maaf, terjadi kesalahan.';

        // Save chat log
        ChatLog::create([
            'session_id' => $sessionId,
            'prompt' => $prompt,
            'response' => $botReply,
        ]);

        return response()->json(['reply' => $botReply]);
    }

    /**
     * @OA\Get(
     *     path="/api/chat/history/{sessionId}",
     *     summary="Ambil semua log chat untuk satu sesi",
     *     tags={"Chatbot"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar log chat",
     *         @OA\JsonContent(
     *             @OA\Property(property="logs", type="array", @OA\Items(
     *                 @OA\Property(property="prompt", type="string"),
     *                 @OA\Property(property="response", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getSessionLogs($sessionId)
    {
        $logs = ChatLog::where('session_id', $sessionId)
            ->orderBy('created_at')
            ->get(['prompt', 'response', 'created_at']);

        return response()->json(['logs' => $logs]);
    }

    /**
     * @OA\Post(
     *     path="/api/chat/end/{sessionId}",
     *     summary="Akhiri sesi chat",
     *     tags={"Chatbot"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sesi chat telah diakhiri",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function endSession($sessionId)
    {
        ChatSession::where('id', $sessionId)->update(['ended_at' => now()]);
        return response()->json(['message' => 'Sesi chat telah diakhiri']);
    }

    /**
     * @OA\Get(
     *     path="/api/chat/sessions",
     *     summary="Ambil semua sesi chat milik user ini",
     *     tags={"Chatbot"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar sesi chat",
     *         @OA\JsonContent(
     *             @OA\Property(property="sessions", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getAllSessions(Request $request)
    {
        $sessions = ChatSession::where('user_id', $request->user()->id)
            ->with(['latestMessage' => function ($query) {
                $query->select('session_id', 'prompt', 'created_at');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['sessions' => $sessions]);
    }

    /**
     * @OA\Get(
     *     path="/api/test-chatbot",
     *     tags={"Quiz"},
     *     summary="Test endpoint dari ChatbotController",
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function testSwagger()
    {
        return response()->json(['ok' => true]);
    }
}
