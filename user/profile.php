<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verificar senha atual se nova senha foi fornecida
    if (!empty($new_password)) {
        $query = "SELECT password FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($current_password, $user_data['password'])) {
            $error = 'Senha atual incorreta';
        } elseif ($new_password !== $confirm_password) {
            $error = 'As novas senhas não coincidem';
        } elseif (strlen($new_password) < 6) {
            $error = 'A nova senha deve ter pelo menos 6 caracteres';
        }
    }
    
    if (empty($error)) {
        $query = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone";
        $params = [
            ':full_name' => $full_name,
            ':email' => $email,
            ':phone' => $phone,
            ':user_id' => $_SESSION['user_id']
        ];
        
        if (!empty($new_password)) {
            $query .= ", password = :password";
            $params[':password'] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        
        $query .= " WHERE id = :user_id";
        
        $stmt = $db->prepare($query);
        if ($stmt->execute($params)) {
            $success = 'Perfil atualizado com sucesso!';
            $_SESSION['full_name'] = $full_name;
        } else {
            $error = 'Erro ao atualizar perfil';
        }
    }
}

// Buscar dados atuais do usuário
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - SoftZone</title>
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
<body class="bg-black text-white min-h-screen">
    <!-- Header -->
    <header class="bg-white/5 backdrop-blur-sm border-b border-white/10">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="https://github.com/NotCaring/SoftZone-Fotos/blob/main/letras.png?raw=true" alt="SoftZone" class="h-8">
                    <span class="text-xl font-bold">Meu Perfil</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">← Dashboard</a>
                    <a href="../logout.php" class="bg-red-500/20 hover:bg-red-500/30 px-4 py-2 rounded-lg transition-colors">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8 max-w-2xl">
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

        <!-- Informações da Conta -->
        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 mb-8">
            <h2 class="text-2xl font-bold mb-6">Informações da Conta</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-white/70 text-sm">Usuário</p>
                    <p class="font-medium"><?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <div>
                    <p class="text-white/70 text-sm">Membro desde</p>
                    <p class="font-medium"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                </div>
                <div>
                    <p class="text-white/70 text-sm">Saldo Atual</p>
                    <p class="font-medium text-green-400">$<?php echo number_format($user['balance'], 2); ?></p>
                </div>
                <div>
                    <p class="text-white/70 text-sm">Total Gasto</p>
                    <p class="font-medium">$<?php echo number_format($user['total_spent'], 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Formulário de Edição -->
        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8">
            <h2 class="text-2xl font-bold mb-6">Editar Perfil</h2>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-white mb-2 font-medium">Nome Completo</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required 
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-medium">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required 
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-medium">Telefone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <hr class="border-white/10">
                
                <h3 class="text-lg font-bold">Alterar Senha</h3>
                <p class="text-white/70 text-sm">Deixe em branco se não quiser alterar a senha</p>
                
                <div>
                    <label class="block text-white mb-2 font-medium">Senha Atual</label>
                    <input type="password" name="current_password" 
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-medium">Nova Senha</label>
                    <input type="password" name="new_password" minlength="6"
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-medium">Confirmar Nova Senha</label>
                    <input type="password" name="confirm_password" minlength="6"
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-transparent transition-all">
                </div>
                
                <button type="submit" 
                        class="w-full bg-white hover:bg-gray-100 text-black font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                    Salvar Alterações
                </button>
            </form>
        </div>
    </div>
</body>
</html>