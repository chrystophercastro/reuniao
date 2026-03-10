<?php
/**
 * Controller: AuthController
 */

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Processar login
     */
    public function login(string $email, string $password): array
    {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Preencha todos os campos.'];
        }

        $user = $this->userModel->authenticate($email, $password);

        if ($user) {
            // Iniciar sessão
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            return ['success' => true, 'message' => 'Login realizado com sucesso!'];
        }

        return ['success' => false, 'message' => 'Email ou senha inválidos.'];
    }

    /**
     * Processar logout
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        header('Location: /reuniao/views/login.php');
        exit;
    }

    /**
     * Verificar se está logado
     */
    public static function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Verificar se é admin
     */
    public static function isAdmin(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Requer autenticação
     */
    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /reuniao/views/login.php');
            exit;
        }
    }

    /**
     * Requer admin
     */
    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            header('Location: /reuniao/views/dashboard.php');
            exit;
        }
    }

    /**
     * Retornar dados do usuário logado
     */
    public static function getUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role'  => $_SESSION['user_role'],
        ];
    }
}
