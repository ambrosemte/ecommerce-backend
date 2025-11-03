<?php

namespace App\Trait;

use App\Enums\RoleEnum;
use App\Helpers\Response;
use App\Http\Resources\UserResource;
use App\Models\User;
use Spatie\Permission\Contracts\Role;

trait AuthTrait
{
    public static function login(string $userId)
    {
        $user = User::find($userId);
        $data = [
            "user" => new UserResource($user),
            "token" => $user->createToken("auth")->plainTextToken,
        ];

        return Response::success(message: "Login successful", data: $data);
    }

    public static function register(array $registerData)
    {
        $user = User::create($registerData);
        $user->wallet()->create();
        $user->assignRole(RoleEnum::USER);

        $data = [
            "user" => new UserResource(User::find($user->id)),
            "token" => $user->createToken("auth")->plainTextToken,
        ];
        return Response::success(message: "Registration successful", data: $data);
    }
}
