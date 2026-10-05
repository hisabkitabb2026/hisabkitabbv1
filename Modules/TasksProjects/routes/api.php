<?php

use Illuminate\Support\Facades\Route;
use Modules\TasksProjects\Http\Controllers\BillingController;
use Modules\TasksProjects\Http\Controllers\BoardController;
use Modules\TasksProjects\Http\Controllers\BulkTasksController;
use Modules\TasksProjects\Http\Controllers\MembersController;
use Modules\TasksProjects\Http\Controllers\ProjectMembersController;
use Modules\TasksProjects\Http\Controllers\ProjectsController;
use Modules\TasksProjects\Http\Controllers\ReportsController;
use Modules\TasksProjects\Http\Controllers\SettingsController;
use Modules\TasksProjects\Http\Controllers\TasksController;
use Modules\TasksProjects\Http\Controllers\TaskStatusesController;
use Modules\TasksProjects\Http\Controllers\TaskTimeLogController;
use Modules\TasksProjects\Http\Controllers\TimeEntriesController;
use Modules\TasksProjects\Http\Controllers\TimerController;

/*
 * The module's HTTP surface. See specs/tasks-projects.md "API surface".
 *
 * Every route sits behind the host stack: `company` puts the active company on
 * the request as the `company` header, `bouncer` scopes permissions to it, and
 * each action then checks its own module ability. The slug prefix means a
 * future core route can never collide with one of these.
 */
Route::prefix('api/v1/tasks-projects')->middleware(['api', 'auth:sanctum', 'company', 'bouncer'])->group(function (): void {
    Route::get('projects', [ProjectsController::class, 'index'])->name('tasks-projects.projects.index');
    Route::post('projects', [ProjectsController::class, 'store'])->name('tasks-projects.projects.store');
    Route::get('projects/{id}', [ProjectsController::class, 'show'])->name('tasks-projects.projects.show');
    Route::put('projects/{id}', [ProjectsController::class, 'update'])->name('tasks-projects.projects.update');
    Route::delete('projects/{id}', [ProjectsController::class, 'destroy'])->name('tasks-projects.projects.destroy');
    Route::post('projects/{id}/archive', [ProjectsController::class, 'archive'])->name('tasks-projects.projects.archive');
    Route::post('projects/{id}/unarchive', [ProjectsController::class, 'unarchive'])->name('tasks-projects.projects.unarchive');

    Route::get('projects/{id}/members', [ProjectMembersController::class, 'index'])->name('tasks-projects.project-members.index');
    Route::post('projects/{id}/members', [ProjectMembersController::class, 'store'])->name('tasks-projects.project-members.store');
    Route::delete('projects/{id}/members/{userId}', [ProjectMembersController::class, 'destroy'])->name('tasks-projects.project-members.destroy');

    Route::get('members', MembersController::class)->name('tasks-projects.members.index');

    Route::get('tasks', [TasksController::class, 'index'])->name('tasks-projects.tasks.index');
    Route::post('tasks', [TasksController::class, 'store'])->name('tasks-projects.tasks.store');
    Route::post('tasks/bulk', BulkTasksController::class)->name('tasks-projects.tasks.bulk');
    Route::get('tasks/{id}', [TasksController::class, 'show'])->name('tasks-projects.tasks.show');
    Route::put('tasks/{id}', [TasksController::class, 'update'])->name('tasks-projects.tasks.update');
    Route::delete('tasks/{id}', [TasksController::class, 'destroy'])->name('tasks-projects.tasks.destroy');
    Route::post('tasks/{id}/move', [TasksController::class, 'move'])->name('tasks-projects.tasks.move');
    Route::post('tasks/{id}/start', [TimerController::class, 'startOnTask'])->name('tasks-projects.tasks.start');
    Route::post('tasks/{id}/stop', [TimerController::class, 'stopOnTask'])->name('tasks-projects.tasks.stop');
    Route::get('tasks/{id}/time-log', TaskTimeLogController::class)->name('tasks-projects.tasks.time-log');

    Route::get('board', BoardController::class)->name('tasks-projects.board.index');

    Route::get('task-statuses', [TaskStatusesController::class, 'index'])->name('tasks-projects.task-statuses.index');
    Route::post('task-statuses', [TaskStatusesController::class, 'store'])->name('tasks-projects.task-statuses.store');
    Route::post('task-statuses/reorder', [TaskStatusesController::class, 'reorder'])->name('tasks-projects.task-statuses.reorder');
    Route::put('task-statuses/{id}', [TaskStatusesController::class, 'update'])->name('tasks-projects.task-statuses.update');
    Route::delete('task-statuses/{id}', [TaskStatusesController::class, 'destroy'])->name('tasks-projects.task-statuses.destroy');

    Route::get('time-entries', [TimeEntriesController::class, 'index'])->name('tasks-projects.time-entries.index');
    Route::post('time-entries', [TimeEntriesController::class, 'store'])->name('tasks-projects.time-entries.store');
    Route::get('time-entries/{id}', [TimeEntriesController::class, 'show'])->name('tasks-projects.time-entries.show');
    Route::put('time-entries/{id}', [TimeEntriesController::class, 'update'])->name('tasks-projects.time-entries.update');
    Route::delete('time-entries/{id}', [TimeEntriesController::class, 'destroy'])->name('tasks-projects.time-entries.destroy');

    Route::get('timer', [TimerController::class, 'show'])->name('tasks-projects.timer.show');
    Route::delete('timer', [TimerController::class, 'destroy'])->name('tasks-projects.timer.destroy');
    Route::post('timer/start', [TimerController::class, 'start'])->name('tasks-projects.timer.start');
    Route::post('timer/stop', [TimerController::class, 'stop'])->name('tasks-projects.timer.stop');

    Route::get('billing/customers', [BillingController::class, 'customers'])->name('tasks-projects.billing.customers');
    Route::get('billing/unbilled', [BillingController::class, 'unbilled'])->name('tasks-projects.billing.unbilled');
    Route::post('billing/prepare', [BillingController::class, 'prepare'])->name('tasks-projects.billing.prepare');
    Route::post('billing/confirm', [BillingController::class, 'confirm'])->name('tasks-projects.billing.confirm');

    Route::get('reports/summary', [ReportsController::class, 'summary'])->name('tasks-projects.reports.summary');

    Route::get('settings', SettingsController::class)->name('tasks-projects.settings.show');
});
