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