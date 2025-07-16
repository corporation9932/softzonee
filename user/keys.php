<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->getConnection();

// Buscar keys do usuário
$query = "SELECT s.*, p.name as product_name, pk.key_value, s.expires_at, s.created_at as purchase_date
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    LEFT JOIN product_keys pk ON s.key_id = pk.id 
    WHERE s.user_id = :user_id AND s.payment_status = 'completed'
    ORDER BY s.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Keys - SoftZone</title>
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
                    <span class="text-xl font-bold">Minhas Keys</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">← Dashboard</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-4">Minhas <span class="text-gray-400">Licenças</span></h1>
            <p class="text-white/70">Gerencie suas keys e licenças ativas</p>
        </div>

        <?php if (empty($keys)): ?>
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-12 text-center">
                <div class="text-6xl mb-4">🔑</div>
                <h3 class="text-xl font-bold mb-2">Nenhuma licença encontrada</h3>
                <p class="text-white/70 mb-6">Você ainda não possui nenhuma licença ativa.</p>
                <a href="shop.php" class="bg-white hover:bg-gray-100 text-black font-semibold px-6 py-3 rounded-lg transition-all">
                    Ir para a Loja
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($keys as $key): ?>
                    <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-all">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($key['product_name']); ?></h3>
                            <?php 
                            $isExpired = $key['expires_at'] && strtotime($key['expires_at']) < time();
                            $statusColor = $isExpired ? 'bg-red-500/20 text-red-400' : 'bg-green-500/20 text-green-400';
                            $statusText = $isExpired ? 'Expirada' : 'Ativa';
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div>
                                <p class="text-white/70 text-sm">Key:</p>
                                <div class="flex items-center space-x-2">
                                    <code class="bg-white/10 px-3 py-1 rounded font-mono text-sm flex-1" id="key-<?php echo $key['id']; ?>">
                                        <?php echo $key['key_value'] ? htmlspecialchars($key['key_value']) : 'Processando...'; ?>
                                    </code>
                                    <?php if ($key['key_value']): ?>
                                        <button onclick="copyKey('key-<?php echo $key['id']; ?>')" 
                                                class="bg-white/10 hover:bg-white/20 p-2 rounded transition-colors">
                                            📋
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <p class="text-white/70 text-sm">Comprada em:</p>
                                <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($key['purchase_date'])); ?></p>
                            </div>
                            
                            <?php if ($key['expires_at']): ?>
                                <div>
                                    <p class="text-white/70 text-sm">Expira em:</p>
                                    <p class="font-medium <?php echo $isExpired ? 'text-red-400' : 'text-green-400'; ?>">
                                        <?php echo date('d/m/Y H:i', strtotime($key['expires_at'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <p class="text-white/70 text-sm">Valor pago:</p>
                                <p class="font-bold text-green-400">$<?php echo number_format($key['amount'], 2); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!$isExpired && $key['key_value']): ?>
                            <button class="w-full bg-white/10 hover:bg-white/20 text-white py-2 rounded-lg transition-colors">
                                Baixar Software
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyKey(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                // Feedback visual
                const originalText = element.textContent;
                element.textContent = 'Copiado!';
                element.classList.add('text-green-400');
                
                setTimeout(() => {
                    element.textContent = originalText;
                    element.classList.remove('text-green-400');
                }, 2000);
            });
        }
    </script>
</body>
</html>