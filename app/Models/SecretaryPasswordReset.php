<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecretaryPasswordReset extends Model
{
    /**
     * Wrong guesses allowed before the code is invalidated.
     */
    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'email',
        'code',
        'attempts',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
