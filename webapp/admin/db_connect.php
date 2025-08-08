<?php
session_start(); // Iniciar sessão para autenticação
try {
    $db = new PDO('sqlite:/opt/captive-govbr/webapp/db/captive.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>
