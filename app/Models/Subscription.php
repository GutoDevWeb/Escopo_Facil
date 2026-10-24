<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected function casts(): array { return ['expires_at' => 'datetime']; }

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'expires_at'
    ];

    // Relação com o Utilizador
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relação com o Plano
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
