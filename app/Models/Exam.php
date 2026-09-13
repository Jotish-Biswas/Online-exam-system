<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'exam_id',
        'exam_name',
        'description',
        'duration_minutes',
        'shuffle_questions',
        'shuffle_options',
        'enable_anti_cheating',
        'negative_marking',
        'pass_percentage',
        'start_time',
        'end_time',
        'created_by',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'enable_anti_cheating' => 'boolean',
        'duration_minutes' => 'integer',
        'negative_marking' => 'decimal:2',
        'pass_percentage' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpenNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $now = now();
        if ($this->start_time && $now->lt($this->start_time)) {
            return false;
        }
        if ($this->end_time && $now->gt($this->end_time)) {
            return false;
        }
        return true;
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }
}
