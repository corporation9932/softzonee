<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $application_id = $_POST['application_id'] ?? '';
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        $query = "UPDATE reseller_applications SET status = :status, admin_notes = :admin_notes WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':admin_notes', $admin_notes);
        $stmt->bindParam(':id', $application_id);
        $stmt->execute();
        
        header('Location: resellers.php?success=1');
        exit();
    }
}

// Buscar aplicações
$query = "SELECT * FROM reseller_applications ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar por status
$stats_query = "SELECT status, COUNT(*) as count FROM reseller_applications GROUP BY status";
$stmt = $db->prepare($stats_query);
$stmt->execute();
$stats_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($stats_raw as $stat) {
    $stats[$stat['status']] = $stat['count'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicações de Reseller - SoftZone Admin</title>
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
                    <span class="text-xl font-bold">Aplicações de Reseller</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">← Dashboard</a>
                    <a href="../logout.php" class="bg-red-500/20 hover:bg-red-500/30 px-4 py-2 rounded-lg transition-colors">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-yellow-500/20 border border-yellow-500/20 rounded-2xl p-6">
                <h3 class="text-yellow-400 text-sm font-medium mb-2">Pendentes</h3>
                <p class="text-3xl font-bold text-white"><?php echo $stats['pending']; ?></p>
            </div>
            <div class="bg-green-500/20 border border-green-500/20 rounded-2xl p-6">
                <h3 class="text-green-400 text-sm font-medium mb-2">Aprovadas</h3>
                <p class="text-3xl font-bold text-white"><?php echo $stats['approved']; ?></p>
            </div>
            <div class="bg-red-500/20 border border-red-500/20 rounded-2xl p-6">
                <h3 class="text-red-400 text-sm font-medium mb-2">Rejeitadas</h3>
                <p class="text-3xl font-bold text-white"><?php echo $stats['rejected']; ?></p>
            </div>
        </div>

        <!-- Lista de Aplicações -->
        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6">
            <h2 class="text-xl font-bold mb-6">Todas as Aplicações</h2>
            
            <?php if (empty($applications)): ?>
                <p class="text-white/70 text-center py-8">Nenhuma aplicação encontrada.</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($applications as $app): ?>
                        <div class="bg-white/5 border border-white/10 rounded-xl p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold"><?php echo htmlspecialchars($app['email']); ?></h3>
                                    <p class="text-white/70 text-sm"><?php echo date('d/m/Y H:i', strtotime($app['created_at'])); ?></p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php 
                                    echo $app['status'] === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 
                                        ($app['status'] === 'approved' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'); 
                                ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-white/70 text-sm">Onde nos encontrou:</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($app['source']); ?></p>
                                </div>
                                <div>
                                    <p class="text-white/70 text-sm">Experiência:</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($app['experience']); ?></p>
                                </div>
                                <div>
                                    <p class="text-white/70 text-sm">Software desejado:</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($app['software']); ?></p>
                                </div>
                                <div>
                                    <p class="text-white/70 text-sm">Plano:</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($app['plan']); ?></p>
                                </div>
                                <div>
                                    <p class="text-white/70 text-sm">Telefone:</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($app['phone']); ?></p>
                                </div>
                                <div>
                                    <p class="text-white/70 text-sm">Discord ID:</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($app['discord_id']); ?></p>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <p class="text-white/70 text-sm">Discord Server:</p>
                                <p class="font-medium"><?php echo htmlspecialchars($app['discord_invite']); ?></p>
                            </div>
                            
                            <div class="mb-4">
                                <p class="text-white/70 text-sm">Locais de venda:</p>
                                <p class="font-medium"><?php echo htmlspecialchars($app['locations']); ?></p>
                            </div>
                            
                            <?php if ($app['admin_notes']): ?>
                                <div class="mb-4">
                                    <p class="text-white/70 text-sm">Notas do Admin:</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($app['admin_notes']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($app['status'] === 'pending'): ?>
                                <div class="flex space-x-4">
                                    <button onclick="openModal(<?php echo $app['id']; ?>, 'approve')" 
                                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                                        Aprovar
                                    </button>
                                    <button onclick="openModal(<?php echo $app['id']; ?>, 'reject')" 
                                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                                        Rejeitar
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="actionModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 id="modalTitle" class="text-xl font-bold mb-4"></h3>
            <form method="POST">
                <input type="hidden" name="action" id="modalAction">
                <input type="hidden" name="application_id" id="modalApplicationId">
                
                <div class="mb-4">
                    <label class="block text-white mb-2">Notas (opcional):</label>
                    <textarea name="admin_notes" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-white/20 h-24"></textarea>
                </div>
                
                <div class="flex space-x-4">
                    <button type="button" onclick="closeModal()" class="flex-1 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="confirmButton" class="flex-1 px-4 py-2 rounded-lg transition-colors">
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(applicationId, action) {
            document.getElementById('modalApplicationId').value = applicationId;
            document.getElementById('modalAction').value = action;
            
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            const button = document.getElementById('confirmButton');
            
            if (action === 'approve') {
                title.textContent = 'Aprovar Aplicação';
                button.textContent = 'Aprovar';
                button.className = 'flex-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors';
            } else {
                title.textContent = 'Rejeitar Aplicação';
                button.textContent = 'Rejeitar';
                button.className = 'flex-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors';
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function closeModal() {
            const modal = document.getElementById('actionModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>
</html>