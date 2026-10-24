<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['title', 'description', 'archived'];
    protected function casts(): array { return ['archived' => 'boolean']; }
    public function owner() { return $this->belongsTo(User::class, 'user_id'); }
    public function members() { return $this->belongsToMany(User::class, 'project_members')->withPivot('role')->withTimestamps(); }
    public function tasks() { return $this->hasMany(Task::class); }
    public function events() { return $this->hasMany(TaskEvent::class); }
    public function scopeVisibleTo($query, User $user) {
        return $query->where(fn ($q) => $q->where('user_id', $user->id)->orWhereHas('members', fn ($q) => $q->where('users.id', $user->id)));
    }
}
