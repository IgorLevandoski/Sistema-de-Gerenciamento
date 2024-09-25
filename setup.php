<?php
// Conexão com o banco de dados SQLite (cria o arquivo se não existir)
$db = new PDO('sqlite:database.db');

// Criação da tabela Produto
$db->exec("CREATE TABLE Produto (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    descricao TEXT,
    preco REAL NOT NULL CHECK(preco > 0),
    estoque INTEGER NOT NULL CHECK(estoque >= 0),
    userInsert TEXT,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP
);");

// Criação da tabela Log
$db->exec("CREATE TABLE IF NOT EXISTS Log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    acao TEXT NOT NULL,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    produto_id INTEGER NOT NULL,
    userInsert TEXT NOT NULL,
    FOREIGN KEY (produto_id) REFERENCES Produto(id)
)");

echo "Tabelas criadas com sucesso!";
?>
