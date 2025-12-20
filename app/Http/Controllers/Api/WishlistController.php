<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\User; // ✅ ДОБАВЛЕН ИМПОРТ
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $wishlists = $request->user()->wishlists;
        // ЕДИНЫЙ ФОРМАТ ОТВЕТА
        return response()->json(['data' => $wishlists]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'required|in:public,friends,private',
        ]);

        $wishlist = $request->user()->wishlists()->create($validated);
        return response()->json($wishlist, 201);
    }

    public function show(Request $request, Wishlist $wishlist)
    {
        if ($this->canAccessWishlist($request->user(), $wishlist)) {
            return response()->json($wishlist->load('items'));
        }
        abort(403, 'Доступ запрещен');
    }

    public function update(Request $request, Wishlist $wishlist)
    {
        if ($wishlist->user_id !== $request->user()->id) {
            abort(403, 'Можно редактировать только свои вишлисты');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'sometimes|in:public,friends,private',
        ]);

        $wishlist->update($validated);
        return response()->json($wishlist);
    }

    public function destroy(Request $request, Wishlist $wishlist)
    {
        if ($wishlist->user_id !== $request->user()->id) {
            abort(403, 'Можно удалять только свои вишлисты');
        }

        $wishlist->delete();
        return response()->json(['message' => 'Вишлист удален']);
    }

    private function canAccessWishlist(User $currentUser, Wishlist $wishlist)
    {
        if ($wishlist->user_id === $currentUser->id) return true;
        if ($wishlist->privacy === 'public') return true;
        if ($wishlist->privacy === 'friends' && $currentUser->friends()->where('friend_id', $wishlist->user_id)->exists()) return true;
        return false;
    }
}
