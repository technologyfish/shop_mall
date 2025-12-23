<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * 成功响应
     */
    protected function success($data = null, $message = 'Success', $httpCode = 200)
    {
        return response()->json([
            'code' => 0,
            'message' => $message,
            'data' => $data
        ], $httpCode);
    }

    /**
     * 失败响应
     */
    protected function error($message = 'Error', $httpCode = 400, $data = null)
    {
        return response()->json([
            'code' => $httpCode,
            'message' => $message,
            'data' => $data
        ], $httpCode);
    }

    /**
     * 分页响应
     */
    protected function paginate($paginator)
    {
        return response()->json([
            'code' => 0,
            'message' => 'Success',
            'data' => [
                'data' => $paginator->items(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage()
            ]
        ]);
    }
}


