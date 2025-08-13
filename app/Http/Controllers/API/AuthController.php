<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public const OAUTH_TOKEN_ENDPOINT = 'oauth/token';

    /**
     * @OA\Post(
     *     path="/auth/token",
     *     summary="Get OAuth token",
     *     description="Exchange credentials for OAuth token
     * 
     * **Curl Example:**
     * ```bash
     * curl -X POST 'https://pm-api.test.primemover.example/api/v1/auth/token' \
     *   -H 'Content-Type: application/json' \
     *   -H 'Accept: application/json' \
     *   -d '{
     *     \"grant_type\": \"client_credentials\",
     *     \"client_id\": \"your_client_id\",
     *     \"client_secret\": \"your_client_secret\"
     *   }'
     * ```
     * 
     * **R Example:**
     * ```r
     * library(httr)
     * library(jsonlite)
     * 
     * response <- POST(
     *   url = \"https://pm-api.test.primemover.example/api/v1/auth/token\",
     *   add_headers(
     *     \"Content-Type\" = \"application/json\",
     *     \"Accept\" = \"application/json\"
     *   ),
     *   body = toJSON(list(
     *     grant_type = \"client_credentials\",
     *     client_id = \"your_client_id\",
     *     client_secret = \"your_client_secret\"
     *   ), auto_unbox = TRUE)
     * )
     * 
     * token_data <- content(response, \"parsed\")
     * print(token_data)
     * ```",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"grant_type","client_id","client_secret"},
     *             @OA\Property(property="grant_type", type="string", example="client_credentials"),
     *             @OA\Property(property="client_id", type="string", example="your_client_id"),
     *             @OA\Property(property="client_secret", type="string", example="your_client_secret")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     )
     * )
     */
    public function token(Request $request): Response
    {
        $proxy = $request->create(self::OAUTH_TOKEN_ENDPOINT, 'POST', $request->all());

        Log::debug('1696930633: Handling oauth token request');

        return App::handle($proxy);
    }

    /**
     * @OA\Get(
     *     path="/auth/user",
     *     summary="Get authenticated user",
     *     description="Get current authenticated user information",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User information",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function user(): ?Authenticatable
    {
        return Auth::user();
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

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="User login",
     *     description="Authenticate user and return access token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email")
     *             ),
     *             @OA\Property(property="access_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid Credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/forgot-password",
     *     summary="Request password reset",
     *     description="Send password reset email to user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset email sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password reset email sent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to send reset email",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/reset-password",
     *     summary="Reset user password",
     *     description="Reset password using token from email",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password","password_confirmation","token"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", description="Must contain uppercase, digit, and special character"),
     *             @OA\Property(property="password_confirmation", type="string", format="password"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Password reset failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
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
