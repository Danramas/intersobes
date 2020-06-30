<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\User;

class AuthController extends Controller
{
    public function login()
    {
        // Проверяем существует ли пользователь с указанным email адресом
        $user = User::whereEmail(request('username'))->first();

        if (!$user) {
            return response()->json([
                'message' => 'Wrong email',
                'status' => 422
            ], 422);
        }

        // Если пользователь с таким email адресом найден - проверим совпадает
        // ли его пароль с указанным
        if (!Hash::check(request('password'), $user->password)) {
            return response()->json([
                'message' => 'Wrong password',
                'status' => 422
            ], 422);
        }

        // Внутренний API запрос для получения токенов
        $client = DB::table('oauth_clients')
            ->where('password_client', true)
            ->first();

        // Убедимся, что Password Client существует в БД (т.е. Laravel Passport
        // установлен правильно)
        if (!$client) {
            return response()->json([
                'message' => 'Laravel Passport is not setup properly.',
                'status' => 500
            ], 500);
        }

//        $data = [
//            'grant_type' => 'password',
//            'client_id' => $client->id,
//            'client_secret' => $client->secret,
//            'email' => request('username'),
//            'password' => request('password'),
//        ];
//
//        $request = Request::create('/oauth/token', 'POST', $data);
//
//        $response = app()->handle($request);
//
//        // Проверяем был ли внутренний запрос успешным
//        if ($response->getStatusCode() != 200) {
//            return response()->json([
//                $request,
//                'message' => 'Wrong email or password',
//                'status' => 422
//            ], 422);
//        }
//
//        // Вытаскиваем данные из ответа
//        $data = json_decode($response->getContent());

        // Формируем окончательный ответ в нужном формате
        return response()->json([
            'token' => $user->createToken('appToken')->accessToken,
            'user' => $user,
            'status' => 200
        ]);
    }
//    public function login(Request $request)
//    {
//        if (auth()->attempt(['email' => request('username'), 'password' => request('password')])) {
//            $user = auth()->user();
//            $token = $user->createToken('appToken')->accessToken;
//            return [
//                'user' => $user,
//                'token' => $token
//            ];
//        }
//   }

    public function logout()
    {
        $accessToken = auth()->user()->token();

        $refreshToken = DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update([
                'revoked' => true
            ]);

        $accessToken->revoke();

        return response()->json(['status' => 200]);
    }
    public function register()
    {
        User::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => bcrypt(request('password'))
        ]);

        return response()->json(['status' => 201]);
    }
}
