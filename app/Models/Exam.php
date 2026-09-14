<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'exam_id',
        'exam_name',
        'creation_mode',
        'academic_group_id',
        'academic_subject_id',
        'description',
        'duration_minutes',
        'shuffle_questions',
        'shuffle_options',
        'enable_anti_cheating',
        'negative_marking',
        'pass_percentage',
        'mcq_pass_percentage',
        'writing_pass_percentage',
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
        'mcq_pass_percentage' => 'decimal:2',
        'writing_pass_percentage' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function academicGroup()
    {
        return $this->belongsTo(AcademicGroup::class);
    }

    public function academicSubject()
    {
        return $this->belongsTo(AcademicSubject::class);
    }

    public function chapters()
    {
        return $this->belongsToMany(AcademicChapter::class, 'exam_chapter');
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

    public function canAttemptNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return !$this->start_time || now()->gte($this->start_time);
    }

    public function scheduleState(): string
    {
        if (!$this->start_time && !$this->end_time) {
            return 'unscheduled';
        }
        if ($this->start_time && now()->lt($this->start_time)) {
            return 'upcoming';
        }
        if ($this->end_time && now()->gt($this->end_time)) {
            return 'ended';
        }

        return 'running';
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }

    public function mcqPassPercentage(): float
    {
        return (float) ($this->mcq_pass_percentage ?? $this->pass_percentage ?? 40);
    }

    public function writingPassPercentage(): float
    {
        return (float) ($this->writing_pass_percentage ?? $this->pass_percentage ?? 40);
    }

    public function hasMcqQuestions(): bool
    {
        return $this->questions->contains(fn ($q) => $q->isMCQ());
    }

    public function hasWritingQuestions(): bool
    {
        return $this->questions->contains(fn ($q) => $q->isFileUpload());
    }
}
