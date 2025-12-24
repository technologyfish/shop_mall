<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Public API routes (no authentication required)
$router->group(['prefix' => 'api'], function () use ($router) {
    // Auth routes
    $router->post('auth/register', 'Api\AuthController@register');
    $router->post('auth/login', 'Api\AuthController@login');
    $router->post('auth/forgot-password/send-code', 'Api\Auth\PasswordResetController@sendCode');
    $router->post('auth/forgot-password/verify-code', 'Api\Auth\PasswordResetController@verifyCode');
    $router->post('auth/forgot-password/reset', 'Api\Auth\PasswordResetController@resetPassword');

    // Products
    $router->get('products', 'Api\ProductController@index');
    $router->get('products/{id}', 'Api\ProductController@show');
    
    // Recipes
    $router->get('recipes', 'Api\RecipeController@index');
    $router->get('recipes/{id}', 'Api\RecipeController@show');
    $router->get('recipe-categories', 'Api\RecipeController@categories');
    
    // Articles
    $router->get('articles', 'Api\ArticleController@index');
    $router->get('articles/{id}', 'Api\ArticleController@show');
    
    // Banners
    $router->get('banners', 'Api\BannerController@index');
    
    // Photos
    $router->get('photos', 'Api\PhotoController@index');
    
    // Announcements
    $router->get('announcements', 'Api\AnnouncementController@index');
    
    // Contact
    $router->get('contact/info', 'Api\ContactController@getContactInfo');
    $router->post('contact/submit', 'Api\ContactController@submitForm');
    
    // Journeys (public)
    $router->get('journeys', 'Api\JourneyController@index');
    
    // Subscription Plans (public)
    $router->get('subscription-plans', 'Api\SubscriptionController@getPlans');
    
    // Shipping Settings (public)
    $router->get('shipping/settings', 'Api\ShippingController@getSettings');
    
    // Promotions
    $router->get('promotions/active', 'Api\PromotionController@getActivePromotion');
    
    // MailTransfer
    $router->post('mail-transfer/submit', 'Api\MailTransferController@submit');
    $router->post('mail-transfer/check', 'Api\MailTransferController@checkSubmission');
    
    // Off Code (首单优惠码)
    $router->post('off-code/collect', 'Api\OffCodeController@collect');
    $router->post('off-code/verify', 'Api\OffCodeController@verify');
    $router->get('off-code/promotion', 'Api\OffCodeController@getFirstOrderPromotion');
    $router->get('off-code/check-popup', 'Api\OffCodeController@checkShowPopup');
    
    // Stripe Webhook
    $router->post('stripe/webhook', 'Api\StripeWebhookController@handle');
    
    // Admin Login (no authentication required)
    $router->post('admin/login', 'Admin\AuthController@login');
});

// Protected API routes (authentication required)
$router->group(['prefix' => 'api', 'middleware' => 'auth'], function () use ($router) {
    // User
    $router->get('user', 'Api\UserController@show');
    $router->get('auth/me', 'Api\UserController@show'); // Alias for /api/user
    $router->put('user', 'Api\UserController@update');
    $router->put('auth/profile', 'Api\AuthController@updateProfile'); // Update profile
    $router->post('auth/avatar', 'Api\AuthController@updateAvatar'); // Update avatar
    $router->get('user/discount', 'Api\UserController@getDiscount');
    $router->post('auth/logout', 'Api\AuthController@logout');
    
    // Off Code - 已登录用户激活首单优惠
    $router->post('off-code/activate', 'Api\OffCodeController@activateForLoggedInUser');
    
    // Cart
    $router->get('cart', 'Api\CartController@index');
    $router->post('cart', 'Api\CartController@add');
    $router->delete('cart', 'Api\CartController@clear');
    $router->post('cart/delete-selected', 'Api\CartController@deleteSelected');
    $router->put('cart/select-all', 'Api\CartController@selectAll');  // 静态路由必须在动态路由前
    $router->put('cart/{id}', 'Api\CartController@update');
    $router->delete('cart/{id}', 'Api\CartController@remove');
    $router->put('cart/{id}/select', 'Api\CartController@toggleSelect');
    
    // Orders
    $router->get('orders', 'Api\OrderController@index');
    $router->post('orders', 'Api\OrderController@create');
    $router->get('orders/{id}', 'Api\OrderController@show');
    $router->put('orders/{id}/cancel', 'Api\OrderController@cancel');
    
    // Payments
    $router->post('payments/create', 'Api\PaymentController@create');
    $router->get('payments/{orderNo}/status', 'Api\PaymentController@status');
    
    // Addresses
    $router->get('addresses', 'Api\AddressController@index');
    $router->post('addresses', 'Api\AddressController@store');
    $router->put('addresses/{id}', 'Api\AddressController@update');
    $router->delete('addresses/{id}', 'Api\AddressController@delete');
    $router->put('addresses/{id}/default', 'Api\AddressController@setDefault');
    
    // Subscriptions
    $router->post('subscriptions/create', 'Api\SubscriptionController@create');
    $router->post('subscriptions/verify', 'Api\SubscriptionController@verify');
    $router->get('subscriptions', 'Api\SubscriptionController@index');
    $router->get('subscriptions/{id}', 'Api\SubscriptionController@show');
    $router->post('subscriptions/{id}/cancel', 'Api\SubscriptionController@cancel');
    $router->post('subscriptions/{id}/pause', 'Api\SubscriptionController@pause');
    $router->post('subscriptions/{id}/resume', 'Api\SubscriptionController@resume');
});

