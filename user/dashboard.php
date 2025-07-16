<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->getConnection();

// Buscar dados do usuário
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar estatísticas
$stats_query = "SELECT 
    COUNT(s.id) as total_purchases,
    COALESCE(SUM(s.amount), 0) as total_spent,
    COUNT(CASE WHEN s.payment_status = 'completed' THEN 1 END) as active_licenses
    FROM sales s WHERE s.user_id = :user_id";
$stmt = $db->prepare($stats_query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar compras recentes
$purchases_query = "SELECT s.*, p.name as product_name, pk.key_value, s.expires_at 
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    LEFT JOIN product_keys pk ON s.key_id = pk.id 
    WHERE s.user_id = :user_id 
    ORDER BY s.created_at DESC LIMIT 10";
$stmt = $db->prepare($purchases_query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SoftZone</title>
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
                    <span class="text-xl font-bold">Dashboard</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-white/70">Olá, <?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="profile.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">Perfil</a>
                    <a href="../logout.php" class="bg-red-500/20 hover:bg-red-500/30 px-4 py-2 rounded-lg transition-colors">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6">
                <h3 class="text-white/70 text-sm font-medium mb-2">Saldo Atual</h3>
                <p class="text-2xl font-bold text-green-400">$<?php echo number_format($user['balance'], 2); ?></p>
            </div>
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6">
                <h3 class="text-white/70 text-sm font-medium mb-2">Total Gasto</h3>
                <p class="text-2xl font-bold text-white">$<?php echo number_format($stats['total_spent'], 2); ?></p>
            </div>
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6">
                <h3 class="text-white/70 text-sm font-medium mb-2">Compras</h3>
                <p class="text-2xl font-bold text-blue-400"><?php echo $stats['total_purchases']; ?></p>
            </div>
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6">
                <h3 class="text-white/70 text-sm font-medium mb-2">Licenças Ativas</h3>
                <p class="text-2xl font-bold text-purple-400"><?php echo $stats['active_licenses']; ?></p>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="shop.php" class="bg-gradient-to-r from-blue-500/20 to-purple-500/20 border border-white/10 rounded-2xl p-6 hover:from-blue-500/30 hover:to-purple-500/30 transition-all">
                <h3 class="text-xl font-bold mb-2">🛒 Loja</h3>
                <p class="text-white/70">Comprar novos softwares e licenças</p>
            </a>
            <a href="keys.php" class="bg-gradient-to-r from-green-500/20 to-blue-500/20 border border-white/10 rounded-2xl p-6 hover:from-green-500/30 hover:to-blue-500/30 transition-all">
                <h3 class="text-xl font-bold mb-2">🔑 Minhas Keys</h3>
                <p class="text-white/70">Gerenciar suas licenças ativas</p>
            </a>
            <a href="transactions.php" class="bg-gradient-to-r from-purple-500/20 to-pink-500/20 border border-white/10 rounded-2xl p-6 hover:from-purple-500/30 hover:to-pink-500/30 transition-all">
                <h3 class="text-xl font-bold mb-2">💰 Transações</h3>
                <p class="text-white/70">Histórico de pagamentos</p>
            </a>
        </div>

        <!-- Compras Recentes -->
        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6">
            <h2 class="text-xl font-bold mb-6">Compras Recentes</h2>
            <?php if (empty($purchases)): ?>
                <p class="text-white/70 text-center py-8">Nenhuma compra realizada ainda.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left py-3 text-white/70">Produto</th>
                                <th class="text-left py-3 text-white/70">Valor</th>
                                <th class="text-left py-3 text-white/70">Status</th>
                                <th class="text-left py-3 text-white/70">Expira em</th>
                                <th class="text-left py-3 text-white/70">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $purchase): ?>
                                <tr class="border-b border-white/5">
                                    <td class="py-3 font-medium"><?php echo htmlspecialchars($purchase['product_name']); ?></td>
                                    <td class="py-3 text-green-400">$<?php echo number_format($purchase['amount'], 2); ?></td>
                                    <td class="py-3">
                                        <span class="px-2 py-1 rounded-full text-xs <?php 
                                            echo $purchase['payment_status'] === 'completed' ? 'bg-green-500/20 text-green-400' : 
                                                ($purchase['payment_status'] === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400'); 
                                        ?>">
                                            <?php echo ucfirst($purchase['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 text-white/70">
                                        <?php echo $purchase['expires_at'] ? date('d/m/Y', strtotime($purchase['expires_at'])) : 'N/A'; ?>
                                    </td>
                                    <td class="py-3 text-white/70"><?php echo date('d/m/Y', strtotime($purchase['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>