<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'related_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'type' => 'string',  // Для enum
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
