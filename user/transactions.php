<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->getConnection();

// Buscar transações do usuário
$query = "SELECT * FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar estatísticas
$stats_query = "SELECT 
    SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposits,
    SUM(CASE WHEN type = 'purchase' THEN amount ELSE 0 END) as total_purchases,
    COUNT(*) as total_transactions
    FROM transactions WHERE user_id = :user_id";
$stmt = $db->prepare($stats_query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transações - SoftZone</title>
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
                    <span class="text-xl font-bold">Transações</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">← Dashboard</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-green-500/20 border border-green-500/20 rounded-2xl p-6">
                <h3 class="text-green-400 text-sm font-medium mb-2">Total Depositado</h3>
                <p class="text-3xl font-bold text-white">$<?php echo number_format($stats['total_deposits'] ?? 0, 2); ?></p>
            </div>
            <div class="bg-red-500/20 border border-red-500/20 rounded-2xl p-6">
                <h3 class="text-red-400 text-sm font-medium mb-2">Total Gasto</h3>
                <p class="text-3xl font-bold text-white">$<?php echo number_format($stats['total_purchases'] ?? 0, 2); ?></p>
            </div>
            <div class="bg-blue-500/20 border border-blue-500/20 rounded-2xl p-6">
                <h3 class="text-blue-400 text-sm font-medium mb-2">Total Transações</h3>
                <p class="text-3xl font-bold text-white"><?php echo $stats['total_transactions'] ?? 0; ?></p>
            </div>
        </div>

        <!-- Lista de Transações -->
        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6">
            <h2 class="text-xl font-bold mb-6">Histórico de Transações</h2>
            
            <?php if (empty($transactions)): ?>
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">💳</div>
                    <h3 class="text-xl font-bold mb-2">Nenhuma transação encontrada</h3>
                    <p class="text-white/70">Suas transações aparecerão aqui quando você fizer depósitos ou compras.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left py-3 text-white/70">Tipo</th>
                                <th class="text-left py-3 text-white/70">Descrição</th>
                                <th class="text-left py-3 text-white/70">Valor</th>
                                <th class="text-left py-3 text-white/70">Status</th>
                                <th class="text-left py-3 text-white/70">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr class="border-b border-white/5">
                                    <td class="py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php 
                                            echo $transaction['type'] === 'deposit' ? 'bg-green-500/20 text-green-400' : 
                                                ($transaction['type'] === 'purchase' ? 'bg-red-500/20 text-red-400' : 
                                                ($transaction['type'] === 'refund' ? 'bg-blue-500/20 text-blue-400' : 'bg-purple-500/20 text-purple-400')); 
                                        ?>">
                                            <?php 
                                            $types = [
                                                'deposit' => 'Depósito',
                                                'purchase' => 'Compra',
                                                'refund' => 'Reembolso',
                                                'bonus' => 'Bônus'
                                            ];
                                            echo $types[$transaction['type']] ?? ucfirst($transaction['type']);
                                            ?>
                                        </span>
                                    </td>
                                    <td class="py-4"><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td class="py-4">
                                        <span class="font-bold <?php echo in_array($transaction['type'], ['deposit', 'refund', 'bonus']) ? 'text-green-400' : 'text-red-400'; ?>">
                                            <?php echo in_array($transaction['type'], ['deposit', 'refund', 'bonus']) ? '+' : '-'; ?>$<?php echo number_format($transaction['amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td class="py-4">
                                        <span class="px-2 py-1 rounded-full text-xs <?php 
                                            echo $transaction['status'] === 'completed' ? 'bg-green-500/20 text-green-400' : 
                                                ($transaction['status'] === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400'); 
                                        ?>">
                                            <?php 
                                            $statuses = [
                                                'completed' => 'Concluída',
                                                'pending' => 'Pendente',
                                                'failed' => 'Falhou'
                                            ];
                                            echo $statuses[$transaction['status']] ?? ucfirst($transaction['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-white/70"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
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