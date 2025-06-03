<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController; // ¡Esta línea es crucial!

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

// Si no estás usando autenticación Sanctum para el chatbot,
// puedes comentar o eliminar esta ruta si no la necesitas.
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Rutas para el Chatbot
Route::post('/chat/send', [ChatController::class, 'sendMessage']);
Route::post('/chat/restart', [ChatController::class, 'restartChat']);
