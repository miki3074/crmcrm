<?php

use App\Http\Controllers\API\CompletedTasksController;
use App\Http\Controllers\API\ProducerBuyerController;
use App\Http\Controllers\API\TaskSummaryController;
use App\Http\Controllers\API\TaskTemplateController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\API\SubtaskController;
use App\Http\Controllers\API\CalendarController;

use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\InteractionController;
use App\Http\Controllers\API\DealController;

use App\Http\Controllers\API\TaskCommentController;
use App\Http\Controllers\API\TaskChecklistController;

use App\Http\Controllers\API\SubprojectController;
use App\Http\Controllers\API\TelegramController;

use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\API\UserManagementController;

use App\Http\Controllers\API\CompanyMapController;

use App\Models\Company;

use App\Http\Controllers\API\TaskDescriptionController;


use App\Http\Controllers\API\SupportController;

use App\Http\Controllers\API\AdminSupportController;

use App\Http\Controllers\API\SupportMessageController;

use App\Http\Controllers\API\SupportReplyController;

use App\Http\Controllers\API\TaskCalendarController;

use App\Http\Controllers\API\SubtaskChecklistController;

use App\Http\Controllers\API\MeetingDocumentController;

use App\Http\Controllers\API\SupportChatController;

use App\Http\Controllers\API\SupportAdminController;

use App\Http\Controllers\API\ContractController;

use App\Http\Controllers\ChatListController;
use App\Http\Controllers\ChatStreamController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/




Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});




Route::middleware('throttle:chat')->group(function () {
    Route::get('/chats', [ChatListController::class, 'index']);
    Route::post('/chats', [ChatListController::class, 'store']);
    Route::get('/chats/{chat}', [ChatListController::class, 'show']);
    Route::post('/chat/stream', [ChatStreamController::class, 'stream']);
    Route::delete('/chats/{chat}', [ChatListController::class, 'destroy']);

});


// Route::middleware(['auth:sanctum'])->group(function () {
//     // компании: просмотр может быть шире, а создание — только для admin
//     Route::get('/companies', [CompanyController::class, 'index']);
//     Route::get('/companies/{company}', [CompanyController::class, 'show']);

//     Route::middleware('role:admin')->group(function () {
//         Route::post('/companies', [CompanyController::class, 'store']);

//     });
// });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies/{company}', [CompanyController::class, 'show']);
});



Route::get('/projects/grouped', [ProjectController::class, 'groupedByCompany']);


Route::middleware('auth:sanctum')->post('/employees', [EmployeeController::class, 'store']);
Route::middleware('auth:sanctum')->get('/employees', [EmployeeController::class, 'index']);

Route::middleware('auth:sanctum')->get('/employeesqw', [EmployeeController::class, 'employeesqw']);



Route::middleware('auth:sanctum')->post('/projects', [ProjectController::class, 'store']);
Route::middleware('auth:sanctum')->get('/projects/{id}', [ProjectController::class, 'show']);
Route::middleware('auth:sanctum')->get('/projects/{project}/employees', [ProjectController::class, 'employees']);
Route::middleware('auth:sanctum')->get('/users/managers', [UserController::class, 'managers']);
Route::middleware('auth:sanctum')->get('/dashboard/companies', [CompanyController::class, 'companiesWhereUserIsManager']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks/summary', [TaskSummaryController::class, 'index']);

    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks', [TaskController::class, 'index']); // если нужна страница всех задач
    Route::patch('/tasks/{task}/progress', [TaskController::class, 'updateProgress']);
Route::post('/tasks/{task}/files', [TaskController::class, 'addFiles']);
Route::get('/tasks/{task}', [TaskController::class, 'show']);

    Route::post('/tasks/{task}/start', [TaskController::class, 'startWork']);
});

Route::get('/tasks/files/{file}', [TaskController::class, 'downloadFile'])
    ->middleware('auth:sanctum');

