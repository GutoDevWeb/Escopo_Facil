<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::deleting(fn (User $user) => $user->notifications()->delete());
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function projects() { return $this->hasMany(Project::class); }
    public function joinedProjects() { return $this->belongsToMany(Project::class, 'project_members')->withPivot('role')->withTimestamps(); }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'plan_id',
        'job_role',
        'terms_accepted_at',
        'terms_version',
        'privacy_notice_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_demo' => 'boolean',
            'password' => 'hashed',
            'terms_accepted_at' => 'datetime',
        ];
    }

}
