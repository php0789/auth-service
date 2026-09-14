<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class OutboxEvent extends Model
{
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'event_type',
        'aggregate_id',
        'payload',
        'metadata',
        'occurred_at',
        'published_at',
        'attempts',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'attempts' => 'integer',
        ];
    }
}
