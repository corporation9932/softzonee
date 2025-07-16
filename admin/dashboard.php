<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Estatísticas gerais
$stats = [];

// Total de usuários
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de vendas
$query = "SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as revenue FROM sales WHERE payment_status = 'completed'";
$stmt = $db->prepare($query);
$stmt->execute();
$sales_data = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_sales'] = $sales_data['total'];
$stats['total_revenue'] = $sales_data['revenue'];

// Produtos ativos
$query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['active_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Keys disponíveis
$query = "SELECT COUNT(*) as total FROM product_keys WHERE status = 'available'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['available_keys'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Vendas recentes
$query = "SELECT s.*, u.username, p.name as product_name 
    FROM sales s 
    JOIN users u ON s.user_id = u.id 
    JOIN products p ON s.product_id = p.id 
    ORDER BY s.created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Usuários recentes
$query = "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SoftZone</title>
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
                    <span class="text-xl font-bold">Admin Dashboard</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-white/70">Admin: <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../index.html" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">Site</a>
                    <a href="../logout.php" class="bg-red-500/20 hover:bg-red-500/30 px-4 py-2 rounded-lg transition-colors">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white/5 backdrop-blur-sm border-r border-white/10 min-h-screen">
            <nav class="p-4">
                <ul class="space-y-2">
                    <li><a href="dashboard.php" class="block px-4 py-2 rounded-lg bg-white/10 text-white">📊 Dashboard</a></li>
                    <li><a href="users.php" class="block px-4 py-2 rounded-lg text-white/70 hover:bg-white/10 hover:text-white transition-colors">👥 Usuários</a></li>
                    <li><a href="products.php" class="block px-4 py-2 rounded-lg text-white/70 hover:bg-white/10 hover:text-white transition-colors">🛍️ Produtos</a></li>
                    <li><a href="keys.php" class="block px-4 py-2 rounded-lg text-white/70 hover:bg-white/10 hover:text-white transition-colors">🔑 Keys</a></li>
                    <li><a href="sales.php" class="block px-4 py-2 rounded-lg text-white/70 hover:bg-white/10 hover:text-white transition-colors">💰 Vendas</a></li>
                    <li><a href="transactions.php" class="block px-4 py-2 rounded-lg text-white/70 hover:bg-white/10 hover:text-white transition-colors">💳 Transações</a></li>
                    <li><a href="settings.php" class="block px-4 py-2 rounded-lg text-white/70 hover:bg-white/10 hover:text-white transition-colors">⚙️ Configurações</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <!-- Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <div class="bg-gradient-to-r from-blue-500/20 to-blue-600/20 border border-blue-500/20 rounded-2xl p-6">
                    <h3 class="text-blue-400 text-sm font-medium mb-2">Total Usuários</h3>
                    <p class="text-3xl font-bold text-white"><?php echo number_format($stats['total_users']); ?></p>
                </div>
                <div class="bg-gradient-to-r from-green-500/20 to-green-600/20 border border-green-500/20 rounded-2xl p-6">
                    <h3 class="text-green-400 text-sm font-medium mb-2">Receita Total</h3>
                    <p class="text-3xl font-bold text-white">$<?php echo number_format($stats['total_revenue'], 2); ?></p>
                </div>
                <div class="bg-gradient-to-r from-purple-500/20 to-purple-600/20 border border-purple-500/20 rounded-2xl p-6">
                    <h3 class="text-purple-400 text-sm font-medium mb-2">Total Vendas</h3>
                    <p class="text-3xl font-bold text-white"><?php echo number_format($stats['total_sales']); ?></p>
                </div>
                <div class="bg-gradient-to-r from-yellow-500/20 to-yellow-600/20 border border-yellow-500/20 rounded-2xl p-6">
                    <h3 class="text-yellow-400 text-sm font-medium mb-2">Produtos Ativos</h3>
                    <p class="text-3xl font-bold text-white"><?php echo number_format($stats['active_products']); ?></p>
                </div>
                <div class="bg-gradient-to-r from-red-500/20 to-red-600/20 border border-red-500/20 rounded-2xl p-6">
                    <h3 class="text-red-400 text-sm font-medium mb-2">Keys Disponíveis</h3>
                    <p class="text-3xl font-bold text-white"><?php echo number_format($stats['available_keys']); ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Vendas Recentes -->
                <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6">
                    <h2 class="text-xl font-bold mb-6">Vendas Recentes</h2>
                    <div class="space-y-4">
                        <?php foreach ($recent_sales as $sale): ?>
                            <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($sale['product_name']); ?></p>
                                    <p class="text-sm text-white/70">por <?php echo htmlspecialchars($sale['username']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-400">$<?php echo number_format($sale['amount'], 2); ?></p>
                                    <p class="text-xs text-white/70"><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Usuários Recentes -->
                <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6">
                    <h2 class="text-xl font-bold mb-6">Usuários Recentes</h2>
                    <div class="space-y-4">
                        <?php foreach ($recent_users as $user): ?>
                            <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                    <p class="text-sm text-white/70">@<?php echo htmlspecialchars($user['username']); ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 rounded-full text-xs <?php 
                                        echo $user['status'] === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'; 
                                    ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                    <p class="text-xs text-white/70 mt-1"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>