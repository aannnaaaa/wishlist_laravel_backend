<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

class WishlistItemController extends Controller
{
    public function index(Request $request)
    {
        // Все элементы текущего пользователя (или по вишлисту)
        return WishlistItem::whereHas('wishlist', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->get();
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

        // Проверяем, что вишлист принадлежит пользователю
        $wishlist = $request->user()->wishlists()->find($validated['wishlist_id']);
        if (!$wishlist) abort(403);

        $item = $wishlist->items()->create($validated);
        return response()->json($item, 201);
    }

    public function show(Request $request, WishlistItem $wishlistItem)
    {
        if ($this->canAccessWishlist($request->user(), $wishlistItem->wishlist)) {
            return $wishlistItem;
        }
        abort(403);
    }

    public function update(Request $request, WishlistItem $wishlistItem)
    {
        if ($wishlistItem->wishlist->user_id !== $request->user()->id) {
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

        $wishlistItem->update($validated);
        return $wishlistItem;
    }

    public function destroy(Request $request, WishlistItem $wishlistItem)
    {
        if ($wishlistItem->wishlist->user_id !== $request->user()->id) {
            abort(403);
        }

        $wishlistItem->delete();
        return response()->json(['message' => 'Элемент удален']);
    }

    private function canAccessWishlist(User $currentUser, Wishlist $wishlist)
    {
        // Повторяю функцию из WishlistController для удобства (можно вынести в trait)
        if ($wishlist->user_id === $currentUser->id) return true;
        if ($wishlist->privacy === 'public') return true;
        if ($wishlist->privacy === 'friends' && $currentUser->friends()->where('friend_id', $wishlist->user_id)->exists()) return true;
        return false;
    }
}
