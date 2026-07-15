<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Broadcast extends Model
{
    protected $fillable = [
        'sent_by',
        'channel',
        'category',
        'audience',
        'title',
        'subject',
        'message',
        'recipients_count',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'recipients_count' => 'integer',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
