<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionGroup extends Model
{
    protected $fillable = ['organizer_id', 'name', 'code', 'session_date', 'description'];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SessionGroupEntry::class);
    }
}
