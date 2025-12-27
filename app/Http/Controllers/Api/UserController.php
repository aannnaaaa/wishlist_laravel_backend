<?php

namespace App\Http\Controllers\Api;
use App\Models\Friendship;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        //список всех + пагинация
        return User::paginate(10);
    }

    public function show(Request $request, User $user)
    {
        // свой профиль всегда доступен
        if ($user->id === $request->user()->id) {
            return $user;
        }

        // проверяем, есть ли принятая дружба в любую сторону
        $isFriend = Friendship::where(function ($q) use ($request, $user) {
            $q->where('user_id', $request->user()->id)
                ->where('friend_id', $user->id);
        })->orWhere(function ($q) use ($request, $user) {
            $q->where('user_id', $user->id)
                ->where('friend_id', $request->user()->id);
        })->where('status', 'accepted')->exists();

        if (!$isFriend) {
            abort(403, 'Доступ запрещен');
        }

        return $user;
    }

    public function update(Request $request)
    {
        $user = $request->user(); // текущий пользователь

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'avatar' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:5120',
            'age' => 'nullable|integer',
            'height' => 'nullable|integer',
            'clothing_size' => 'nullable|string',
            'shoe_size' => 'nullable|integer',
            'bio' => 'nullable|string',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // обработка аватарки
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

            if (!file_exists(public_path('avatars'))) {
                mkdir(public_path('avatars'), 0755, true);
            }

            $file->move(public_path('avatars'), $filename);

            $validated['avatar'] = url('avatars/' . $filename);
        }


        $user->update($validated);

        return response()->json(['user' => $user]);
    }



    public function destroy(Request $request, User $user)
    {
        if ($user->id !== $request->user()->id) {
            abort(403, 'Можно удалять только свой профиль');
        }

        $user->delete();
        return response()->json(['message' => 'Профиль удален']);
    }

    public function search(Request $request)
    {
        $query = $request->query('query');

        if (empty($query)) {
            return response()->json(['data' => []]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->where('id', '!=', $request->user()->id)
            ->select('id', 'name', 'email', 'avatar', 'bio')
            ->limit(20)
            ->get();

        return response()->json(['data' => $users]);
    }

    public function friendProfile(Request $request, User $user)
    {
        // проверяем, что пользователь запрашивает профиль своего друга
        $isFriend = Friendship::where(function($q) use ($request, $user) {
            $q->where('user_id', $request->user()->id)
                ->where('friend_id', $user->id);
        })->orWhere(function($q) use ($request, $user) {
            $q->where('user_id', $user->id)
                ->where('friend_id', $request->user()->id);
        })->where('status', 'accepted')->exists();

        if (!$isFriend) {
            abort(403, 'Доступ запрещен: это не ваш друг');
        }

        // загружаем вишлисты
        $wishlists = $user->wishlists()->get();

        return response()->json([
            'user' => $user,
            'wishlists' => $wishlists
        ]);
    }

}