Route::get('/files/{id}/download', [ProjectController::class, 'download'])
    ->middleware('auth:sanctum');



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks/{task}/subtasks', [SubtaskController::class, 'index']);
    Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store']);
    Route::get('/subtasks/{subtask}', [SubtaskController::class, 'show']);
    Route::post('/subtasks/{subtask}/start', [App\Http\Controllers\API\SubtaskController::class, 'startWork']);

});


Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/projects/{project}/budget', [ProjectController::class, 'updateBudget']);
    Route::patch('/projects/{project}/description', [ProjectController::class, 'updateDescription']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/calendar/events', [CalendarController::class, 'index']);
    Route::post('/calendar/events', [CalendarController::class, 'store']);
    Route::patch('/calendar/events/{event}', [CalendarController::class, 'update']);
    Route::delete('/calendar/events/{event}', [CalendarController::class, 'destroy']);

    Route::get('/companies/{company}/employees', [CompanyController::class, 'employees']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard/summary', [CompanyController::class, 'summary']);
});

Route::patch('/subtasks/{subtask}/progress', [SubtaskController::class, 'updateProgress'])
    ->middleware(['auth:sanctum']);


    Route::patch('/subtasks/{subtask}/progress', [SubtaskController::class, 'updateProgress'])
    ->middleware('auth:sanctum');

Route::patch('/subtasks/{subtask}/complete', [SubtaskController::class, 'complete'])
    ->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('clients', ClientController::class)->only(['index','store','show','update']);

    // вложенные:
    Route::post('clients/{client}/interactions', [InteractionController::class, 'store']);
    Route::get('clients/{client}/deals', [DealController::class, 'indexByClient']);
    Route::post('clients/{client}/deals', [DealController::class, 'store']);

    // изменение статуса сделки:
    Route::patch('deals/{deal}/status', [DealController::class, 'updateStatus']);
});

//Route::middleware(['auth', 'verified'])->group(function () {
//    Route::get('/tasks/completed', [CompletedTasksController::class, 'index']);
//
//});


Route::middleware('auth:sanctum')->prefix('storage')->group(function () {
    Route::get('/companies', [\App\Http\Controllers\API\StorageController::class, 'companies']);
    Route::get('/companies/{company}', [\App\Http\Controllers\API\StorageController::class, 'company']);

    Route::post('/companies/{company}/managers', [\App\Http\Controllers\API\StorageController::class, 'saveManagers']);

    Route::post('/companies/{company}/files', [\App\Http\Controllers\API\StorageController::class, 'upload']);
    Route::get('/files/{file}/download', [\App\Http\Controllers\API\StorageController::class, 'download']);
    Route::delete('/files/{file}', [\App\Http\Controllers\API\StorageController::class, 'destroy']);


});

// routes/api.php
Route::patch('/tasks/{task}/complete', [TaskController::class, 'complete'])->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks/{task}/comments', [TaskCommentController::class, 'index']);
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store']);
    Route::delete('/task-comments/{comment}', [TaskCommentController::class, 'destroy']);

    Route::put('/files/{file}/approve', [TaskController::class, 'approve']);
    Route::put('/files/{file}/reject', [TaskController::class, 'reject']);
    Route::post('/files/{file}/replace', [TaskController::class, 'replace']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/for-attach', [EmployeeController::class, 'usersForAttach']);
    Route::post('/employees/attach', [EmployeeController::class, 'attach']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks/{task}/checklists', [TaskChecklistController::class, 'index']);
    Route::post('/tasks/{task}/checklists', [TaskChecklistController::class, 'store']);
    Route::patch('/checklists/{checklist}/toggle', [TaskChecklistController::class, 'toggle']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/projects/{project}/subprojects', [SubprojectController::class, 'store']);
    Route::get('/subprojects/{subproject}', [SubprojectController::class, 'show']);

    // Можно так же вложить роуты для задач подпроекта
    Route::post('/subprojects/{subproject}/tasks', [TaskController::class, 'store']);
});


Route::middleware('auth:sanctum')->patch('/projects/{project}/name', [ProjectController::class, 'updateName']);

Route::post('/telegram/webhook', [TelegramController::class, 'handle']);

Route::middleware('auth:sanctum')->post('/user/telegram-token', [\App\Http\Controllers\API\UserController::class, 'generateTelegramToken']);

Route::middleware('auth:sanctum')->post('/user/save-chat-id', [UserController::class, 'saveChatId']);

Route::post('/password/telegram', [PasswordResetController::class, 'sendResetLinkViaTelegram']);

Route::get('/owner-companies', [\App\Http\Controllers\API\EmployeeController::class, 'ownerCompanies']);


Route::put('/employees/{id}/update-role', [\App\Http\Controllers\API\EmployeeController::class, 'updateRole']);


Route::get('/companies/{company}/employees', function (Company $company) {
    return $company->users()->select('users.id', 'users.name', 'users.email')->get();
});



Route::middleware('auth:sanctum')->put('/tasks/{task}', [TaskController::class, 'update']);


Route::middleware('auth:sanctum')->post('/tasks/{task}/watchers', [TaskController::class, 'addWatcher']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/projects/{project}/add-manager', [ProjectController::class, 'addManager']);
    Route::post('/projects/{project}/replace-manager', [ProjectController::class, 'replaceManager']);
});

