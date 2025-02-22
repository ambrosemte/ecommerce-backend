<?php

namespace App\Trait;

use App\Helpers\Response;
use App\Http\Resources\UserResource;
use App\Models\User;

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

        $data = [
            "user" => new UserResource(User::find($user->id)),
            "token" => $user->createToken("auth")->plainTextToken,
        ];
        return Response::success(message: "Registration successful", data: $data);
    }
}
