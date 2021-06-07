<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\RedGifsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/weather/mj', [IndexController::class, 'weather']);//天气
Route::get('/live/mj', [IndexController::class, 'live']);//生活指数
Route::get('/news', [IndexController::class, 'getTodayNews']);//社会新闻

//推送跟随消息到tg
Route::get('/follows', [RedGifsController::class, 'index']);//获取跟随列表
