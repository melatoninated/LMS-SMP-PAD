<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'event', 'entity_type', 'entity_id', 'detail', 'created_at'];
}
