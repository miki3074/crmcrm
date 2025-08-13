<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/companies/{id}', function ($id) {
    return Inertia::render('Companies/Show', [
        'id' => $id,
    ]);
})->middleware(['auth', 'verified']);


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/employees', function () {
        return Inertia::render('Employees'); // или 'Employees/Index' если ты положишь в папку
    });
});

Route::middleware(['auth'])->get('/projects/{id}', function ($id) {
    return Inertia::render('Projects/Show', [
        'id' => $id,
    ]);
});

Route::get('/tasks/{id}', function ($id) {
    return Inertia::render('Tasks/Show', ['id' => (int)$id]);
})->middleware(['auth']);

Route::get('/subtasks/{id}', function ($id) {
    return Inertia::render('Subtasks/Show', ['id' => (int)$id]);
})->middleware(['auth']);

Route::middleware(['auth', 'verified'])->get('/calendar', function () {
    return Inertia::render('Calendar/Index');
});

require __DIR__.'/auth.php';
