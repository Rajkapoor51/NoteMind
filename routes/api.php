<?php

use App\Http\Controllers\Api\NoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:notes-api')->group(function () {
    Route::get('notes/search', [NoteController::class, 'search']);
    Route::post('notes/{note}/summary', [NoteController::class, 'summary']);
    Route::apiResource('notes', NoteController::class);
});
