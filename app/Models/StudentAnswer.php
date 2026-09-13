<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    protected $fillable = [
        'exam_result_id',
        'question_id',
        'answer_id',
        'file_path',
        'original_filename',
        'file_size',
        'file_mime_type',
        'manual_score',
        'admin_feedback',
        'is_graded'
    ];

    protected $casts = [
        'is_graded' => 'boolean',
        'manual_score' => 'decimal:2',
        'file_size' => 'integer',
    ];

    public function examResult()
    {
        return $this->belongsTo(ExamResult::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }

    /**
     * Check if this is a file upload answer
     */
    public function isFileUpload()
    {
        if ($this->relationLoaded('question') && $this->question) {
            return $this->question->isFileUpload();
        }

        return !is_null($this->file_path);
    }

    public function getIsCorrectAttribute(): bool
    {
        if ($this->isFileUpload()) {
            $max = (float) ($this->question->marks ?? 1);

            return $this->is_graded && $max > 0 && (float) $this->manual_score >= $max;
        }

        return (bool) ($this->answer && $this->answer->is_correct);
    }

    public function getGradedScoreAttribute()
    {
        return $this->attributes['manual_score'] ?? null;
    }

    public function getGradingNotesAttribute()
    {
        return $this->attributes['admin_feedback'] ?? null;
    }

    /**
     * Get the file download URL
     */
    public function getFileUrl()
    {
        if (!$this->file_path) {
            return null;
        }
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSize()
    {
        if (!$this->file_size) {
            return null;
        }
        
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
