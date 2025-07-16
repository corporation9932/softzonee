<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function login($username, $password) {
        $query = "SELECT * FROM users WHERE (username = :username OR email = :username) AND status = 'active'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                $this->logActivity($user['id'], 'login', 'User logged in');
                return true;
            }
        }
        return false;
    }
    
    public function register($username, $email, $password, $full_name) {
        // Verificar se usuário já existe
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return false;
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, full_name) VALUES (:username, :email, :password, :full_name)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':full_name', $full_name);
        
        if ($stmt->execute()) {
            $user_id = $this->db->lastInsertId();
            $this->logActivity($user_id, 'register', 'User registered');
            return true;
        }
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ../login.php');
            exit();
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: ../user/dashboard.php');
            exit();
        }
    }
    
    private function logActivity($user_id, $action, $description) {
        $query = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (:user_id, :action, :description, :ip, :user_agent)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR'] ?? '');
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
        $stmt->execute();
    }
}
?>