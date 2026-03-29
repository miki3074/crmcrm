<?php

use App\Http\Controllers\API\CompletedTasksController;
use App\Http\Controllers\API\SubtaskController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\ChatController;

use App\Http\Controllers\KlientController;
use App\Http\Controllers\KlientDealController;
use App\Http\Controllers\KlientFileController;
use App\Http\Controllers\KlientTaskController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingDocumentController;
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

Route::middleware(['auth'])->group(function () {
    // Основная страница чата с параметрами
    Route::get('/chat/{type?}/{targetId?}', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/send', [ChatController::class, 'store'])->name('chat.store');

    // Создание группы
    Route::post('/chat/create-group', [ChatController::class, 'createGroup'])->name('chat.group.create');

    Route::post('/chat/groups/{group}/add', [ChatController::class, 'addMember']);
    Route::post('/chat/groups/{group}/remove', [ChatController::class, 'removeMember']);
});



Route::middleware(['auth'])->group(function () {
    // Список всех клиентов
    Route::get('/klients', [KlientController::class, 'index'])->name('klients.index');

    // Форма создания
    Route::get('/klients/create', [KlientController::class, 'create'])->name('klients.create');

    // Сохранение
    Route::post('/klients', [KlientController::class, 'store'])->name('klients.store');

    Route::get('/klients/{klient}', [KlientController::class, 'show'])->name('klients.show');
});

Route::middleware(['auth'])->group(function () {
    // Загрузка (передаем ID клиента)
    Route::post('/klients/{klient}/files', [KlientFileController::class, 'store'])->name('klient-files.store');

    // Скачивание (передаем ID файла)
    Route::get('/klient-files/{file}/download', [KlientFileController::class, 'download'])->name('klient-files.download');

    // Удаление (передаем ID файла)
    Route::delete('/klient-files/{file}', [KlientFileController::class, 'destroy'])->name('klient-files.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/klients/{klient}/tasks', [KlientTaskController::class, 'store'])->name('klient-tasks.store');
    Route::patch('/klient-tasks/{task}/toggle', [KlientTaskController::class, 'toggleStatus'])->name('klient-tasks.toggle');
    Route::patch('/klient-tasks/{task}/status', [KlientTaskController::class, 'updateStatus'])->name('klient-tasks.update-status');

    Route::get('/klient-tasks/{task}', [KlientTaskController::class, 'show'])->name('klient-tasks.show');


    Route::get('/klients/{klient}/edit', [KlientController::class, 'edit'])->name('klients.edit');
    Route::put('/klients/{klient}', [KlientController::class, 'update'])->name('klients.update');

});

Route::middleware(['auth'])->group(function () {
    // Страница создания (форма)
    Route::get('/klients/{klient}/deals/create', [KlientDealController::class, 'create'])->name('klient-deals.create');

    // Сохранение
    Route::post('/klients/{klient}/deals', [KlientDealController::class, 'store'])->name('klient-deals.store');

    // Просмотр сделки
    Route::get('/klient-deals/{deal}', [KlientDealController::class, 'show'])->name('klient-deals.show');

    Route::patch('/klient-deals/{deal}/status', [KlientDealController::class, 'updateStatus'])->name('klient-deals.update-status');

    Route::get('/klient-deals/{deal}/edit', [KlientDealController::class, 'edit'])->name('klient-deals.edit');
    Route::put('/klient-deals/{deal}', [KlientDealController::class, 'update'])->name('klient-deals.update');
    Route::delete('/klient-deal-files/{file}', [KlientDealController::class, 'destroyFile'])->name('klient-deal-files.destroy');

    Route::get('/deals', [KlientDealController::class, 'index'])->name('deals.index');

    Route::prefix('klients/{klient}/tasks')->group(function () {
        Route::put('/{task}', [KlientTaskController::class, 'update'])->name('klient-tasks.update');
        Route::delete('/{task}/files/{file}', [KlientTaskController::class, 'deleteFile'])->name('klient-tasks.delete-file');
    });

});

Route::middleware(['auth'])->group(function () {
    // Маршруты для файлов задач
    Route::post('/klient-tasks/{task}/files', [KlientTaskController::class, 'upload'])
        ->name('klient-task-files.upload');

    Route::get('/klient-task-files/{file}/download', [KlientTaskController::class, 'download'])
        ->name('klient-task-files.download');

    Route::delete('/klient-task-files/{file}', [KlientTaskController::class, 'destroy'])
        ->name('klient-task-files.destroy');

    Route::delete('/klient-tasks/{task}/files', [KlientTaskController::class, 'destroyMultiple'])
        ->name('klient-task-files.destroy-multiple');


    Route::post('/klient-deals/{deal}/upload-files', [KlientDealController::class, 'uploadFiles'])
        ->name('klient-deals.upload-files');

// Скачивание файла
    Route::get('/klient-deal-files/{file}/download', [KlientDealController::class, 'downloadFile'])
        ->name('klient-deal-files.download');

});




//new---------------------------------------------------------------

Route::get('/home', function () {
    return Inertia::render('v2/Dashboard');
})->middleware(['auth', 'verified'])->name('home');

Route::get('/companieshome/{id}', function ($id) {
    return Inertia::render('v2/Companies/Show', [
        'id' => $id,
    ]);
})->middleware(['auth', 'verified']);

Route::middleware(['auth'])->get('/projectshome/{id}', function ($id) {
    return Inertia::render('v2/Projects/Show', [
        'id' => $id,
    ]);
});

Route::get('/taskshome/{id}', function ($id) {
    return Inertia::render('v2/Tasks/Show', ['id' => (int)$id]);
})->middleware(['auth']);



Route::get('/subtaskshome/{id}', function ($id) {
    return Inertia::render('v2/Subtasks/Show', ['id' => (int)$id]);
})->middleware(['auth']);

Route::middleware(['auth', 'verified'])->get('/calendarhome', function () {
    return Inertia::render('v2/Calendar/Index');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/employeeshome', function () {
        return Inertia::render('v2'); // или 'Employees/Index' если ты положишь в папку
    });
});

