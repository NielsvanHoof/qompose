<?php

declare(strict_types=1);

use App\Http\Controllers\Questionnaires\QuestionnaireTemplateController;
use App\Http\Controllers\Questionnaires\QuestionnaireTemplateItemController;
use Illuminate\Support\Facades\Route;

Route::get('templates', [QuestionnaireTemplateController::class, 'index'])->name('templates.index');
Route::get('templates/create', [QuestionnaireTemplateController::class, 'create'])->name('templates.create');
Route::post('templates', [QuestionnaireTemplateController::class, 'store'])->name('templates.store');
Route::get('templates/{template}', [QuestionnaireTemplateController::class, 'show'])->name('templates.show');
Route::put('templates/{template}', [QuestionnaireTemplateController::class, 'update'])->name('templates.update');
Route::delete('templates/{template}', [QuestionnaireTemplateController::class, 'destroy'])->name('templates.destroy');
Route::post('templates/{template}/copy', [QuestionnaireTemplateController::class, 'copy'])->name('templates.copy');
Route::post('templates/{template}/items/reorder', [QuestionnaireTemplateItemController::class, 'reorder'])
    ->name('templates.items.reorder');
Route::post('templates/{template}/items', [QuestionnaireTemplateItemController::class, 'store'])
    ->name('templates.items.store');
Route::put('templates/{template}/items/{item}', [QuestionnaireTemplateItemController::class, 'update'])
    ->name('templates.items.update');
Route::delete('templates/{template}/items/{item}', [QuestionnaireTemplateItemController::class, 'destroy'])
    ->name('templates.items.destroy');
