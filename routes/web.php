        <?php

        use Illuminate\Support\Facades\Route;

        /*
        |--------------------------------------------------------------------------
        | Web Routes
        |--------------------------------------------------------------------------
        |
        | Here is where you can register web routes for your application. These
        | routes are loaded by the RouteServiceProvider within a group which
        | contains the "web" middleware group. Now create something great!
        |
        */

        Route::get('/', function () {
            return view('chatbot'); // Asegúrate de que esto apunte a 'chatbot'
        });

        // Puedes añadir más rutas web aquí si las necesitas
        