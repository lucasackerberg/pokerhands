<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionGroupEntry extends Model
{
    protected $fillable = ['session_group_id', 'user_id', 'buy_in', 'cash_out', 'notes'];

    protected $casts = [
        'buy_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
    ];

    protected $appends = ['profit'];

    public function getProfitAttribute(): float
    {
        return (float) ($this->cash_out - $this->buy_in);
    }

    public function sessionGroup(): BelongsTo
    {
        return $this->belongsTo(SessionGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
