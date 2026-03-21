<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

/**
 * Class AuthController
 * * Centraliza o fluxo de autenticação stateless via JWT (JSON Web Token).
 * Responsável pela emissão, renovação (implícita) e invalidação de tokens.
 */
class AuthController extends Controller
{
    /**
     * Autentica as credenciais do usuário e emite um token de acesso.
     * * @param Request $request Contém email e password.
     * @return JsonResponse Token JWT e dados básicos do perfil se as credenciais forem válidas.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // O guard 'api' utiliza o driver JWT configurado no auth.php
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'error' => 'Credenciais inválidas'
            ], 401);
        }

        return $this->respondWithToken((string) $token);
    }

    /**
     * Recupera o perfil do usuário autenticado a partir do Bearer Token enviado.
     * * @return JsonResponse Dados completos do modelo User autenticado.
     */
    public function me(): JsonResponse
    {
        return response()->json(Auth::guard('api')->user());
    }

    /**
     * Invalida o token atual (Blacklist) para encerrar a sessão.
     * * @return JsonResponse Mensagem de confirmação de logout.
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Estrutura a resposta JSON padrão contendo os metadados do token.
     * * @param string $token O token gerado pelo JWT-Auth.
     * @return JsonResponse Estrutura com access_token, token_type e TTL.
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        /** @var \Tymon\JWTAuth\Factory $factory */
        $factory = Auth::guard('api')->factory();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $factory->getTTL() * 60, // Converte minutos para segundos
            'user' => Auth::guard('api')->user(),
        ]);
    }
}