Route::delete('/companies/{company}', [CompanyController::class, 'destroy']);
Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

// управление участниками задачи
Route::patch('/tasks/{task}/executor', [TaskController::class, 'updateExecutor']);

Route::patch('/tasks/{task}/responsible', [TaskController::class, 'updateResponsible']);

Route::patch('/tasks/{task}/watcher', [TaskController::class, 'updateWatcher']);

Route::delete('/subtasks/{subtask}', [SubtaskController::class, 'destroy']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/projects/{project}/watchers', [ProjectController::class, 'addWatcher']);
    Route::delete('/projects/{project}/watchers', [ProjectController::class, 'removeWatcher']);
});



// Route::delete('/tasks/files/{id}', [TaskController::class, 'deleteFile'])
//     ->middleware('auth:sanctum');

    Route::delete('/tasks/files/{id}', [TaskController::class, 'deleteFile'])
     ->middleware('auth:sanctum');




Route::middleware(['auth:sanctum', 'admin.only', 'throttle:10,1'])->group(function () {
    Route::get('/users', [UserManagementController::class, 'index']);
    Route::put('/users/{user}', [UserManagementController::class, 'update']);
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
});


Route::middleware('auth:sanctum')->group(function () {
Route::post('/tasks/{task}/executors/add', [TaskController::class, 'addExecutors']);
Route::post('/tasks/{task}/responsibles/add', [TaskController::class, 'addResponsibles']);
});


Route::delete('/tasks/{task}/executors', [TaskController::class, 'removeExecutor']);
Route::delete('/tasks/{task}/responsibles', [TaskController::class, 'removeResponsible']);
Route::delete('/tasks/{task}/watchers', [TaskController::class, 'removeWatcher']);


Route::post('/projects/{project}/executors', [ProjectController::class, 'addExecutor']);
Route::delete('/projects/{project}/executors', [ProjectController::class, 'removeExecutor']);


Route::prefix('subtasks/{subtask}')->group(function () {
    Route::patch('/responsible/change', [SubtaskController::class, 'changeResponsible']);
    Route::patch('/executor/change', [SubtaskController::class, 'changeExecutor']);
    Route::post('/executors/add', [SubtaskController::class, 'addExecutors']);
    Route::post('/responsibles/add', [SubtaskController::class, 'addResponsibles']);
        Route::delete('/executors', [SubtaskController::class, 'removeExecutor']);
    Route::delete('/responsibles', [SubtaskController::class, 'removeResponsible']);
     Route::patch('/update', [SubtaskController::class, 'update']);
});


Route::prefix('subtasks/{subtask}')->group(function () {
    Route::post('/files', [SubtaskController::class, 'uploadFile']);
});

Route::get('/subtask-files/{file}/download', [SubtaskController::class, 'downloadFile']);
Route::delete('/subtask-files/{file}', [SubtaskController::class, 'deleteFile']);


Route::post('/subtasks/{subtask}/children', [SubtaskController::class, 'storeChild']);

