<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiExplanation extends Model
{
    protected $fillable = [
        'exam_result_id',
        'question_id',
        'answer_fingerprint',
        'explanation',
    ];
}