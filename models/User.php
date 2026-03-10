<?php
/**
 * Model: User
 */

require_once __DIR__ . '/../config/database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getConnection();
        $this->ensurePhoneColumn();
    }

    /**
     * Garante que a coluna phone exista
     */
    private function ensurePhoneColumn(): void
    {
        try {
            $this->db->query("SELECT phone FROM users LIMIT 1");
        } catch (\PDOException $e) {
            $this->db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT '' AFTER email");
        }
    }

    /**
     * Buscar usuário por ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Buscar usuário por email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Listar todos os usuários
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT id, name, email, phone, role, created_at FROM users ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Criar novo usuário
     */
    public function create(string $name, string $email, string $password, string $role = 'user', string $phone = ''): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, password, role, phone) VALUES (:name, :email, :password, :role, :phone)"
        );
        $stmt->execute([
            'name'     => $name,
            'email'    => $email,
            'password' => $hash,
            'role'     => $role,
            'phone'    => $phone,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualizar usuário
     */
    public function update(int $id, string $name, string $email, string $role, string $phone = ''): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET name = :name, email = :email, role = :role, phone = :phone WHERE id = :id"
        );
        return $stmt->execute([
            'id'    => $id,
            'name'  => $name,
            'email' => $email,
            'role'  => $role,
            'phone' => $phone,
        ]);
    }

    /**
     * Atualizar senha
     */
    public function updatePassword(int $id, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute(['id' => $id, 'password' => $hash]);
    }

    /**
     * Deletar usuário
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Verificar login
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        return null;
    }

    /**
     * Contar total de usuários
     */
    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM users");
        return (int) $stmt->fetch()['total'];
    }
}