Route::get('/companies/{company}/members', [CompanyController::class, 'members']);

Route::get('/projects/{project}/tasks', [ProjectController::class, 'tasks']);

Route::get('/projects/{project}/task-stats', [ProjectController::class, 'taskStats']);


Route::get('/tasks/{task}/description', [TaskDescriptionController::class, 'show']);
Route::patch('/tasks/{task}/description', [TaskDescriptionController::class, 'update']);


Route::get('/projects', [ProjectController::class, 'index']);

Route::delete('/clients/{client}', [ClientController::class, 'destroy']);


Route::middleware(['auth:sanctum', 'throttle:5,1']) // не более 5 раз в минуту
    ->post('/support', [SupportController::class, 'store']);

Route::middleware(['auth', 'role:support'])->group(function () {
    Route::get('/support/messages', [AdminSupportController::class, 'index'])->name('support.index');
});

Route::get('/mapdiagram', function () {
    return \App\Models\Company::where('user_id', auth()->id())->get(['id', 'name']);
})->middleware('auth:sanctum');

// Получить структуру конкретной компании (проект → задачи → подзадачи)
Route::get('/mapdiagram/{company}/map', [CompanyMapController::class, 'show'])
    ->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/support/history', [SupportMessageController::class, 'index']);
});


// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/support/reply', [SupportReplyController::class, 'store']);
// });

Route::middleware('auth:sanctum')->group(function () {
    // пользователь пишет ответ
    Route::post('/support/reply', [SupportReplyController::class, 'storeUser']);

    // техподдержка отвечает пользователю
    Route::post('/support/admin-reply', [SupportReplyController::class, 'storeSupport'])
        ->middleware('role:support');
});


Route::post('/support/messages/{id}/transfer', [SupportMessageController::class, 'transfer'])
    ->middleware(['auth:sanctum']);



Route::delete('/projects/{project}/members', [ProjectController::class, 'remove']);
Route::delete('/tasks/files/{file}', [TaskController::class, 'deleteFile']);


Route::post('/support/read/{id}', [SupportMessageController::class, 'markRead']);
Route::post('/support/read-user/{id}', [SupportMessageController::class, 'markUserRead']);

Route::post('/support/read-support/{id}', [SupportMessageController::class, 'markSupportRead']);





Route::get('/calendar/tasks', [TaskCalendarController::class, 'index']);


Route::middleware('auth')->group(function () {

    Route::post('/subtasks/{id}/comments', [SubtaskController::class, 'addComment']);
    Route::patch('/subtask-comments/{id}', [SubtaskController::class, 'updateComment']);
    Route::delete('/subtask-comments/{id}', [SubtaskController::class, 'deleteComment']);

});

Route::prefix('subtasks/{id}/checklist')->group(function () {
    Route::post('/', [SubtaskChecklistController::class, 'store']);
});

Route::prefix('subtask-checklist/{id}')->group(function () {
    Route::patch('/', [SubtaskChecklistController::class, 'update']);
    Route::patch('/toggle', [SubtaskChecklistController::class, 'toggle']);
    Route::delete('/', [SubtaskChecklistController::class, 'destroy']);
});


Route::patch('/subtasks/{subtask}/description', [SubtaskController::class, 'updateDescription']);


Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

Route::post('/dadata/inn', [ClientController::class, 'findByInn']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('meeting-documents', MeetingDocumentController::class);
});


Route::get('/tasks-with-subtasks', [\App\Http\Controllers\API\TaskAccessController::class, 'index']);
Route::get('/meeting-documents/{id}/pdf', [MeetingDocumentController::class, 'pdf']);



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/support/threads', [SupportChatController::class, 'threads']);
    Route::post('/support/threads', [SupportChatController::class, 'createThread']);
    Route::get('/support/threads/{thread}', [SupportChatController::class, 'messages']);
    Route::post('/support/threads/{thread}/messages', [SupportChatController::class, 'sendMessage']);
});

