<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicChapter extends Model
{
    protected $fillable = ['academic_subject_id', 'title', 'title_bn', 'sort_order'];

    public function subject()
    {
        return $this->belongsTo(AcademicSubject::class, 'academic_subject_id');
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_chapter');
    }
}
