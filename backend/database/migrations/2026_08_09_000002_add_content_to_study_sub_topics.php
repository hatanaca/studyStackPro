<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Conteúdo educacional do sub-tópico: texto explicativo (JSON estruturado),
        // FAQs, objetivos de aprendizado e configuração da animação interativa.
        Schema::table('study_sub_topics', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->jsonb('content')->nullable();
            $table->jsonb('faqs')->nullable();
            $table->jsonb('learning_objectives')->nullable();
            $table->jsonb('simulation_config')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('study_sub_topics', function (Blueprint $table) {
            $table->dropColumn(['description', 'content', 'faqs', 'learning_objectives', 'simulation_config']);
        });
    }
};
