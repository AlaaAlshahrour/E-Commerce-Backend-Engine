<?php

namespace App\Helpers;
use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    public static function jsonResponse($data = null, string $message = '', int $statusCode = 200, bool $successful = true, int $pageCount = null, int $userCount = null): JsonResponse
    {
        $responseData = [
            'successful' => $successful,
            'message' => $message,
            'data' => $data,
            'page_count' => $pageCount,
            'user_count' => $userCount,
            'status_code' => $statusCode,
        ];

        if (is_null($data) || (is_array($data) && empty($data))) {
            unset($responseData['data']);
        }

        if (is_null($pageCount)) {
            unset($responseData['page_count']);
        }

        if (is_null($userCount)) {
            unset($responseData['user_count']);
        }
        return response()->json($responseData, $statusCode);
    }
}

