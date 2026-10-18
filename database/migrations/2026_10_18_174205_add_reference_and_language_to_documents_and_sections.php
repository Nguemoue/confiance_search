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
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('reference_code')->nullable()->after('slug'); // Ex: "CS-RH-2026-001"
            $table->string('language', 5)->default('fr')->after('department'); // 'fr' ou 'en'
            $table->index(['language', 'reference_code']);
        });

        Schema::table('document_sections', function (Blueprint $table): void {
            $table->string('reference_label')->nullable()->after('page_number'); // Ex: "CS-RH-2026-001 § Article 4"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_sections', function (Blueprint $table): void {
            $table->dropColumn('reference_label');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex(['language', 'reference_code']);
            $table->dropColumn(['reference_code', 'language']);
        });
    }
};
