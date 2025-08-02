<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_quiz_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->integer('score'); // Jumlah jawaban benar
            $table->integer('total_questions'); // Total soal
            $table->decimal('percentage', 5, 2); // Nilai dalam persen (0-100)
            $table->timestamp('submitted_at');
            $table->timestamps();
            
            // Mencegah user mengerjakan quiz yang sama lebih dari sekali
            $table->unique(['user_id', 'quiz_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_quiz_scores');
    }
}; 
