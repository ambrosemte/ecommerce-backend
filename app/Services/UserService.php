<?php
namespace App\Services;

use App\Enums\SessionKey;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class UserService
{

    public function updateProfileImage(array $data)
    {
        $imagePath = request()->file('image')->store("profile-images", "public");

        $oldImagePath = Auth::user()->image_url;
        if ($oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        User::where('id', Auth::id())->update(['image_url' => $imagePath]);
    }

    public function updateProfile(array $data)
    {
        $user = User::find(Auth::id());
        $user->update(["phone" => $data['phone']]);

    }

    public function setPreferredCurrency(array $data)
    {
        $user = User::find(Auth::id());
        $user->update(["preferred_currency" => $data['currency']]);

    }

    public function updateFirebaseToken(array $data)
    {
        if (auth('sanctum')->check()) {
            $user = User::find(auth('sanctum')->id());
            $user->update(['firebase_token' => $data['token']]);
        } else {
            $guestId = request()->input('guest_id');

            $allTokens = Cache::get(SessionKey::FirebaseToken->value, []);

            $allTokens[$guestId] = $data['token'];

            Cache::put(SessionKey::FirebaseToken->value, $allTokens, now()->addDays(30));
        }


    }

    public function syncFromGuest(string $userId, string $guestId)
    {
        $allTokens = Cache::get(SessionKey::FirebaseToken->value, []);

        // Check if the guest token exists
        if (isset($allTokens[$guestId])) {
            $token = $allTokens[$guestId];

            unset($allTokens[$guestId]);

            Cache::put(SessionKey::FirebaseToken->value, $allTokens, now()->addDays(30));

            $user = User::find($userId);

            $user->update(['firebase_token' => $token]);
        }

    }

    public function getUsers()
    {
        $users = User::select(['name', 'email', 'phone'])
            ->latest()
            ->paginate(15)
            ->toArray();

        return $users;
    }
}
