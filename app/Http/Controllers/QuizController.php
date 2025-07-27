<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/quiz",
     *     summary="Buat quiz baru dengan upload thumbnail",
     *     tags={"Quiz"},
     *     security={{ "sanctum":{ }}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title"},
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="thumbnail", type="file", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Quiz berhasil dibuat"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnail_quiz', 'public');
            $thumbnailPath = 'storage/' . $path;
        }

        $quiz = Quiz::create([
            'title' => $request->title,
            'description' => $request->description,
            'thumbnail' => $thumbnailPath,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Quiz berhasil dibuat',
            'quiz' => $quiz
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/quiz/{quiz_id}",
     *     summary="Update quiz (admin only)",
     *     tags={"Quiz"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="quiz_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="thumbnail", type="file", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quiz berhasil diupdate"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Quiz tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */
    public function update(Request $request, $quiz_id)
    {
        try {
            $quiz = Quiz::findOrFail($quiz_id);
            
            $request->validate([
                'title' => 'sometimes|required|string',
                'description' => 'nullable|string',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $data = $request->only(['title', 'description']);

            // Handle thumbnail update
            if ($request->hasFile('thumbnail')) {
                // Hapus thumbnail lama jika ada
                if ($quiz->thumbnail) {
                    $oldPath = str_replace('storage/', '', $quiz->thumbnail);
                    Storage::disk('public')->delete($oldPath);
                }
                
                // Upload thumbnail baru
                $path = $request->file('thumbnail')->store('thumbnail_quiz', 'public');
                $data['thumbnail'] = 'storage/' . $path;
            }

            $quiz->update($data);

            return response()->json([
                'message' => 'Quiz berhasil diupdate',
                'quiz' => $quiz
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Gagal mengupdate quiz: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/quiz/questions/{question_id}",
     *     summary="Update soal quiz (admin only)",
     *     tags={"Quiz"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="question_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question_text"},
     *             @OA\Property(property="question_text", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Soal berhasil diupdate"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Soal tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */
    public function updateQuestion(Request $request, $question_id)
    {
        try {
            $question = Question::findOrFail($question_id);
            
            $request->validate([
                'question_text' => 'required|string',
            ]);

            $question->update([
                'question_text' => $request->question_text,
            ]);

            return response()->json([
                'message' => 'Soal berhasil diupdate',
                'question' => $question
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Gagal mengupdate soal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/quiz/questions/{question_id}",
     *     summary="Hapus soal quiz dan semua pilihan terkait (admin only)",
     *     tags={"Quiz"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="question_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Soal berhasil dihapus"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Soal tidak ditemukan"
     *     )
     * )
     */
    public function destroyQuestion($question_id)
    {
        try {
            $question = Question::findOrFail($question_id);
            
            // Hapus semua options yang terkait dengan question ini
            Option::where('question_id', $question_id)->delete();
            
            // Hapus question
            $question->delete();
            
            return response()->json([
                'message' => 'Soal berhasil dihapus beserta semua pilihan terkait'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Gagal menghapus soal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/quiz/options/{option_id}",
     *     summary="Update pilihan soal (admin only)",
     *     tags={"Quiz"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="option_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"option_label","option_text","is_correct"},
     *             @OA\Property(property="option_label", type="string", enum={"A","B","C","D"}),
     *             @OA\Property(property="option_text", type="string"),
     *             @OA\Property(property="is_correct", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pilihan berhasil diupdate"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pilihan tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */
    public function updateOption(Request $request, $option_id)
    {
        try {
            $option = Option::findOrFail($option_id);
            
            $request->validate([
                'option_label' => 'required|string|in:A,B,C,D',
                'option_text' => 'required|string',
                'is_correct' => 'required|boolean',
            ]);

            // Validasi agar tidak ada duplikasi option_label untuk question yang sama (kecuali option yang sedang diupdate)
            $existingOption = Option::where('question_id', $option->question_id)
                ->where('option_label', $request->option_label)
                ->where('id', '!=', $option_id)
                ->first();
            
            if ($existingOption) {
                return response()->json([
                    'error' => true,
                    'message' => 'Pilihan dengan label ' . $request->option_label . ' sudah ada untuk soal ini'
                ], 422);
            }

            // Jika option ini akan menjadi jawaban benar, pastikan tidak ada jawaban benar lain
            if ($request->is_correct) {
                $existingCorrectOption = Option::where('question_id', $option->question_id)
                    ->where('is_correct', true)
                    ->where('id', '!=', $option_id)
                    ->first();
                
                if ($existingCorrectOption) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Sudah ada jawaban benar untuk soal ini. Hanya boleh ada satu jawaban benar per soal.'
                    ], 422);
                }
            }

            $option->update([
                'option_label' => $request->option_label,
                'option_text' => $request->option_text,
                'is_correct' => $request->is_correct,
            ]);

            return response()->json([
                'message' => 'Pilihan berhasil diupdate',
                'option' => $option
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Gagal mengupdate pilihan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/quiz/options/{option_id}",
     *     summary="Hapus pilihan soal (admin only)",
     *     tags={"Quiz"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="option_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pilihan berhasil dihapus"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pilihan tidak ditemukan"
     *     )
     * )
     */
    public function destroyOption($option_id)
    {
        try {
            $option = Option::findOrFail($option_id);
            $option->delete();
            
            return response()->json([
                'message' => 'Pilihan berhasil dihapus'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Gagal menghapus pilihan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/quiz/{quiz_id}/questions",
     *     summary="Tambah soal ke quiz (admin)",
     *     tags={"Quiz"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="quiz_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question_text"},
     *             @OA\Property(property="question_text", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Soal berhasil ditambahkan"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */
    public function addQuestion(Request $request, $quiz_id)
    {
        $request->validate([
            'question_text' => 'required|string',
        ]);
        $question = Question::create([
            'quiz_id' => $quiz_id,
            'question_text' => $request->question_text,
        ]);
        return response()->json(['message' => 'Soal berhasil ditambahkan', 'question' => $question], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/quiz/questions/{question_id}/options",
     *     summary="Tambah pilihan ke soal (admin)",
     *     tags={"Quiz"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="question_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"option_label","option_text","is_correct"},
     *             @OA\Property(property="option_label", type="string", enum={"A","B","C","D"}),
     *             @OA\Property(property="option_text", type="string"),
     *             @OA\Property(property="is_correct", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pilihan berhasil ditambahkan"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */
    public function addOption(Request $request, $question_id)
    {
        try {
            $request->validate([
                'option_label' => 'required|string|in:A,B,C,D',
                'option_text' => 'required|string',
                'is_correct' => 'required|boolean',
            ]);

            // Validasi agar tidak ada duplikasi option_label untuk question yang sama
            $existingOption = Option::where('question_id', $question_id)
                ->where('option_label', $request->option_label)
                ->first();
            
            if ($existingOption) {
                return response()->json([
                    'error' => true,
                    'message' => 'Pilihan dengan label ' . $request->option_label . ' sudah ada untuk soal ini'
                ], 422);
            }

            // Jika option ini akan menjadi jawaban benar, pastikan tidak ada jawaban benar lain
            if ($request->is_correct) {
                $existingCorrectOption = Option::where('question_id', $question_id)
                    ->where('is_correct', true)
                    ->first();
                
                if ($existingCorrectOption) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Sudah ada jawaban benar untuk soal ini. Hanya boleh ada satu jawaban benar per soal.'
                    ], 422);
                }
            }

            $option = Option::create([
                'question_id' => $question_id,
                'option_label' => $request->option_label,
                'option_text' => $request->option_text,
                'is_correct' => $request->is_correct,
            ]);
            
            return response()->json(['message' => 'Pilihan berhasil ditambahkan', 'option' => $option], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/quiz",
     *     summary="List quiz untuk user",
     *     tags={"Quiz"},
     *     @OA\Response(
     *         response=200,
     *         description="List quiz",
     *         @OA\JsonContent(
     *             @OA\Property(property="quizzes", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="thumbnail", type="string")
     *             ))
     *         )
     *     )
     * )
     */
    public function listQuiz()
    {
        $quizzes = Quiz::select('id', 'title', 'description', 'thumbnail')->get();
        return response()->json(['quizzes' => $quizzes]);
    }

    /**
     * @OA\Get(
     *     path="/api/quiz/{quiz_id}/questions",
     *     summary="Ambil soal dan pilihan untuk user",
     *     tags={"Quiz"},
     *     @OA\Parameter(
     *         name="quiz_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List soal dan pilihan",
     *         @OA\JsonContent(
     *             @OA\Property(property="questions", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="question_text", type="string"),
     *                 @OA\Property(property="options", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="option_label", type="string"),
     *                     @OA\Property(property="option_text", type="string")
     *                 ))
     *             ))
     *         )
     *     )
     * )
     */
    public function getQuestions($quiz_id)
    {
        $questions = Question::where('quiz_id', $quiz_id)
            ->with(['options:id,question_id,option_label,option_text,is_correct'])
            ->select('id', 'question_text')
            ->get();
        return response()->json(['questions' => $questions]);
    }

    /**
     * @OA\Post(
     *     path="/api/quiz/{quiz_id}/submit",
     *     summary="Submit jawaban user",
     *     tags={"Quiz"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="quiz_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(property="answers", type="array", @OA\Items(
     *                 @OA\Property(property="question_id", type="integer"),
     *                 @OA\Property(property="selected_option", type="string", enum={"A","B","C","D"})
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jawaban berhasil disubmit dan skor dikembalikan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="score", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */
    public function submitAnswers(Request $request, $quiz_id)
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.selected_option' => 'required|string|in:A,B,C,D',
        ]);
        $user_id = $request->user()->id;
        $score = 0;
        $total = count($request->answers);
        foreach ($request->answers as $ans) {
            $question = Question::find($ans['question_id']);
            $correct = Option::where('question_id', $question->id)
                ->where('option_label', $ans['selected_option'])
                ->where('is_correct', true)
                ->exists();
            if ($correct) $score++;
        }
        return response()->json([
            'message' => 'Jawaban berhasil disubmit',
            'score' => $score,
            'total' => $total
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/quiz/{quiz_id}",
     *     summary="Hapus quiz dan semua data terkait (admin only)",
     *     tags={"Quiz"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="quiz_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quiz berhasil dihapus"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Quiz tidak ditemukan"
     *     )
     * )
     */
    public function destroy($quiz_id)
    {
        try {
            // Cari quiz
            $quiz = Quiz::findOrFail($quiz_id);
            
            // Hapus thumbnail jika ada
            if ($quiz->thumbnail) {
                $oldPath = str_replace('storage/', '', $quiz->thumbnail);
                Storage::disk('public')->delete($oldPath);
            }
            
            // Hapus semua options yang terkait dengan questions dari quiz ini
            $questionIds = Question::where('quiz_id', $quiz_id)->pluck('id');
            Option::whereIn('question_id', $questionIds)->delete();
            
            // Hapus semua questions dari quiz ini
            Question::where('quiz_id', $quiz_id)->delete();
            
            // Hapus quiz
            $quiz->delete();
            
            return response()->json([
                'message' => 'Quiz berhasil dihapus beserta semua data terkait'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Gagal menghapus quiz: ' . $e->getMessage()
            ], 500);
        }
    }
} 
