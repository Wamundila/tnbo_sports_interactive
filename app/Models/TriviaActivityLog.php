<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'event_name',
        'reference_type',
        'reference_id',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
