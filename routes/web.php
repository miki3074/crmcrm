<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


use App\Models\Subproject;
use App\Models\User;

use App\Http\Controllers\Auth\NewPasswordController;
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

Route::get('/dashboardold', function () {
    return Inertia::render('Dashboardold');
})->middleware(['auth', 'verified'])->name('dashboardold');





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



Route::middleware(['auth'])->get('/subprojects/{id}', function ($id) {
    $subproject = Subproject::with([
        'responsible:id,name',
        'tasks.executor:id,name',
        'tasks.responsible:id,name',
    ])->findOrFail($id);

    // Кого можно назначать — например, всех пользователей компании проекта
    $users = User::where('company_id', $subproject->project->company_id ?? null)
        ->get(['id','name']);

    return Inertia::render('Subprojects/Show', [
        'subproject' => $subproject,
        'users'      => $users,
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


Route::middleware(['auth','verified'])->group(function () {
    Route::get('/clients', fn() => Inertia::render('Clients/Index'))->name('clients.index');
    Route::get('/clients/{id}', fn($id) => Inertia::render('Clients/Show', ['id' => (int)$id]))->name('clients.show');
});


Route::middleware(['auth','verified'])->group(function () {
    Route::get('/file-storage', fn() => Inertia::render('Storage/Index'))
        ->name('storage.index');

    Route::get('/file-storage/companies/{id}', fn($id) =>
        Inertia::render('Storage/Company', ['id' => (int)$id])
    )->name('storage.company');
});


Route::middleware(['auth', 'verified'])->group(function () {
    // Страница визуальной схемы (Draw.io-подобная)
    Route::get('/mapdiagram', function () {
        return Inertia::render('CompanyDiagram');
    })->name('mapdiagram');
});




Route::middleware(['auth', 'verified'])->get('/users', function () {
    abort_unless(auth()->user()->email === 'miki23074@gmail.com', 403);
    return Inertia::render('Users/Index');
})->name('users.index');



Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');


require __DIR__.'/auth.php';
