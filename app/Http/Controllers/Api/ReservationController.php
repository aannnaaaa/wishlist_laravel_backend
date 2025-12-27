<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $reservations = $request->user()->reservations()
            ->with(['wishlistItem.wishlist.user'])
            ->get();

        return response()->json([
            'data' => $reservations->map(function ($reservation) {
                $item = $reservation->wishlistItem;
                $wishlist = $item?->wishlist;
                $owner = $wishlist?->user;

                return [
                    'id' => $reservation->id,
                    'wishlist_item_id' => $reservation->wishlist_item_id,
                    'wishlist_id' => $wishlist?->id,
                    'user_id' => $reservation->user_id,
                    'status' => $reservation->status,
                    'note' => $reservation->note,
                    'created_at' => $reservation->created_at,
                    'updated_at' => $reservation->updated_at,

                    'wishlist_item' => $item ? [
                        'id' => $item->id,
                        'wishlist_id' => $item->wishlist_id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'link' => $item->link,
                        'image' => $item->image,
                        'description' => $item->description,
                        'category' => $item->category,
                        'priority' => $item->priority,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                    ] : null,

                    'wishlist' => $wishlist ? [
                        'id' => $wishlist->id,
                        'name' => $wishlist->name,
                        'description' => $wishlist->description,
                        'privacy' => $wishlist->privacy,
                    ] : null,

                    'wishlist_owner' => $owner ? [
                        'id' => $owner->id,
                        'name' => $owner->name,
                        'avatar' => $owner->avatar,
                    ] : null,
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'wishlist_item_id' => 'required|exists:wishlist_items,id',
            'status' => 'sometimes|in:reserved,purchased,cancelled',
            'note' => 'nullable|string',
        ]);

        $item = \App\Models\WishlistItem::find($validated['wishlist_item_id']);

        if (!$this->canAccessWishlist($request->user(), $item->wishlist)) {
            abort(403);
        }

        // нельзя резервировать свой подарок
        if ($item->wishlist->user_id === $request->user()->id) {
            abort(403, 'Нельзя резервировать свой подарок');
        }

        // проверяем, нет ли уже резерва
        if ($item->reservations()->where('user_id', $request->user()->id)->exists()) {
            abort(409, 'Уже зарезервировано');
        }

        // создаем бронь
        $reservation = $item->reservations()->create([
            'user_id' => $request->user()->id,
            'status' => $validated['status'] ?? 'reserved',
            'note' => $validated['note'] ?? null,
        ]);

        // создаем уведомление владельцу вишлиста
        \App\Models\Notification::create([
            'user_id' => $item->wishlist->user_id,
            'type' => 'reservation',
            'message' => $request->user()->name . " забронировал подарок \"{$item->name}\"",
            'related_id' => $reservation->id,
            'is_read' => false,
        ]);

        return response()->json($reservation, 201);
    }


    public function show(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id === $request->user()->id || $reservation->wishlistItem->wishlist->user_id === $request->user()->id) {
            return $reservation;
        }
        abort(403);
    }

    public function update(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:reserved,purchased,cancelled',
            'note' => 'nullable|string',
        ]);

        $reservation->update($validated);
        return $reservation;
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== $request->user()->id) {
            abort(403);
        }

        $reservation->delete();
        return response()->json(['message' => 'Резерв отменен']);
    }

    private function canAccessWishlist(User $currentUser, Wishlist $wishlist)
    {
        if ($wishlist->user_id === $currentUser->id) return true;
        if ($wishlist->privacy === 'public') return true;
        if ($wishlist->privacy === 'friends' && $currentUser->friends()->where('friend_id', $wishlist->user_id)->exists()) return true;
        return false;
    }
}
