<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->getConnection();

$sale_id = $_GET['sale_id'] ?? 0;

// Buscar venda
$query = "SELECT s.*, p.name as product_name, p.image 
          FROM sales s 
          JOIN products p ON s.product_id = p.id 
          WHERE s.id = :sale_id AND s.user_id = :user_id AND s.payment_status = 'pending'";
$stmt = $db->prepare($query);
$stmt->bindParam(':sale_id', $sale_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    header('Location: shop.php');
    exit();
}

// Gerar código PIX (simulado)
$pix_code = '00020126580014BR.GOV.BCB.PIX0136' . uniqid() . '5204000053039865802BR5925SOFTZONE TECNOLOGIA LTDA6009SAO PAULO62070503***6304' . strtoupper(substr(md5($sale_id), 0, 4));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout PIX - SoftZone</title>
    <link href="https://github.com/NotCaring/SoftZone-Fotos/blob/main/Logo.png?raw=true" rel="icon" sizes="16x16" type="image/gif"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
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
                    <span class="text-xl font-bold">Checkout PIX</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="shop.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">← Voltar</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-4">Pagamento via PIX</h1>
                <p class="text-white/70">Escaneie o QR Code ou copie o código PIX</p>
            </div>

            <!-- Detalhes do Produto -->
            <div class="bg-white/5 rounded-xl p-6 mb-8">
                <div class="flex items-center space-x-4">
                    <img src="https://github.com/NotCaring/SoftZone-Fotos/blob/main/<?php echo $sale['image']; ?>?raw=true" 
                         alt="<?php echo htmlspecialchars($sale['product_name']); ?>" 
                         class="w-16 h-16 object-cover rounded-lg">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold"><?php echo htmlspecialchars($sale['product_name']); ?></h3>
                        <p class="text-2xl font-bold text-green-400">$<?php echo number_format($sale['amount'], 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- QR Code -->
            <div class="text-center mb-8">
                <div class="bg-white p-4 rounded-xl inline-block mb-4">
                    <canvas id="qrcode"></canvas>
                </div>
                <p class="text-white/70 text-sm">Escaneie com o app do seu banco</p>
            </div>

            <!-- Código PIX -->
            <div class="mb-8">
                <label class="block text-white mb-2 font-medium">Código PIX:</label>
                <div class="flex items-center space-x-2">
                    <input type="text" id="pixCode" value="<?php echo $pix_code; ?>" readonly 
                           class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white font-mono text-sm">
                    <button onclick="copyPixCode()" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition-colors">
                        Copiar
                    </button>
                </div>
            </div>

            <!-- Instruções -->
            <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-6 mb-8">
                <h3 class="font-bold mb-4">📱 Como pagar:</h3>
                <ol class="space-y-2 text-sm text-white/80">
                    <li>1. Abra o app do seu banco</li>
                    <li>2. Procure pela opção PIX</li>
                    <li>3. Escaneie o QR Code ou cole o código</li>
                    <li>4. Confirme o pagamento</li>
                    <li>5. Aguarde a confirmação (até 5 minutos)</li>
                </ol>
            </div>

            <!-- Status -->
            <div class="text-center">
                <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-xl p-6">
                    <div class="animate-pulse text-yellow-400 text-2xl mb-2">⏳</div>
                    <p class="font-bold text-yellow-400">Aguardando Pagamento</p>
                    <p class="text-white/70 text-sm">Verificando pagamento automaticamente...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gerar QR Code
        QRCode.toCanvas(document.getElementById('qrcode'), '<?php echo $pix_code; ?>', {
            width: 200,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#FFFFFF'
            }
        });

        function copyPixCode() {
            const pixCode = document.getElementById('pixCode');
            pixCode.select();
            document.execCommand('copy');
            
            // Feedback visual
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Copiado!';
            button.classList.add('bg-green-500');
            button.classList.remove('bg-blue-500');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('bg-green-500');
                button.classList.add('bg-blue-500');
            }, 2000);
        }

        // Verificar pagamento (simulado)
        let checkCount = 0;
        const maxChecks = 60; // 5 minutos

        function checkPayment() {
            checkCount++;
            
            // Simular confirmação após 30 segundos (para demonstração)
            if (checkCount > 6) {
                // Redirecionar para sucesso (em produção, verificar via webhook)
                window.location.href = 'keys.php?payment=success';
                return;
            }
            
            if (checkCount < maxChecks) {
                setTimeout(checkPayment, 5000); // Verificar a cada 5 segundos
            }
        }

        // Iniciar verificação
        setTimeout(checkPayment, 5000);
    </script>
</body>
</html>