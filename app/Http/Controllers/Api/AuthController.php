<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Exception;

class AuthController extends Controller
{
   #[OA\Post(
        path: "/register",
        summary: "Rejestracja nowego użytkownika",
        tags: ["Autoryzacja"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Jan Kowalski"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "jan@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "haslo123")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Użytkownik pomyślnie zarejestrowany."
    )]
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Generujemy token dostępowy Sanctum dla użytkownika
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Rejestracja przebiegła pomyślnie!',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ], 201);
        } catch (Exception $e) {
            Log::error('Błąd rejestracji użytkownika: ' . $e->getMessage());
            return response()->json(['error' => 'Nie udało się zarejestrować użytkownika.'], 500);
        }
    }

    #[OA\Post(
        path: "/login",
        summary: "Logowanie użytkownika",
        tags: ["Autoryzacja"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "jan@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "haslo123")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Pomyślnie zalogowano, zwrócono token."
    )]
    #[OA\Response(
        response: 401,
        description: "Błędne dane logowania."
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'error' => 'Podany e-mail lub hasło są niepoprawne.'
                ], 401);
            }

            // Unieważniamy poprzednie tokeny, aby zachować tylko jedną aktywną sesję
            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Zalogowano pomyślnie!',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ], 200);
        } catch (Exception $e) {
            Log::error('Błąd logowania użytkownika: ' . $e->getMessage());
            return response()->json(['error' => 'Wystąpił błąd podczas próby logowania.'], 500);
        }
    }

    #[OA\Post(
        path: "/logout",
        operationId: "logoutUser",
        summary: "Wyloguj użytkownika",
        description: "Usuwa aktualny token Sanctum.",
        security: [["sanctum" => []]],
        tags: ["Autoryzacja"]
    )]
    #[OA\Response(
        response: 200,
        description: "Pomyślnie wylogowano."
    )]
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Pomyślnie wylogowano, unieważniono token.'
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Błąd podczas wylogowywania.'], 500);
        }
    }
}