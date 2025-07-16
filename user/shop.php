<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->getConnection();

// Processar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy') {
    $product_id = $_POST['product_id'];
    $payment_method = $_POST['payment_method'];
    
    // Buscar produto
    $query = "SELECT * FROM products WHERE id = :id AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        // Buscar usuário
        $query = "SELECT balance FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment_method === 'balance') {
            if ($user['balance'] >= $product['price']) {
                // Processar compra com saldo
                $db->beginTransaction();
                try {
                    // Criar venda
                    $query = "INSERT INTO sales (user_id, product_id, amount, payment_method, payment_status, expires_at) 
                              VALUES (:user_id, :product_id, :amount, 'balance', 'completed', DATE_ADD(NOW(), INTERVAL 30 DAY))";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                    $stmt->bindParam(':product_id', $product_id);
                    $stmt->bindParam(':amount', $product['price']);
                    $stmt->execute();
                    
                    $sale_id = $db->lastInsertId();
                    
                    // Atualizar saldo do usuário
                    $new_balance = $user['balance'] - $product['price'];
                    $query = "UPDATE users SET balance = :balance, total_spent = total_spent + :amount WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':balance', $new_balance);
                    $stmt->bindParam(':amount', $product['price']);
                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                    $stmt->execute();
                    
                    // Criar transação
                    $query = "INSERT INTO transactions (user_id, type, amount, description, reference_id) 
                              VALUES (:user_id, 'purchase', :amount, :description, :reference_id)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                    $stmt->bindParam(':amount', $product['price']);
                    $description = 'Compra: ' . $product['name'];
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':reference_id', $sale_id);
                    $stmt->execute();
                    
                    // Gerar key
                    $key_value = 'SK-' . strtoupper(bin2hex(random_bytes(8)));
                    $query = "INSERT INTO product_keys (product_id, key_value, status) VALUES (:product_id, :key_value, 'sold')";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':product_id', $product_id);
                    $stmt->bindParam(':key_value', $key_value);
                    $stmt->execute();
                    
                    $key_id = $db->lastInsertId();
                    
                    // Atualizar venda com key
                    $query = "UPDATE sales SET key_id = :key_id WHERE id = :sale_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':key_id', $key_id);
                    $stmt->bindParam(':sale_id', $sale_id);
                    $stmt->execute();
                    
                    $db->commit();
                    header('Location: keys.php?success=1');
                    exit();
                } catch (Exception $e) {
                    $db->rollback();
                    $error = 'Erro ao processar compra';
                }
            } else {
                $error = 'Saldo insuficiente';
            }
        } elseif ($payment_method === 'pix') {
            // Criar venda pendente para PIX
            $query = "INSERT INTO sales (user_id, product_id, amount, payment_method, payment_status, expires_at) 
                      VALUES (:user_id, :product_id, :amount, 'pix', 'pending', DATE_ADD(NOW(), INTERVAL 30 DAY))";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':amount', $product['price']);
            $stmt->execute();
            
            $sale_id = $db->lastInsertId();
            header('Location: checkout.php?sale_id=' . $sale_id);
            exit();
        }
    }
}

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
        <?php if (isset($error)): ?>
            <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 mb-6">
                <p class="text-red-400 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

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
                        
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-2xl font-bold text-green-400">$<?php echo number_format($product['price'], 2); ?></span>
                        </div>
                        
                        <?php if ($product['status'] === 'active'): ?>
                            <button onclick="openBuyModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>)" 
                                    class="w-full bg-white hover:bg-gray-100 text-black font-semibold py-3 px-6 rounded-lg transition-all">
                                Comprar Agora
                            </button>
                        <?php else: ?>
                            <a href="../Development.html" class="block w-full bg-white/10 text-white/50 font-semibold py-3 px-6 rounded-lg text-center">
                                Em Desenvolvimento
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal de Compra -->
    <div id="buyModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold mb-4">Escolha o método de pagamento</h3>
            <div id="buyContent"></div>
            <form method="POST" id="buyForm">
                <input type="hidden" name="action" value="buy">
                <input type="hidden" name="product_id" id="modalProductId">
                <input type="hidden" name="payment_method" id="modalPaymentMethod">
                
                <div class="space-y-4 mb-6">
                    <button type="button" onclick="selectPayment('balance')" 
                            class="w-full bg-green-500/20 hover:bg-green-500/30 border border-green-500/20 text-white px-4 py-3 rounded-lg transition-colors text-left">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">💰 Usar Saldo</p>
                                <p class="text-sm text-white/70">Saldo atual: $<?php echo number_format($user['balance'], 2); ?></p>
                            </div>
                            <span class="text-green-400">Instantâneo</span>
                        </div>
                    </button>
                    
                    <button type="button" onclick="selectPayment('pix')" 
                            class="w-full bg-blue-500/20 hover:bg-blue-500/30 border border-blue-500/20 text-white px-4 py-3 rounded-lg transition-colors text-left">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">🏦 PIX</p>
                                <p class="text-sm text-white/70">Pagamento via PIX</p>
                            </div>
                            <span class="text-blue-400">Rápido</span>
                        </div>
                    </button>
                </div>
                
                <div class="flex space-x-4">
                    <button type="button" onclick="closeBuyModal()" class="flex-1 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentProduct = null;

        function openBuyModal(id, name, price) {
            currentProduct = { id, name, price };
            
            document.getElementById('buyContent').innerHTML = `
                <div class="bg-white/5 rounded-lg p-4 mb-6">
                    <p class="font-bold text-lg">${name}</p>
                    <p class="text-green-400 text-xl">$${price.toFixed(2)}</p>
                </div>
            `;
            
            document.getElementById('modalProductId').value = id;
            document.getElementById('buyModal').classList.remove('hidden');
            document.getElementById('buyModal').classList.add('flex');
        }

        function closeBuyModal() {
            document.getElementById('buyModal').classList.add('hidden');
            document.getElementById('buyModal').classList.remove('flex');
            currentProduct = null;
        }

        function selectPayment(method) {
            if (!currentProduct) return;
            
            const userBalance = <?php echo $user['balance']; ?>;
            
            if (method === 'balance' && userBalance < currentProduct.price) {
                alert('Saldo insuficiente! Adicione fundos à sua conta.');
                return;
            }
            
            document.getElementById('modalPaymentMethod').value = method;
            document.getElementById('buyForm').submit();
        }
    </script>
</body>
</html>