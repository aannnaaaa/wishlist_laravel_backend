<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Только для админа или поиск, но для простоты - все
        return User::paginate(10);
    }

    public function show(Request $request, User $user)
    {
        // Показываем профиль, если публичный или друг
        if ($user->id === $request->user()->id || $request->user()->friends()->where('friend_id', $user->id)->exists()) {
            return $user;
        }
        abort(403, 'Доступ запрещен');
    }

    public function update(Request $request, User $user)
    {
        if ($user->id !== $request->user()->id) {
            abort(403, 'Можно редактировать только свой профиль');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'avatar' => 'nullable|string',
            'age' => 'nullable|integer',
            'height' => 'nullable|integer',
            'clothing_size' => 'nullable|string',
            'shoe_size' => 'nullable|integer',
            'bio' => 'nullable|string',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        return $user;
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id !== $request->user()->id) {
            abort(403, 'Можно удалять только свой профиль');
        }

        $user->delete();
        return response()->json(['message' => 'Профиль удален']);
    }
}
