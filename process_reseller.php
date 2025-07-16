<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $source = $_POST['source'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $software = $_POST['software'] ?? '';
    $plan = $_POST['plan'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $discord_id = $_POST['discordId'] ?? '';
    $discord_invite = $_POST['discordInv'] ?? '';
    $locations = $_POST['locationsLink'] ?? '';
    
    try {
        $query = "INSERT INTO reseller_applications (source, experience, software, plan, phone, email, discord_id, discord_invite, locations) VALUES (:source, :experience, :software, :plan, :phone, :email, :discord_id, :discord_invite, :locations)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':source', $source);
        $stmt->bindParam(':experience', $experience);
        $stmt->bindParam(':software', $software);
        $stmt->bindParam(':plan', $plan);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':discord_id', $discord_id);
        $stmt->bindParam(':discord_invite', $discord_invite);
        $stmt->bindParam(':locations', $locations);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Aplicação enviada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao enviar aplicação.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}
?>