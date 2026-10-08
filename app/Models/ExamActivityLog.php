<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamActivityLog extends Model
{
    protected $fillable = ['exam_session_id', 'event', 'detail'];
}
