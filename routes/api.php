<?php

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


use App\Models\Company;

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


Route::middleware('auth:sanctum')->post('/employees', [EmployeeController::class, 'store']);
Route::middleware('auth:sanctum')->get('/employees', [EmployeeController::class, 'index']);

Route::middleware('auth:sanctum')->post('/projects', [ProjectController::class, 'store']);
Route::middleware('auth:sanctum')->get('/projects/{id}', [ProjectController::class, 'show']);
Route::middleware('auth:sanctum')->get('/projects/{project}/employees', [ProjectController::class, 'employees']);
Route::middleware('auth:sanctum')->get('/users/managers', [UserController::class, 'managers']);
Route::middleware('auth:sanctum')->get('/dashboard/companies', [CompanyController::class, 'companiesWhereUserIsManager']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks', [TaskController::class, 'index']); // если нужна страница всех задач
    Route::patch('/tasks/{task}/progress', [TaskController::class, 'updateProgress']);
Route::post('/tasks/{task}/files', [TaskController::class, 'addFiles']);
Route::get('/tasks/{task}', [TaskController::class, 'show']);
});

Route::get('/tasks/files/{file}', [TaskController::class, 'downloadFile'])
    ->middleware('auth:sanctum');

Route::get('/files/{id}/download', [ProjectController::class, 'download'])
    ->middleware('auth:sanctum');



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks/{task}/subtasks', [SubtaskController::class, 'index']);
    Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store']);
    Route::get('/subtasks/{subtask}', [SubtaskController::class, 'show']);
    
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

