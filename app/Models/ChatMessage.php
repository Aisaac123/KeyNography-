<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'message',
        'hidden_message',
        'hidden_content_type',
        'hidden_document_path',
        'hidden_document_filename',
        'hidden_document_mime_type',
        'hidden_document_size',
        'is_password_protected',
    ];

    protected $casts = [
        'is_password_protected' => 'boolean',
        'hidden_document_size' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica si el mensaje tiene contenido oculto extraído
     */
    public function hasHiddenContent(): bool
    {
        return $this->hidden_content_type !== null;
    }

    /**
     * Verifica si el contenido oculto es un documento
     */
    public function hasHiddenDocument(): bool
    {
        return $this->hidden_content_type === 'document';
    }

    /**
     * Verifica si el contenido oculto es texto
     */
    public function hasHiddenText(): bool
    {
        return $this->hidden_content_type === 'text';
    }

    /**
     * Obtiene el tamaño del documento en formato legible
     */
    public function getFormattedDocumentSize(): string
    {
        if (!$this->hidden_document_size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->hidden_document_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Obtiene el ícono según el tipo de archivo
     */
    public function getDocumentIcon(): string
    {
        if (!$this->hidden_document_mime_type) {
            return 'heroicon-o-document';
        }

        return match (true) {
            str_contains($this->hidden_document_mime_type, 'pdf') => 'heroicon-o-document-text',
            str_contains($this->hidden_document_mime_type, 'word') => 'heroicon-o-document-text',
            str_contains($this->hidden_document_mime_type, 'excel') => 'heroicon-o-table-cells',
            str_contains($this->hidden_document_mime_type, 'sheet') => 'heroicon-o-table-cells',
            str_contains($this->hidden_document_mime_type, 'image') => 'heroicon-o-photo',
            str_contains($this->hidden_document_mime_type, 'video') => 'heroicon-o-film',
            str_contains($this->hidden_document_mime_type, 'audio') => 'heroicon-o-musical-note',
            str_contains($this->hidden_document_mime_type, 'zip') => 'heroicon-o-archive-box',
            str_contains($this->hidden_document_mime_type, 'text') => 'heroicon-o-document-text',
            default => 'heroicon-o-document',
        };
    }
}
