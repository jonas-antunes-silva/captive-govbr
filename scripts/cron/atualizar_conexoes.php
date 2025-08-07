
<?php
// conectar em um bando de dados sqlite3 utilzando PDO
$pdo = new PDO('sqlite:/opt/captive-govbr/webapp/db/captive.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Verifica se a conexão foi bem sucedida
if (!$pdo) {
    die("Erro ao conectar ao banco de dados.");
}

//realizar consulta para obter todas as conexões ativas







//$stmt = $pdo->prepare("SELECT * FROM conexoes_ativas WHERE data_conexao >= :timestamp");
$stmt = $pdo->prepare("SELECT * FROM conexoes_ativas WHERE JULIANDAY('now') - JULIANDAY(data_conexao) >= 1");


$stmt->execute();


// iterar sobre as entradas e executar o comando ipset_del.sh para cada entrada
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $clientIp = $row['ip'];
    $clientMacaddress = $row['macaddress'];
    if ($clientIp && $clientMacaddress) {
        // Executa o comando ipset_del.sh para remover a conexão    
        $output = [];
        $return_var = 0;
        // Escapa os parâmetros para evitar injeção de comandos
        $cmd = escapeshellcmd("sudo /opt/captive-govbr/scripts/ipset_del.sh $clientIp $clientMacaddress");
        exec("$cmd 2>&1", $output, $return_var);
        if ($return_var !== 0) {
            echo "Erro ao remover a conexão $clientIp: " . implode("\n", $output);
        } else {
            echo "Conexão $clientIp removida com sucesso.";
        }
        // Remove a entrada do banco de dados
        $deleteStmt = $pdo->prepare("DELETE FROM conexoes_ativas WHERE ip = :ip");
        $deleteStmt->execute([':ip' => $clientIp]);
        if ($deleteStmt->rowCount() > 0) {
            echo "Entrada do banco de dados para $clientIp removida com sucesso.";
        } else {
            echo "Nenhuma entrada encontrada para $clientIp no banco de dados.";
        }
    }
}
?>