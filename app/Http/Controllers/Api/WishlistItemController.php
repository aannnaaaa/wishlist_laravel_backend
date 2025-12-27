<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WishlistItem;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\Reservation;
use Illuminate\Http\Request;

class WishlistItemController extends Controller
{
    public function index(Request $request)
    {
        // берем все товары пользователя через связь с вишлистом
        $items = WishlistItem::whereHas('wishlist', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            // подгружаем бронь текущего пользователя
            ->with(['reservations' => function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            }])
            ->get();

        $result = $items->map(function ($item) {
            $reservation = $item->reservations->first();
            return [
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
                // информация о бронировании
                'is_reserved' => $reservation ? true : false,
                'reserved_by_user_id' => $reservation ? $reservation->user_id : null,
            ];
        });

        return response()->json([
            'data' => $result,
        ]);
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'wishlist_id' => 'required|exists:wishlists,id',
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric',
            'link' => 'nullable|url',
            'image' => 'nullable|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
        ]);

        // вишлист принадлежит пользвоателю текущему
        $wishlist = $request->user()->wishlists()->find($validated['wishlist_id']);
        if (!$wishlist) abort(403);

        $item = $wishlist->items()->create($validated);
        return response()->json($item, 201);
    }

    public function show(Request $request, WishlistItem $item)
    {
        if (!$this->canAccessWishlist($request->user(), $item->wishlist)) {
            abort(403);
        }

        $item->load([
            'reservations' => function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            }
        ]);

        $reservation = $item->reservations->first();

        return response()->json([
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

            'is_reserved' => $reservation !== null,
            'reserved_by_user_id' => $reservation?->user_id,
        ]);
    }


    public function update(Request $request, WishlistItem $item)
    {
        if ($item->wishlist->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'nullable|numeric',
            'link' => 'nullable|url',
            'image' => 'nullable|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'priority' => 'sometimes|in:low,medium,high',
        ]);

        $item->update($validated);
        return $item;
    }

    public function destroy(Request $request, WishlistItem $item)
    {
        if ($item->wishlist->user_id !== $request->user()->id) {
            abort(403);
        }

        $item->delete();
        return response()->json(['message' => 'Элемент удален']);
    }


    private function canAccessWishlist(User $currentUser, Wishlist $wishlist)
    {
        if ($wishlist->user_id === $currentUser->id) return true;
        if ($wishlist->privacy === 'public') return true;
        if ($wishlist->privacy === 'friends' && $currentUser->friends()->where('friend_id', $wishlist->user_id)->exists()) return true;
        return false;
    }
    public function reserve(Request $request, WishlistItem $item)
    {

        if ($item->reservations()->where('user_id', $request->user()->id)->exists()) {
            abort(409, 'Уже зарезервировано');
        }

        $reservation = $item->reservations()->create([
            'user_id' => $request->user()->id,
            'status' => 'reserved',
            'note' => $request->note ?? null,
        ]);

        // создаём уведомление владельцу вишлиста
        \App\Models\Notification::create([
            'user_id' => $item->wishlist->user_id,
            'type' => 'reservation',
            'message' => $request->user()->name . " забронировал подарок \"{$item->name}\"",
            'related_id' => $reservation->id,
            'is_read' => false,
        ]);

        return response()->json(['message' => 'Подарок забронирован'], 201);
    }

    public function unreserve(Request $request, WishlistItem $item)
    {
        $reservation = $item->reservations()->where('user_id', $request->user()->id)->first();

        if (!$reservation) {
            return response()->json(['message' => 'Бронь не найдена'], 404);
        }

        $reservation->delete();

        return response()->json(['message' => 'Бронь успешно отменена'], 200);
    }

}
