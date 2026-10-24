<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskEvent extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['changes' => 'array']; }
}
