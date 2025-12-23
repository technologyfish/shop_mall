<?php

/** @var \Laravel\Lumen\Routing\Router $router */

// 最简单的测试路由
$router->get('/', function () use ($router) {
    return response()->json([
        'message' => 'API Root works!'
    ]);
});

$router->get('/test', function () use ($router) {
    return response()->json([
        'message' => 'Test route works!',
        'timestamp' => time()
    ]);
});

$router->get('/api/test', function () use ($router) {
    return response()->json([
        'code' => 200,
        'message' => 'API test works!',
        'data' => [
            'version' => '1.0.0',
            'time' => date('Y-m-d H:i:s')
        ]
    ]);
});

// 测试数据库连接
$router->get('/api/db-test', function () use ($router) {
    try {
        $pdo = app('db')->connection()->getPdo();
        return response()->json([
            'code' => 200,
            'message' => 'Database connected!',
            'data' => [
                'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)
            ]
        ]);
    } catch (Exception $e) {
        return response()->json([
            'code' => 500,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
});

// 测试Category模型
$router->get('/api/model-test', function () use ($router) {
    try {
        $categories = \App\Models\Category::all();
        return response()->json([
            'code' => 200,
            'message' => 'Model works!',
            'data' => $categories
        ]);
    } catch (Exception $e) {
        return response()->json([
            'code' => 500,
            'message' => 'Model error: ' . $e->getMessage()
        ]);
    }
});

// 用户端API路由
$router->group(['prefix' => 'api', 'namespace' => 'Api'], function () use ($router) {
    // 公开路由
    $router->get('categories', 'CategoryController@index');
    $router->get('products', 'ProductController@index');
});







