<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

class AuthController extends Controller
{
    public function token(Request $request)
    {
        $request->request->add([
            'grant_type' => 'password',
            'client_id' => config('auth.passport.client_id'),
            'client_secret' => config('auth.passport.client_secret'),
        ]);

        $proxy = Request::create('oauth/token', 'post');

        return Route::dispatch($proxy);
    }

    public function user(): ?Authenticatable
    {
        return auth()->user();
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:55',
            'email' => 'email|required|unique:users',
            'password' => 'required|confirmed',
        ]);

        $validatedData['password'] = bcrypt($request->password);

        $user = User::create($validatedData);

        $accessToken = $user->createToken('authToken')->accessToken;

        return response(['user' => $user, 'access_token' => $accessToken]);
    }

    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'email|required',
            'password' => 'required',
        ]);

        $auth = auth()->guard('web')->attempt($loginData);

        if ( ! $auth) {
            return response(['message' => 'Invalid Credentials']);
        }

        $user = auth()->guard('web')->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->accessToken;

        // $accessToken = auth()->user()->createToken('authToken')->accessToken;

        // return response(['user' => auth()->user(), 'access_token' => $accessToken]);
        return response(['user' => $user, 'access_token' => $token]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->email;
        $response = Password::broker()->sendResetLink($request->only('email'));

        if (Password::RESET_LINK_SENT === $response) {
            return response()->json([
                'status' => true,
                'message' => trans($response),
            ]);
        }

        Log::alert(now(), ['email' => $email]);

        return response()->json([
            'status' => false,
            'message' => trans($response),
        ], 400);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'email',
            'password' => "required|confirmed|string|min:8|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/",
        ]);

        $response = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password): void {
                $user->password = bcrypt($password);
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if (Password::PASSWORD_RESET === $response) {
            return response()->json([
                'message' => trans($response),
            ]);
        }

        return response()->json([
            'message' => trans($response),
        ], 400);
    }
}
