<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->getConnection();

// Buscar produtos
$query = "SELECT * FROM products WHERE status IN ('active', 'development') ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar dados do usuário
$query = "SELECT balance FROM users WHERE id = :user_id";
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
    <title>Loja - SoftZone</title>
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
                    <span class="text-xl font-bold">Loja</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-green-400 font-medium">Saldo: $<?php echo number_format($user['balance'], 2); ?></span>
                    <a href="dashboard.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">← Dashboard</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-4">Nossa <span class="text-gray-400">Loja</span></h1>
            <p class="text-white/70 text-lg">Escolha o software perfeito para suas necessidades</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($products as $product): ?>
                <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl overflow-hidden hover:bg-white/10 transition-all">
                    <div class="aspect-video relative">
                        <img src="https://github.com/NotCaring/SoftZone-Fotos/blob/main/<?php echo $product['image']; ?>?raw=true" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="w-full h-full object-cover">
                        <div class="absolute top-4 right-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php 
                                echo $product['status'] === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-blue-500/20 text-blue-400'; 
                            ?>">
                                <?php echo $product['status'] === 'active' ? 'Disponível' : 'Em Desenvolvimento'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="text-white/70 mb-4"><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-2xl font-bold text-green-400">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php if ($product['status'] === 'active'): ?>
                                <button onclick="buyProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>)" 
                                        class="bg-white hover:bg-gray-100 text-black font-semibold px-6 py-2 rounded-lg transition-all">
                                    Comprar
                                </button>
                            <?php else: ?>
                                <button disabled class="bg-white/10 text-white/50 font-semibold px-6 py-2 rounded-lg cursor-not-allowed">
                                    Em Breve
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal de Compra -->
    <div id="buyModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold mb-4">Confirmar Compra</h3>
            <div id="buyContent"></div>
            <div class="flex space-x-4 mt-6">
                <button onclick="closeBuyModal()" class="flex-1 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg transition-colors">
                    Cancelar
                </button>
                <button onclick="confirmPurchase()" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentProduct = null;

        function buyProduct(id, name, price) {
            const userBalance = <?php echo $user['balance']; ?>;
            
            if (userBalance < price) {
                alert('Saldo insuficiente! Adicione fundos à sua conta.');
                return;
            }

            currentProduct = { id, name, price };
            
            document.getElementById('buyContent').innerHTML = `
                <p class="text-white/70 mb-4">Você está prestes a comprar:</p>
                <div class="bg-white/5 rounded-lg p-4 mb-4">
                    <p class="font-bold">${name}</p>
                    <p class="text-green-400 text-lg">$${price.toFixed(2)}</p>
                </div>
                <p class="text-white/70 text-sm">Saldo atual: $${userBalance.toFixed(2)}</p>
                <p class="text-white/70 text-sm">Saldo após compra: $${(userBalance - price).toFixed(2)}</p>
            `;
            
            document.getElementById('buyModal').classList.remove('hidden');
            document.getElementById('buyModal').classList.add('flex');
        }

        function closeBuyModal() {
            document.getElementById('buyModal').classList.add('hidden');
            document.getElementById('buyModal').classList.remove('flex');
            currentProduct = null;
        }

        function confirmPurchase() {
            if (!currentProduct) return;
            
            // Aqui você implementaria a lógica de compra via AJAX
            alert('Funcionalidade de compra será implementada em breve!');
            closeBuyModal();
        }
    </script>
</body>
</html>