Route::middleware(['auth:sanctum', 'support'])->prefix('support/admin')->group(function () {
    Route::get('/threads', [SupportAdminController::class, 'threads']);
    Route::get('/threads/{thread}', [SupportAdminController::class, 'show']);
    Route::post('/threads/{thread}/messages', [SupportAdminController::class, 'sendMessage']);
    Route::post('/threads/{thread}/close', [SupportAdminController::class, 'close']);
    Route::post('/threads/{thread}/reopen', [SupportAdminController::class, 'reopen']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/contracts', [ContractController::class, 'index']);
    Route::post('/contracts', [ContractController::class, 'store']);
    Route::put('/contracts/{contract}', [ContractController::class, 'update']);
    Route::delete('/contracts/{contract}', [ContractController::class, 'destroy']);
    Route::delete('/contracts/files/{file}', [ContractController::class, 'deleteFile']);
    Route::post('/contracts/{contract}/move', [ContractController::class, 'move']);
    Route::get('/contracts/files/{file}/download', [ContractController::class, 'downloadFile']);
});

Route::middleware('auth:sanctum')->group(function () {

    // список шаблонов
    Route::get('/task-templates', [TaskTemplateController::class, 'index']);

    // справочники (компании, проекты, сотрудники)
    Route::get('/task-templates/companies', [TaskTemplateController::class, 'companiesForUser']);
    Route::get('/task-templates/companies/{company}/projects', [TaskTemplateController::class, 'projectsByCompany']);
    Route::get('/task-templates/companies/{company}/employees', [TaskTemplateController::class, 'employeesByCompany']);

    // CRUD по шаблонам
    Route::post('/task-templates', [TaskTemplateController::class, 'store']);
    Route::put('/task-templates/{template}', [TaskTemplateController::class, 'update']);
    Route::delete('/task-templates/{template}', [TaskTemplateController::class, 'destroy']);

    // файлы шаблонов
    Route::delete('/task-template-files/{file}', [TaskTemplateController::class, 'deleteFile']);

    // создать задачу по шаблону
    Route::post('/task-templates/{template}/create-task', [TaskTemplateController::class, 'createTaskFromTemplate']);

    Route::post('/task-templates/{template}/duplicate',
        [TaskTemplateController::class, 'duplicate']);

    Route::delete('/task-template-files/{file}',
        [TaskTemplateController::class, 'deleteFile']);

    // Фильтрация производителей/покупателей по компании
    Route::get('/task-templates/companies/{company}/producers', [ProducerBuyerController::class, 'producersByCompany']);
    Route::get('/task-templates/companies/{company}/buyers',    [ProducerBuyerController::class, 'buyersByCompany']);

});

// === ПРОИЗВОДИТЕЛИ / ПОКУПАТЕЛИ ===
Route::get('/producers', [ProducerBuyerController::class, 'producers']);
Route::get('/buyers', [ProducerBuyerController::class, 'buyers']);

Route::post('/producers', [ProducerBuyerController::class, 'storeProducer']);
Route::post('/buyers', [ProducerBuyerController::class, 'storeBuyer']);

Route::put('/producers/{producer}', [ProducerBuyerController::class, 'updateProducer']);
Route::put('/buyers/{buyer}', [ProducerBuyerController::class, 'updateBuyer']);

Route::delete('/producers/{producer}', [ProducerBuyerController::class, 'deleteProducer']);
Route::delete('/buyers/{buyer}', [ProducerBuyerController::class, 'deleteBuyer']);

// Привязка к задаче
Route::post('/tasks/{task}/producers/{producer}', [ProducerBuyerController::class, 'attachProducerToTask']);
Route::post('/tasks/{task}/buyers/{buyer}', [ProducerBuyerController::class, 'attachBuyerToTask']);



//Route::post('/contracts/{contract}/move', [ContractController::class, 'move']);



Route::get('/my-calendar-companies', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    $companies = Company::query()
        ->where('user_id', $user->id) // Владелец
        ->orWhereHas('users', function ($q) use ($user) {
            $q->where('users.id', $user->id)
              ->where('company_user.role', 'manager'); // Менеджер
        })
        ->get(['id','name']);

    return $companies;
});

