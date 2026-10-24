<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['title', 'description', 'status', 'priority', 'due_date'];
    protected function casts(): array { return ['due_date' => 'date:Y-m-d']; }
    public function project() { return $this->belongsTo(Project::class); }
    public function checklistItems() { return $this->hasMany(ChecklistItem::class); }
}
