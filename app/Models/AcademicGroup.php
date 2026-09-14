<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicGroup extends Model
{
    protected $fillable = ['name', 'slug'];

    public function subjects()
    {
        return $this->hasMany(AcademicSubject::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
