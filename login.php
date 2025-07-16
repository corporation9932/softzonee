<?php
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';
$success = '';

// Processar login
if (isset($_POST['action']) && $_POST['action'] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: user/dashboard.php');
        }
        exit();
    } else {
        $error = 'Credenciais inválidas ou conta suspensa';
    }
}

// Processar registro
if (isset($_POST['action']) && $_POST['action'] === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    
    if ($password !== $confirm_password) {
        $error = 'As senhas não coincidem';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres';
    } elseif ($auth->register($username, $email, $password, $full_name)) {
        $success = 'Conta criada com sucesso! Faça login para continuar.';
    } else {
        $error = 'Usuário ou email já existe';
    }
}

if ($auth->isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoftZone - Login & Registro</title>
    <link href="https://github.com/NotCaring/SoftZone-Fotos/blob/main/Logo.png?raw=true" rel="icon" sizes="16x16" type="image/gif"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #000;
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-black text-white min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-auto p-6">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="https://github.com/NotCaring/SoftZone-Fotos/blob/main/letras.png?raw=true" alt="SoftZone" class="h-12 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-white">Bem-vindo ao SoftZone</h1>
        </div>

        <!-- Mensagens -->
        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 mb-6">
                <p class="text-red-400 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4 mb-6">
                <p class="text-green-400 text-sm"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="flex mb-6">
            <button id="loginTab" class="flex-1 py-3 px-4 text-center font-medium rounded-l-lg bg-white/10 text-white border-r border-white/10 transition-colors">
                Login
            </button>
            <button id="registerTab" class="flex-1 py-3 px-4 text-center font-medium rounded-r-lg bg-white/5 text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                Registro
            </button>
        </div>

        <!-- Login Form -->
        <div id="loginForm" class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="login">
                
                <div>
                    <label class="block text-white mb-2 font-medium">Usuário ou Email</label>
                    <input type="text" name="username" required 
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-medium">Senha</label>
                    <input type="password" name="password" required 
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <button type="submit" 
                        class="w-full bg-white hover:bg-gray-100 text-black font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                    Entrar
                </button>
            </form>
        </div>

        <!-- Register Form -->
        <div id="registerForm" class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hidden">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="register">
                
                <div>
                    <label class="block text-white mb-2 font-medium">Nome Completo</label>
                    <input type="text" name="full_name" required 
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-medium">Usuário</label>
                    <input type="text" name="username" required 
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-medium">Email</label>
                    <input type="email" name="email" required 
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-medium">Senha</label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-medium">Confirmar Senha</label>
                    <input type="password" name="confirm_password" required minlength="6"
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <button type="submit" 
                        class="w-full bg-white hover:bg-gray-100 text-black font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                    Criar Conta
                </button>
            </form>
        </div>

        <!-- Voltar -->
        <div class="text-center mt-6">
            <a href="index.html" class="text-white/70 hover:text-white transition-colors">
                ← Voltar ao site
            </a>
        </div>
    </div>

    <script>
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        loginTab.addEventListener('click', () => {
            loginTab.classList.add('bg-white/10', 'text-white');
            loginTab.classList.remove('bg-white/5', 'text-white/70');
            registerTab.classList.add('bg-white/5', 'text-white/70');
            registerTab.classList.remove('bg-white/10', 'text-white');
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
        });

        registerTab.addEventListener('click', () => {
            registerTab.classList.add('bg-white/10', 'text-white');
            registerTab.classList.remove('bg-white/5', 'text-white/70');
            loginTab.classList.add('bg-white/5', 'text-white/70');
            loginTab.classList.remove('bg-white/10', 'text-white');
            registerForm.classList.remove('hidden');
            loginForm.classList.add('hidden');
        });
    </script>
</body>
</html>