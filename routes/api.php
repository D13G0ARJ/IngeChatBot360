<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

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

// Agrupar las rutas del chatbot bajo el middleware 'web' para habilitar la sesión
// Esto asegura que el estado de la conversación (session) persista entre solicitudes.
Route::middleware('web')->group(function () {
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::post('/chat/restart', [ChatController::class, 'restartChat']);
});

// Puedes mantener otras rutas API aquí si las tienes, sin el middleware 'web'
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

