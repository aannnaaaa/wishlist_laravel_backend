<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->reservations;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'wishlist_item_id' => 'required|exists:wishlist_items,id',
            'status' => 'sometimes|in:reserved,purchased,cancelled',
            'note' => 'nullable|string',
        ]);

        $item = WishlistItem::find($validated['wishlist_item_id']);

        // Проверяем доступ к вишлисту
        if (!$this->canAccessWishlist($request->user(), $item->wishlist)) {
            abort(403);
        }

        // Нельзя резервировать свой подарок (опционально)
        if ($item->wishlist->user_id === $request->user()->id) {
            abort(403, 'Нельзя резервировать свой подарок');
        }

        // Проверяем, нет ли уже резерва
        if ($item->reservations()->where('user_id', $request->user()->id)->exists()) {
            abort(409, 'Уже зарезервировано');
        }

        $reservation = $item->reservations()->create([
            'user_id' => $request->user()->id,
            'status' => $validated['status'] ?? 'reserved',
            'note' => $validated['note'],
        ]);

        // Уведомление владельцу (пример)
        // $item->wishlist->user->notify(new ReservationNotification($reservation));

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
