<?php

use App\Http\Controllers\AdminInviteController;
use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ChatbotLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SupportRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC USER ROUTES (KNOWURLOCAL)
|--------------------------------------------------------------------------
*/

// Landing → login page
Route::redirect('/', '/login-page');

/*
|--------------------------------------------------------------------------
| AUTH (PUBLIC USERS)
|--------------------------------------------------------------------------
*/

Route::get('/login-page', function () {
    return view('public_user.login-page');
})->name('public.login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

// Register (OTP-based)
Route::get('/register', function () {
    return view('public_user.login-page');
})->name('public.register');

Route::post('/register', [AuthController::class, 'register'])
    ->name('public.register.submit');

// OTP
Route::get('/otp', function () {
    return view('otp');
})->name('otp.page');

Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
    ->name('otp.verify');

/*
|--------------------------------------------------------------------------
| STATIC + PUBLIC PAGES
|--------------------------------------------------------------------------
*/
Route::get('/admin/dashboard/export', [DashboardController::class, 'exportPdf']);

Route::view('/privacy', 'privacy');
Route::view('/terms', 'terms');

// Route::get('/map', [PageController::class, 'map'])->name('map');
// Route::get('/chat', fn() => view('public_user.chatbot'))->name('chat');
// Route::get('/chat/suggestions', [ChatbotController::class, 'suggestions']);
// Route::get('/agencies', [PageController::class, 'agencies'])->name('agencies');
// Route::get('/about', fn() => view('public_user.about'))->name('about');

// Route::get('/agency/{id}', [AgencyController::class, 'show'])->name('agency.show');
// Route::get('/navigate/{agency}', [AgencyController::class, 'navigate'])->name('navigate');

// // API
// Route::get('/api/agencies', [AgencyController::class, 'getAll']);

// // Chatbot
// Route::post('/chat', [ChatbotController::class, 'ask']);
// Route::post('/chat/support', [ChatbotController::class, 'submitSupportRequest']);

// // FAQ
// Route::resource('faqs', FaqController::class);



Route::middleware(['auth', 'no.cache'])->group(function () {

    Route::get('/map', [PageController::class, 'map'])->name('map');

    Route::get('/chat', fn() => view('public_user.chatbot'))->name('chat');
    Route::get('/chat/suggestions', [ChatbotController::class, 'suggestions']);

    Route::get('/agencies', [PageController::class, 'agencies'])->name('agencies');
    Route::get('/about', fn() => view('public_user.about'))->name('about');

    Route::get('/agency/{id}', [AgencyController::class, 'show'])->name('agency.show');
    Route::get('/navigate/{agency}', [AgencyController::class, 'navigate'])->name('navigate');

    // API
    Route::get('/api/agencies', [AgencyController::class, 'getAll']);

    // Chatbot
    Route::post('/chat', [ChatbotController::class, 'ask']);
    Route::post('/chat/support', [ChatbotController::class, 'submitSupportRequest']);

    // FAQ (IMPORTANT — protect this too)
    Route::resource('faqs', FaqController::class);

    // 🔥 YOUR NEW FEATURE
    Route::get('/my-inquiries', [SupportRequestController::class, 'userIndex'])
        ->name('user.inquiries');

});
/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (UNIFIED AUTH + INVITE SYSTEM)
|--------------------------------------------------------------------------
*/

// Shortcut
Route::redirect('/admin-login', '/admin/login');

Route::prefix('admin')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ADMIN ACCESS
    |--------------------------------------------------------------------------
    */

    Route::get('/login', function () {

        if (auth()->check()) {
            return redirect('/admin/dashboard');
        }

        return view('admin.login-page');

    })->name('admin.login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('admin.login.submit');

    /*
    |--------------------------------------------------------------------------
    | ADMIN REGISTRATION (TOKEN-BASED)
    |--------------------------------------------------------------------------
    */

    // 🔐 MUST have token
    Route::get('/register', [AdminInviteController::class, 'validateInvite'])
        ->name('admin.register.page');

    // 🔐 Still uses AuthController but will validate token later
    Route::post('/register', [AuthController::class, 'register'])
        ->name('admin.register');


    /*
|--------------------------------------------------------------------------
| PROTECTED ADMIN ROUTES (SECURED)
|--------------------------------------------------------------------------
*/

// ================= ADMIN + SUPERADMIN =================
Route::middleware(['auth', 'admin.only', 'no.cache'])->group(function () {

    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('admin.dashboard');

    // LOGS (we will restrict data later)
    Route::get('/logs', [UserLogController::class, 'index'])
        ->name('admin.logs');

    // NGA MANAGEMENT
    Route::get('/nga-management', [AgencyController::class, 'index'])
        ->name('admin.nga');

    Route::post('/agencies', [AgencyController::class, 'store'])
        ->name('agencies.store');

    Route::put('/agencies/{agency}', [AgencyController::class, 'update'])
        ->name('admin.agencies.update');

    Route::delete('/agencies/{agency}', [AgencyController::class, 'destroy'])
        ->name('admin.agencies.destroy');

    Route::get('/admin/users', [UserController::class, 'index'])
        ->name('admin.users');

    Route::get('/admin/support-requests', [SupportRequestController::class, 'index'])
        ->name('admin.support.requests');

    Route::post('/admin/support-requests/reply', [SupportRequestController::class, 'reply'])
        ->name('admin.support.reply');

    Route::put('/support-requests/{id}', [SupportRequestController::class, 'update'])
        ->name('admin.support.update'); 

    // CHATBOT LOGS
    Route::get('/chatbot-logs', [ChatbotLogController::class, 'index'])
        ->name('admin.chatbot.logs');

    

    // LOGOUT
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('admin.logout');

});


// ================= SUPERADMIN ONLY =================
Route::middleware(['auth', 'superadmin.only', 'no.cache'])->group(function () {

    // ADMIN MANAGEMENT PAGE
    Route::get('/admins', [AdminManagementController::class, 'admins'])
        ->name('admin.admins');

    // APPROVAL
    Route::post('/admins/approve/{id}', [AdminManagementController::class, 'approve'])
        ->name('admin.approve');

    // (future actions)
    Route::post('/admins/promote/{id}', [AdminManagementController::class, 'promote'])
        ->name('admin.promote');

    Route::post('/admins/demote/{id}', [AdminManagementController::class, 'demote'])
        ->name('admin.demote');

    Route::delete('/admins/delete/{id}', [AdminManagementController::class, 'delete'])
        ->name('admin.delete');

    Route::delete('/support-requests/{id}', [SupportRequestController::class, 'destroy'])
        ->name('admin.support.delete');

    Route::post('/support-requests/{id}/to-faq', [SupportRequestController::class, 'toFaq'])
        ->name('admin.support.toFaq');

    // INVITE SYSTEM
    Route::post('/invite', [AdminInviteController::class, 'sendInvite'])
        ->name('admin.invite');

    Route::delete('/admin/users/{id}', [UserController::class, 'destroy'])
        ->name('admin.users.delete');

});
});