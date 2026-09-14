<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSubject extends Model
{
    protected $fillable = ['academic_group_id', 'name', 'name_bn', 'paper', 'sort_order'];

    public function group()
    {
        return $this->belongsTo(AcademicGroup::class, 'academic_group_id');
    }

    public function chapters()
    {
        return $this->hasMany(AcademicChapter::class)->orderBy('sort_order');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
