<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

// Route publique pour les invitations de guilde
Route::get('/invite/{code}', function ($code) {
    // ⭐ UTILISER L'URL FRONTEND DEPUIS LE .ENV
    $frontUrl = env('FRONT_URL', 'http://127.0.0.1:4200');
    return redirect("{$frontUrl}/join-guild/{$code}");
})->name('guild.invite.redirect');