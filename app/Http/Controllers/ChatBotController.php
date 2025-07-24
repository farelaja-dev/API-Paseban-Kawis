<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ChatLog;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function startSession(Request $request)
    {
        $session = ChatSession::create([
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['session_id' => $session->id]);
    }

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

        $botReply = $response->json('choices.0.message.content') ?? 'Maaf, terjadi kesalahan.';

        // Save chat log
        ChatLog::create([
            'session_id' => $sessionId,
            'prompt' => $prompt,
            'response' => $botReply,
        ]);

        return response()->json(['reply' => $botReply]);
    }

    public function getSessionLogs($sessionId)
    {
        $logs = ChatLog::where('session_id', $sessionId)
            ->orderBy('created_at')
            ->get(['prompt', 'response', 'created_at']);

        return response()->json(['logs' => $logs]);
    }

    public function endSession($sessionId)
    {
        ChatSession::where('id', $sessionId)->update(['ended_at' => now()]);
        return response()->json(['message' => 'Sesi chat telah diakhiri']);
    }

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
}
