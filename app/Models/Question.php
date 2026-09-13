<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'exam_id',
        'question_text',
        'question_type',
        'marks',
        'explanation',
        'file_upload_settings'
    ];

    protected $casts = [
        'file_upload_settings' => 'array',
        'marks' => 'decimal:2',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }

    /**
     * Check if this question is a file upload type
     */
    public function isFileUpload()
    {
        return $this->question_type === 'file_upload';
    }

    /**
     * Check if this question is an MCQ type (single or multiple)
     */
    public function isMCQ()
    {
        return in_array($this->question_type, ['single', 'multiple']);
    }

    /**
     * Get allowed file extensions for file upload questions
     */
    public function getAllowedExtensions()
    {
        if (!$this->isFileUpload() || !$this->file_upload_settings) {
            return [];
        }
        return $this->file_upload_settings['allowed_extensions'] ?? ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    }

    /**
     * Get maximum file size in MB for file upload questions
     */
    public function getMaxFileSize()
    {
        if (!$this->isFileUpload() || !$this->file_upload_settings) {
            return 10; // Default 10MB
        }
        return $this->file_upload_settings['max_size_mb'] ?? 10;
    }
}
