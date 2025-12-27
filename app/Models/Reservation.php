<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'wishlist_item_id',
        'user_id',
        'status',
        'note',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // Отношения
    public function wishlistItem()
    {
        return $this->belongsTo(WishlistItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
