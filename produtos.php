<?php
// Conexão com o banco de dados SQLite
$db = new PDO('sqlite:database.db');

// Define o cabeçalho para JSON
header('Content-Type: application/json');

function registrarLog($acao, $produto_id, $userInsert) {
    global $db;
    $stmt = $db->prepare("INSERT INTO Log (acao, produto_id, userInsert) VALUES (:acao, :produto_id, :userInsert)");
    $stmt->bindParam(':acao', $acao);
    $stmt->bindParam(':produto_id', $produto_id);
    $stmt->bindParam(':userInsert', $userInsert);
    
    if ($stmt->execute()) {
        return true;
    } else {
        // Log do erro caso a inserção falhe
        error_log("Erro ao registrar log: " . implode(", ", $stmt->errorInfo()));
        return false;
    }
}

// Verifica o método da requisição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lê os dados enviados via POST (JSON)
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validação de campos obrigatórios
    if (isset($input['nome'], $input['preco'], $input['estoque']) &&
        strlen($input['nome']) >= 3 &&
        $input['preco'] > 0 &&
        $input['estoque'] >= 0) {
        
        // Prepara a consulta para inserir o produto
        $stmt = $db->prepare("INSERT INTO Produto (nome, descricao, preco, estoque, userInsert) 
                              VALUES (:nome, :descricao, :preco, :estoque, :userInsert)");
        $stmt->bindParam(':nome', $input['nome']);
        $stmt->bindParam(':descricao', $input['descricao']);
        $stmt->bindParam(':preco', $input['preco']);
        $stmt->bindParam(':estoque', $input['estoque']);
        $stmt->bindParam(':userInsert', $input['userInsert']);

        if ($stmt->execute()) {
            $produtoId = $db->lastInsertId();
            // Registrar log da inserção
            registrarLog('inserção', $produtoId, $input['userInsert']);
            http_response_code(201);
            echo json_encode(["message" => "Produto criado com sucesso", "id" => $produtoId]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao criar o produto"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Dados inválidos. Verifique os campos."]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Lógica GET (para listar produtos)
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $query = $db->prepare("SELECT * FROM Produto WHERE id = :id");
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
        $produto = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($produto) {
            echo json_encode($produto);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Produto não encontrado"]);
        }
    } else {
        $query = $db->query("SELECT * FROM Produto");
        $produtos = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($produtos);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Lê os dados enviados via PUT (JSON)
    $id = $_GET['id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['nome'], $input['preco'], $input['estoque']) &&
        strlen($input['nome']) >= 3 &&
        $input['preco'] > 0 &&
        $input['estoque'] >= 0) {
        
        $stmt = $db->prepare("UPDATE Produto SET nome = :nome, descricao = :descricao, 
                              preco = :preco, estoque = :estoque, userInsert = :userInsert 
                              WHERE id = :id");
        $stmt->bindParam(':nome', $input['nome']);
        $stmt->bindParam(':descricao', $input['descricao']);
        $stmt->bindParam(':preco', $input['preco']);
        $stmt->bindParam(':estoque', $input['estoque']);
        $stmt->bindParam(':userInsert', $input['userInsert']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                // Registrar log da atualização
                registrarLog('atualização', $id, $input['userInsert']);
                echo json_encode(["message" => "Produto atualizado com sucesso"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Produto não encontrado"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar o produto"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Dados inválidos. Verifique os campos."]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Lógica para deletar um produto
    $id = $_GET['id'];
    
    // Obter o produto antes de excluir para registrar o log
    $query = $db->prepare("SELECT * FROM Produto WHERE id = :id");
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $produto = $query->fetch(PDO::FETCH_ASSOC);
    
    if ($produto) {
        // Obtenha o userInsert do produto antes de excluir
        $userInsert = $produto['userInsert'];

        // Registrar log antes da exclusão
        if (!registrarLog('exclusão', $produto['id'], $userInsert)) {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao registrar log"]);
            exit;
        }
        
        // Excluir o produto
        $stmt = $db->prepare("DELETE FROM Produto WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode(["message" => "Produto excluído com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao excluir o produto"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Produto não encontrado"]);
    }
}
?>
