<?php

use Illuminate\Support\Facades\Route;
use Rabianr\LogMailViewer\Controllers\MailLogController;

Route::get(config('logmailviewer.route'), [ MailLogController::class, 'index' ])
    ->name('logmailviewer.index');
