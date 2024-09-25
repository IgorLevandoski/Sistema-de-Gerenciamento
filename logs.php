<?php
// Conexão com o banco de dados SQLite
$db = new PDO('sqlite:database.db');

// Define o cabeçalho para JSON
header('Content-Type: application/json');

// Lógica GET para listar todos os logs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = $db->query("SELECT * FROM Log");
    $logs = $query->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($logs);
} else {
    http_response_code(405); // Método não permitido
    echo json_encode(["message" => "Método não permitido"]);
}
?>
