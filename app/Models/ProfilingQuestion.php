<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilingQuestion extends Model
{
    protected $fillable = ['code', 'prompt', 'material_keyword'];
}
