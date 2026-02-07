<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PokerSession extends Model
{
    protected $fillable = ['user_id', 'buy_in', 'cash_out', 'session_date', 'notes'];

    protected $casts = [
        'buy_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
        'profit' => 'decimal:2',
        'session_date' => 'date',
    ];

    protected $appends = ['profit'];

    public function getProfitAttribute(): float
    {
        return (float) ($this->cash_out - $this->buy_in);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
