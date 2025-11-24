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
        Schema::table('chat_messages', function (Blueprint $table) {
            // Tipo de contenido oculto: "text", "document", null
            $table->string('hidden_content_type')->nullable()->after('hidden_message');

            // Para documentos ocultos
            $table->string('hidden_document_path')->nullable()->after('hidden_content_type');
            $table->string('hidden_document_filename')->nullable()->after('hidden_document_path');
            $table->string('hidden_document_mime_type')->nullable()->after('hidden_document_filename');
            $table->integer('hidden_document_size')->nullable()->after('hidden_document_mime_type');

            // Indica si el contenido está protegido con contraseña
            $table->boolean('is_password_protected')->default(false)->after('hidden_document_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn([
                'hidden_content_type',
                'hidden_document_path',
                'hidden_document_filename',
                'hidden_document_mime_type',
                'hidden_document_size',
                'is_password_protected',
            ]);
        });
    }

};
