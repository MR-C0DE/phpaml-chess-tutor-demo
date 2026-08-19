<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\{ChessContext, User};
use PHPAML\Http\{Request, Response};
use PHPAML\Mvc\Controller;
use PHPAML\Session\Session;
use Throwable;

final class AuthController extends Controller
{
    public function __construct(private readonly Session $session) {}

    public function me(Request $request): Response
    {
        $id = $this->session->get('user_id');
        if (!is_string($id)) return $this->json(['authenticated' => false]);
        $user = ChessContext::connect()->users()->find($id);
        return $this->json($user ? ['authenticated' => true, 'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]] : ['authenticated' => false]);
    }

    public function register(Request $request): Response
    {
        $name = trim((string) $request->input('name'));
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            return $this->json(['error' => 'Enter a valid name, email and a password of at least 8 characters.'], 422);
        }
        try {
            $db = ChessContext::connect();
            if ($db->users()->where('email', '=', $email)->first()) return $this->json(['error' => 'This email is already registered.'], 409);
            $user = new User();
            $user->name = $name; $user->email = $email;
            $user->passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $user->createdAt = gmdate(DATE_ATOM);
            $db->users()->add($user);
            $this->session->regenerate(); $this->session->set('user_id', $user->id);
            return $this->json(['user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]], 201);
        } catch (Throwable) { return $this->json(['error' => 'The account service is temporarily unavailable.'], 503); }
    }

    public function login(Request $request): Response
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        try {
            $user = ChessContext::connect()->users()->where('email', '=', $email)->first();
            if (!$user || !password_verify($password, $user->passwordHash)) return $this->json(['error' => 'Incorrect email or password.'], 401);
            $this->session->regenerate(); $this->session->set('user_id', $user->id);
            return $this->json(['user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]]);
        } catch (Throwable) { return $this->json(['error' => 'The login service is temporarily unavailable.'], 503); }
    }

    public function logout(Request $request): Response
    {
        $this->session->remove('user_id'); $this->session->regenerate();
        return $this->json(['ok' => true]);
    }
}