//new------------------------------------------------------------



Route::get('/docs', function () {
    return view('docs');
});

Route::get('/licen', function () {
    return view('license-terms');
});



Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth'])->group(function () {
    // ... другие маршруты

    Route::get('/watching', function () {
        return Inertia::render('WatchingPage');
    })->name('watching');
});


//Route::get('/docs/registry', function () {
//    return Inertia::render('Public/RegistryDocs');
//})->name('registry.docs');
//
//Route::get('/licensing', function () {
//    return Inertia::render('Public/FreeLicense');
//})->name('licensing.show');

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

    Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');

    Route::post('/meetings/{meeting}/review', [MeetingController::class, 'reviewProtocol'])->name('meetings.review');

    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])
        ->name('meetings.destroy');

    Route::post('/meetings/{meeting}/agenda-feedback', [MeetingController::class, 'agendaFeedback'])->name('meetings.agenda.feedback');

    Route::post('/meetings/{meeting}/agenda-fix/{participant}', [MeetingController::class, 'markAgendaFixed'])
        ->name('meetings.agenda.fixed');

    Route::post('/meetings/{meeting}/documents', [MeetingDocumentController::class, 'store'])->name('meetings.documents.store');
    Route::get('/meetings/{meeting}/documents/{document}', [MeetingDocumentController::class, 'download'])->name('meetings.documents.download');
// Используем POST с _method PUT для файлов, так надежнее в Laravel
    Route::post('/meetings/{meeting}/documents/{document}/update', [MeetingDocumentController::class, 'update'])->name('meetings.documents.update');
    Route::delete('/meetings/{meeting}/documents/{document}', [MeetingDocumentController::class, 'destroy'])->name('meetings.documents.destroy');
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

Route::get('/formzak', function () {
    return Inertia::render('LegislationForm');
})->middleware(['auth', 'verified'])->name('formzak');



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
