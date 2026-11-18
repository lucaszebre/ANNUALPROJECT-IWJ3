<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Level and cover shown by the catalogue and the course page (Figma "Cartes / Cours catalogue").
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('level')->default('beginner')->after('description');
            $table->string('cover_label', 40)->nullable()->after('level');
            $table->string('cover_tone')->default('soft')->after('cover_label');
            $table->string('cover_code', 80)->nullable()->after('cover_tone');

            $table->index(['status', 'level']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['status', 'level']);
            $table->dropColumn(['level', 'cover_label', 'cover_tone', 'cover_code']);
        });
    }
};
