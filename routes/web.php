<?php

use App\Http\Controllers\API\CompletedTasksController;
use App\Http\Controllers\API\SubtaskController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


use App\Models\Subproject;
use App\Models\User;

use App\Http\Controllers\Auth\NewPasswordController;

use App\Http\Controllers\Support\AdminSupportController;
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

Route::middleware('auth')->group(function () {
    Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');

    Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');

    Route::get('/meetings', [App\Http\Controllers\MeetingController::class, 'index'])->name('meetings.index');

    Route::get('/meetings/{meeting}', [App\Http\Controllers\MeetingController::class, 'show'])
        ->name('meetings.show');

    Route::put('/meetings/{meeting}/participation', [App\Http\Controllers\MeetingController::class, 'updateParticipation'])
        ->name('meetings.participation.update');

    Route::put('/meetings/{meeting}/status', [App\Http\Controllers\MeetingController::class, 'updateStatus'])
        ->name('meetings.status.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Маршруты восстановления
    Route::post('/tasks/{task}/restore', [TaskController::class, 'restore'])->name('tasks.restore');
    Route::post('/subtasks/{subtask}/restore', [SubtaskController::class, 'restore'])->name('subtasks.restore');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboardold', function () {
    return Inertia::render('Dashboard22');
})->middleware(['auth', 'verified'])->name('dashboard2');

Route::get('/chatai', function () {
    return Inertia::render('Chat');
})->middleware(['auth', 'verified'])->name('chat');





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

Route::get('/companies2/{id}', function ($id) {
    return Inertia::render('Companies/Show22', [
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

Route::middleware(['auth'])->get('/projects2/{id}', function ($id) {
    return Inertia::render('Projects/Show22', [
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

Route::get('/tasks2/{id}', function ($id) {
    return Inertia::render('Tasks/Show22', ['id' => (int)$id]);
})->middleware(['auth']);

Route::get('/subtasks/{id}', function ($id) {
    return Inertia::render('Subtasks/Show', ['id' => (int)$id]);
})->middleware(['auth']);

Route::get('/subtasks2/{id}', function ($id) {
    return Inertia::render('Subtasks/Show22', ['id' => (int)$id]);
})->middleware(['auth']);

Route::middleware(['auth', 'verified'])->get('/calendar', function () {
    return Inertia::render('Calendar/Index');
});

Route::middleware(['auth', 'verified'])->get('/taskpull', function () {
    return Inertia::render('Taskspull/TaskSummary');
})->name('tasks.summary');

Route::middleware(['auth'])->group(function () {
    Route::get('/completed', [CompletedTasksController::class, 'index'])->name('tasks.completed');
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

    Route::get('/mapdiagram', function () {
        return Inertia::render('CompanyDiagram');
    })->name('mapdiagram');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/supportmessages', function () {
        return Inertia::render('Supporttwo/Chat');
    })->name('support.chat');
});

Route::middleware(['auth', 'verified', 'support'])->group(function () {

    Route::get('/support/admin', function () {
        return Inertia::render('Supporttwo/Admin');
    })->name('support.admin');

});



Route::middleware(['auth', 'verified'])->group(function () {
    // Панель техподдержки (только для support — проверка через Policy)
    Route::get('/support/messages', [AdminSupportController::class, 'index'])
        ->name('support.index');

    Route::post('/support/messages/{message}/reply', [AdminSupportController::class, 'reply'])
        ->name('support.reply');

    Route::post('/support/messages/{message}/close', [AdminSupportController::class, 'close'])
        ->name('support.close');

    // История обращений для обычных пользователей
    Route::get('/support-history', function () {
        return Inertia::render('SupportHistory/supporthistory');
    })->name('support.history');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/support/messages/{id}/transfer', [AdminSupportController::class, 'transfer'])
        ->name('support.transfer');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/ruk', function () {
        return Inertia::render('Ruk');
    })->name('ruk');
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/contracts', function () {
        return Inertia::render('Contracts/Index');
    })->name('contracts.index');
});


Route::middleware(['auth', 'verified'])->get('/users', function () {
    abort_unless(auth()->user()->email === 'miki23074@gmail.com', 403);
    return Inertia::render('Users/Index');
})->name('users.index');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/task-templates', function () {
        return Inertia::render('TaskTemplates/Indexs');
    })->name('task-templates.index');
});


Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');


    Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/meeting-documents', function () {
        return inertia('MeetingDocuments/Index');
    })->name('meeting-documents.index');
});



require __DIR__.'/auth.php';
