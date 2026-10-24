<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['limits' => 'array', 'price_monthly' => 'decimal:2', 'price_yearly' => 'decimal:2'];
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'limits',
    ];
}
