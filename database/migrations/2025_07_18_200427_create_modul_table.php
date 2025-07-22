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
        Schema::create('modul', function (Blueprint $table) {
            $table->id();
            $table->string('judul_modul');
            $table->unsignedBigInteger('category_modul_id');
            $table->string('link_video');
            $table->string('path_pdf')->nullable();
            $table->string('foto')->nullable();
            $table->text('deskripsi_modul');
            $table->timestamps();

            $table->foreign('category_modul_id')->references('id')->on('category_modul')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modul');
    }
};