// Admin routes (authentication required)
$router->group(['prefix' => 'api/admin', 'middleware' => 'admin'], function () use ($router) {
    // Auth (authenticated endpoints only)
    $router->post('logout', 'Admin\AuthController@logout');
    $router->get('user', 'Admin\AuthController@user');
    $router->get('me', 'Admin\AuthController@me');
    
    // Dashboard
    $router->get('dashboard/stats', 'Admin\DashboardController@stats');
    
    // Users
    $router->get('users', 'Admin\UserController@index');
    $router->get('users/{id}', 'Admin\UserController@show');
    $router->put('users/{id}', 'Admin\UserController@update');
    $router->delete('users/{id}', 'Admin\UserController@destroy');
    
    // Products
    $router->get('products', 'Admin\ProductController@index');
    $router->post('products', 'Admin\ProductController@store');
    $router->get('products/{id}', 'Admin\ProductController@show');
    $router->put('products/{id}', 'Admin\ProductController@update');
    $router->put('products/{id}/status', 'Admin\ProductController@updateStatus');
    $router->delete('products/{id}', 'Admin\ProductController@delete');
    
    // Orders
    $router->get('orders', 'Admin\OrderController@index');
    $router->get('orders/export', 'Admin\OrderController@export');  // 静态路由必须在动态路由之前
    $router->get('orders/{id}', 'Admin\OrderController@show');
    $router->put('orders/{id}/ship', 'Admin\OrderController@ship');
    
    // Subscription Orders
    $router->get('subscription-orders', 'Admin\SubscriptionOrderController@index');
    $router->get('subscription-orders/{id}', 'Admin\SubscriptionOrderController@show');
    
    // Recipes
    $router->get('recipes', 'Admin\RecipeController@index');
    $router->post('recipes', 'Admin\RecipeController@store');
    $router->get('recipes/{id}', 'Admin\RecipeController@show');
    $router->put('recipes/{id}', 'Admin\RecipeController@update');
    $router->delete('recipes/{id}', 'Admin\RecipeController@destroy');
    
    // Recipe Categories
    $router->get('recipe-categories', 'Admin\RecipeCategoryController@index');
    $router->post('recipe-categories', 'Admin\RecipeCategoryController@store');
    $router->put('recipe-categories/{id}', 'Admin\RecipeCategoryController@update');
    $router->delete('recipe-categories/{id}', 'Admin\RecipeCategoryController@destroy');
    
    // Articles
    $router->get('articles', 'Admin\ArticleController@index');
    $router->post('articles', 'Admin\ArticleController@store');
    $router->get('articles/{id}', 'Admin\ArticleController@show');
    $router->put('articles/{id}', 'Admin\ArticleController@update');
    $router->delete('articles/{id}', 'Admin\ArticleController@destroy');
    
    // Banners
    $router->get('banners', 'Admin\BannerController@index');
    $router->post('banners', 'Admin\BannerController@store');
    $router->get('banners/{id}', 'Admin\BannerController@show');
    $router->put('banners/{id}', 'Admin\BannerController@update');
    $router->delete('banners/{id}', 'Admin\BannerController@delete');
    
    // Photos
    $router->get('photos', 'Admin\PhotoController@index');
    $router->post('photos', 'Admin\PhotoController@store');
    $router->put('photos/{id}', 'Admin\PhotoController@update');
    $router->delete('photos/{id}', 'Admin\PhotoController@destroy');
    
    // Contact Info
    $router->get('contact/info', 'Admin\ContactController@getContactInfo');
    $router->put('contact/info', 'Admin\ContactController@updateContactInfo');
    
    // Contact Forms
    $router->get('contact/forms', 'Admin\ContactController@getForms');
    $router->get('contact/forms/{id}', 'Admin\ContactController@getForm');
    $router->put('contact/forms/{id}/status', 'Admin\ContactController@updateFormStatus');
    $router->delete('contact/forms/{id}', 'Admin\ContactController@deleteForm');
    
    // Contacts
    $router->get('contacts', 'Admin\ContactController@index');
    $router->get('contacts/{id}', 'Admin\ContactController@show');
    $router->put('contacts/{id}/status', 'Admin\ContactController@updateStatus');
    $router->delete('contacts/{id}', 'Admin\ContactController@destroy');
    
    // Contact Submissions
    $router->get('contact-submissions', 'Admin\ContactController@submissions');
    $router->get('contact-submissions/{id}', 'Admin\ContactController@showSubmission');
    $router->put('contact-submissions/{id}/status', 'Admin\ContactController@updateSubmissionStatus');
    $router->delete('contact-submissions/{id}', 'Admin\ContactController@destroySubmission');
    
    // MailTransfer Submissions
    $router->get('mail-transfer/forms', 'Admin\MailTransferController@getForms');
    $router->get('mail-transfer/forms/{id}', 'Admin\MailTransferController@getForm');
    $router->delete('mail-transfer/forms/{id}', 'Admin\MailTransferController@deleteForm');
    
    // Email Tasks
    $router->get('email-tasks', 'Admin\EmailTaskController@index');
    $router->post('email-tasks', 'Admin\EmailTaskController@store');
    $router->put('email-tasks/{id}', 'Admin\EmailTaskController@update');
    $router->delete('email-tasks/{id}', 'Admin\EmailTaskController@destroy');
    
    // Subscription Plans
    $router->get('subscription-plans', 'Admin\SubscriptionPlanController@index');
    $router->post('subscription-plans', 'Admin\SubscriptionPlanController@store');
    $router->get('subscription-plans/{id}', 'Admin\SubscriptionPlanController@show');
    $router->put('subscription-plans/{id}', 'Admin\SubscriptionPlanController@update');
    $router->delete('subscription-plans/{id}', 'Admin\SubscriptionPlanController@destroy');
    
    // Promotions
    $router->get('promotions', 'Admin\PromotionController@index');
    $router->post('promotions', 'Admin\PromotionController@store');
    $router->get('promotions/{id}', 'Admin\PromotionController@show');
    $router->put('promotions/{id}', 'Admin\PromotionController@update');
    $router->delete('promotions/{id}', 'Admin\PromotionController@destroy');
    
    // Announcements
    $router->get('announcements', 'Admin\AnnouncementController@index');
    $router->post('announcements', 'Admin\AnnouncementController@store');
    $router->get('announcements/{id}', 'Admin\AnnouncementController@show');
    $router->put('announcements/{id}', 'Admin\AnnouncementController@update');
    $router->delete('announcements/{id}', 'Admin\AnnouncementController@destroy');
    
    // Journeys
    $router->get('journeys', 'Admin\JourneyController@index');
    $router->post('journeys', 'Admin\JourneyController@store');
    $router->put('journeys/{id}', 'Admin\JourneyController@update');
    $router->delete('journeys/{id}', 'Admin\JourneyController@delete');
    
    // Shipping Settings
    $router->get('shipping-settings', 'Admin\ShippingSettingController@index');
    $router->put('shipping-settings', 'Admin\ShippingSettingController@update');
    
    // Upload
    $router->post('upload', 'Admin\UploadController@upload');
});
