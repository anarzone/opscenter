<?php


use App\Http\Controllers\WebhookController;

Route::post('webhooks/{source}', [WebhookController::class,'handle'])->name('webhooks.handle');
