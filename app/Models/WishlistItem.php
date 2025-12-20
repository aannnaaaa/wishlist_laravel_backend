<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'wishlist_id',
        'name',
        'price',
        'link',
        'image',
        'description',
        'category',
        'priority',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'priority' => 'string',
    ];

    // Автоматически добавлять эти поля в JSON
    protected $appends = ['is_reserved', 'reserved_by_user_id'];

    // Аксессор для проверки резервации
    public function getIsReservedAttribute()
    {
        return $this->reservations()->exists();
    }

    // Аксессор для получения ID пользователя, который зарезервировал
    public function getReservedByUserIdAttribute()
    {
        $reservation = $this->reservations()->first();
        return $reservation ? $reservation->user_id : null;
    }

    // Отношения
    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
