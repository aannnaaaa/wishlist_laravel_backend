<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function index(Request $request)
    {
        $friendships = Friendship::where('user_id', $request->user()->id)
            ->where('status', 'accepted')
            ->with('friend')
            ->get();

        return $friendships->map(function($f) {
            return [
                'id' => $f->id,
                'user_id' => $f->user_id,
                'friend_id' => $f->friend_id,
                'status' => $f->status,
                'created_at' => $f->created_at,
                'updated_at' => $f->updated_at,
                // Если друга нет — fallback
                'friend_name' => $f->friend ? $f->friend->name : 'Пользователь',
                'friend_email' => $f->friend ? $f->friend->email : null,
                'friend_avatar' => $f->friend ? $f->friend->avatar : null,
            ];
        });
    }


    public function pending(Request $request)
    {
        $friendships = Friendship::where('friend_id', $request->user()->id)
            ->where('status', 'pending')
            ->with('user')
            ->get();

        return $friendships->map(function($f) {
            return [
                'id' => $f->id,
                'user_id' => $f->user_id,
                'friend_id' => $f->friend_id,
                'status' => $f->status,
                'created_at' => $f->created_at,
                'updated_at' => $f->updated_at,
                'friend_name' => $f->user->name,
                'friend_email' => $f->user->email,
                'friend_avatar' => $f->user->avatar,
            ];
        });
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'friend_id' => 'required|exists:users,id|different:' . $request->user()->id,
        ]);

        $existing = Friendship::where(function ($q) use ($request, $validated) {
            $q->where('user_id', $request->user()->id)
                ->where('friend_id', $validated['friend_id']);
        })->orWhere(function ($q) use ($request, $validated) {
            $q->where('user_id', $validated['friend_id'])
                ->where('friend_id', $request->user()->id);
        })->first();

        if ($existing) {
            // Если уже существует, просто обновляем статус на pending
            $existing->update(['status' => 'pending', 'updated_at' => now()]);
            $friendship = $existing;
        } else {
            // Если нет, создаем новую запись
            $friendship = Friendship::create([
                'user_id' => $request->user()->id,
                'friend_id' => $validated['friend_id'],
                'status' => 'pending',
            ]);
        }

        // Создаем уведомление для получателя
        \App\Models\Notification::create([
            'user_id' => $validated['friend_id'],
            'type' => 'friend_request',
            'message' => $request->user()->name . ' отправил(а) запрос в друзья',
            'related_id' => $friendship->id,
            'is_read' => false,
        ]);

        return $friendship;
    }



    public function update(Request $request, Friendship $friendship)
    {
        // Проверяем, что текущий пользователь — тот, кому пришла заявка
        if ($friendship->friend_id !== $request->user()->id) {
            abort(403, 'Нет доступа');
        }

        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected',
        ]);

        $friendship->update(['status' => $validated['status']]);

        $message = $validated['status'] === 'accepted'
            ? 'принял(а) ваш запрос в друзья'
            : 'отклонил(а) ваш запрос в друзья';

        // Создаём уведомление для отправителя
        Notification::create([
            'user_id' => $friendship->user_id,
            'type' => 'friend_response',
            'message' => $request->user()->name . ' ' . $message,
            'related_id' => $friendship->id,
            'is_read' => false,
        ]);

        // если запрос принят — создаём зеркальную запись дружбы
        if ($validated['status'] === 'accepted') {
            \App\Models\Friendship::firstOrCreate(
                [
                    'user_id' => $friendship->friend_id,
                    'friend_id' => $friendship->user_id,
                ],
                [
                    'status' => 'accepted',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return $friendship;
    }


    public function destroy(Request $request, Friendship $friendship)
    {
        $currentUserId = $request->user()->id;

        if ($friendship->user_id !== $currentUserId && $friendship->friend_id !== $currentUserId) {
            abort(403);
        }

        $user1 = min($friendship->user_id, $friendship->friend_id);
        $user2 = max($friendship->user_id, $friendship->friend_id);

        Friendship::where(function ($query) use ($user1, $user2) {
            $query->where('user_id', $user1)->where('friend_id', $user2);
        })->orWhere(function ($query) use ($user1, $user2) {
            $query->where('user_id', $user2)->where('friend_id', $user1);
        })->delete();

        return response()->json(['message' => 'Дружба удалена']);
    }
}
