<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\AdminController;

use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('/admin', function () {
    return redirect()->route('admin.login');
});
Route::get('/employee', [ImportController::class, 'import'])->name('employee');
Route::post('/import-users', [ImportController::class, 'importUsers'])->name('users.import');

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    Route::get('/video-index', [VideoController::class, 'index'])->name('video.index');
    Route::post('/video-store', [VideoController::class, 'store'])->name('video.store');
    // Banner download temporarily disabled.
    // Route::get('/banner/{id}/download', [VideoController::class, 'downloadBanner'])->name('banner.download');
    Route::get('/video/{id}/download',  [VideoController::class, 'downloadVideo'])->name('video.download');

});
Route::prefix('admin')->group(function () {
    Route::get('/login',  [AdminController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout',[AdminController::class, 'logout'])->name('admin.logout');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard',       [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/doctor',         [AdminController::class, 'doctor'])->name('doctors.index');
    Route::get('/doctor/export',  [AdminController::class, 'doctor_export'])->name('doctors.export');
    Route::post('doctor/destroy/{id}', [AdminController::class, 'doctor_destroy'])->name('doctors.destroy');

    Route::get('/employee',         [AdminController::class, 'employee'])->name('employees.index');
    Route::get('/employee/export',  [AdminController::class, 'employee_export'])->name('employees.export');
    Route::post('employee/destroy/{id}', [AdminController::class, 'employee_destroy'])->name('employees.destroy');
});
