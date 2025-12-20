<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->friends;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'friend_id' => 'required|exists:users,id|not_in:' . $request->user()->id,
        ]);

        // Проверяем, нет ли уже запроса
        if (Friendship::where('user_id', $request->user()->id)->where('friend_id', $validated['friend_id'])->exists()) {
            abort(409, 'Запрос уже отправлен');
        }

        $friendship = Friendship::create([
            'user_id' => $request->user()->id,
            'friend_id' => $validated['friend_id'],
            'status' => 'pending',
        ]);

        // Отправить уведомление (пример)
        // $friend = User::find($validated['friend_id']);
        // $friend->notify(new FriendRequestNotification($request->user()));

        return response()->json($friendship, 201);
    }

    public function update(Request $request, Friendship $friendship)
    {
        if ($friendship->friend_id !== $request->user()->id) {
            abort(403, 'Можно отвечать только на свои запросы');
        }

        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected',
        ]);

        $friendship->update($validated);
        return $friendship;
    }

    public function destroy(Request $request, Friendship $friendship)
    {
        if ($friendship->user_id !== $request->user()->id && $friendship->friend_id !== $request->user()->id) {
            abort(403);
        }

        $friendship->delete();
        return response()->json(['message' => 'Дружба удалена']);
    }
}
