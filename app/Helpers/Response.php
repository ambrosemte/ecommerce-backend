<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class Response
{
    public static function success(int $statusCode = 200, string $message = "Operation successful", array $data = []): JsonResponse
    {
        return response()->json(["success" => true, "message" => $message, "data" => $data], status: $statusCode);
    }

    public static function error(int $statusCode = 400, string $message = "An error occured", array $data = []): JsonResponse
    {
        return response()->json(["success" => false, "message" => $message, "data" => $data], status: $statusCode);
    }

    public static function notFound(int $statusCode = 404, string $message = "Not found"): JsonResponse
    {
        return response()->json(["success" => false, "message" => $message], status: $statusCode);
    }
}
