<?php
try {
    $db = new PDO('sqlite:/opt/captive-govbr/webapp/db/captive.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar tabela conexoes
    $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL
    )");

    // Inserir usuário de exemplo (senha: "admin123")
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $db->exec("INSERT OR IGNORE INTO usuarios (username, password) VALUES ('admin', '$password_hash')");

    echo "Banco de dados e tabelas criados com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
