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
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('department')->nullable(); // Ex: Ressources Humaines, Informatique, etc.
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->unsignedInteger('file_size')->default(0); // in bytes
            $table->unsignedInteger('page_count')->default(1);
            $table->boolean('is_published')->default(true);
            $table->longText('raw_text')->nullable();
            $table->timestamps();
        });

        Schema::create('document_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('page_number')->default(1);
            $table->string('section_title')->nullable(); // Ex: "Chapitre II : Télétravail" ou "Section 3"
            $table->longText('content');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['document_id', 'page_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_sections');
        Schema::dropIfExists('documents');
    }
};
