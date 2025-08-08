<?php
require_once 'db_connect.php';

// Verificar se o usuário está.logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Configurações de paginação
$itens_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $itens_por_pagina;

// Filtros
$nome_filtro = isset($_GET['nome']) ? trim($_GET['nome']) : '';
$cpf_filtro = isset($_GET['cpf']) ? trim($_GET['cpf']) : '';
$data_filtro = isset($_GET['data_conexao']) ? trim($_GET['data_conexao']) : '';
$ip_filtro = isset($_GET['ip']) ? trim($_GET['ip']) : '';
$mac_filtro = isset($_GET['macaddress']) ? trim($_GET['macaddress']) : '';

// Construir a consulta SQL com filtros
$sql = "SELECT * FROM conexoes WHERE 1=1";
$params = [];

if ($nome_filtro) {
    $sql .= " AND nome LIKE :nome";
    $params[':nome'] = "%$nome_filtro%";
}
if ($cpf_filtro) {
    $sql .= " AND cpf LIKE :cpf";
    $params[':cpf'] = "%$cpf_filtro%";
}
if ($data_filtro) {
    $sql .= " AND DATE(data_conexao) = :data_conexao";
    $params[':data_conexao'] = $data_filtro;
}
if ($ip_filtro) {
    $sql .= " AND ip LIKE :ip";
    $params[':ip'] = "%$ip_filtro%";
}
if ($mac_filtro) {
    $sql .= " AND macaddress LIKE :macaddress";
    $params[':macaddress'] = "%$mac_filtro%";
}

$sql .= " ORDER BY data_conexao DESC LIMIT :limit OFFSET :offset";
try {
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contar total de registros para paginação
    $count_sql = "SELECT COUNT(*) FROM conexoes WHERE 1=1";
    $count_params = [];
    if ($nome_filtro) {
        $count_sql .= " AND nome LIKE :nome";
        $count_params[':nome'] = "%$nome_filtro%";
    }
    if ($cpf_filtro) {
        $count_sql .= " AND cpf LIKE :cpf";
        $count_params[':cpf'] = "%$cpf_filtro%";
    }
    if ($data_filtro) {
        $count_sql .= " AND DATE(data_conexao) = :data_conexao";
        $count_params[':data_conexao'] = $data_filtro;
    }
    if ($ip_filtro) {
        $count_sql .= " AND ip LIKE :ip";
        $count_params[':ip'] = "%$ip_filtro%";
    }
    if ($mac_filtro) {
        $count_sql .= " AND macaddress LIKE :macaddress";
        $count_params[':macaddress'] = "%$mac_filtro%";
    }
    $count_stmt = $db->prepare($count_sql);
    foreach ($count_params as $key => $value) {
        $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $count_stmt->execute();
    $total_registros = $count_stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $itens_por_pagina);
} catch (PDOException $e) {
    $error = "Erro ao consultar dados: " . $e->getMessage();
}

// Exportação para CSV
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio_conexoes.csv"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // Suporte a UTF-8
    fputcsv($output, ['ID', 'Nome', 'CPF', 'Email', 'IP', 'MAC Address', 'Data de Conexão']);
    
    $export_sql = "SELECT * FROM conexoes WHERE 1=1";
    if ($nome_filtro) {
        $export_sql .= " AND nome LIKE :nome";
    }
    if ($cpf_filtro) {
        $export_sql .= " AND cpf LIKE :cpf";
    }
    if ($data_filtro) {
        $export_sql .= " AND DATE(data_conexao) = :data_conexao";
    }
    if ($ip_filtro) {
        $export_sql .= " AND ip LIKE :ip";
    }
    if ($mac_filtro) {
        $export_sql .= " AND macaddress LIKE :macaddress";
    }
    $export_sql .= " ORDER BY data_conexao DESC";
    $export_stmt = $db->prepare($export_sql);
    foreach ($params as $key => $value) {
        $export_stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $export_stmt->execute();
    while ($row = $export_stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Conexões</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Menu Bootstrap -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Relatório de Conexões</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Relatório de Conexões</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="authenticated_clients.php">Clientes Autenticados</a>
                    </li>
                </ul>
                <span class="navbar-text me-3">Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn btn-danger">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1 class="mb-4">Relatório de Conexões</h1>

        <!-- Formulário de Filtros -->
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome_filtro); ?>">
                </div>
                <div class="col-md-3">
                    <label for="cpf" class="form-label">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf" value="<?php echo htmlspecialchars($cpf_filtro); ?>">
                </div>
                <div class="col-md-3">
                    <label for="ip" class="form-label">IP</label>
                    <input type="text" class="form-control" id="ip" name="ip" value="<?php echo htmlspecialchars($ip_filtro); ?>">
                </div>
                <div class="col-md-3">
                    <label for="macaddress" class="form-label">MAC Address</label>
                    <input type="text" class="form-control" id="macaddress" name="macaddress" value="<?php echo htmlspecialchars($mac_filtro); ?>">
                </div>
                <div class="col-md-3">
                    <label for="data_conexao" class="form-label">Data de Conexão</label>
                    <input type="date" class="form-control" id="data_conexao" name="data_conexao" value="<?php echo htmlspecialchars($data_filtro); ?>">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="?export_csv=1&nome=<?php echo urlencode($nome_filtro); ?>&cpf=<?php echo urlencode($cpf_filtro); ?>&ip=<?php echo urlencode($ip_filtro); ?>&macaddress=<?php echo urlencode($mac_filtro); ?>&data_conexao=<?php echo urlencode($data_filtro); ?>" class="btn btn-success">Exportar CSV</a>
                <a href="index.php" class="btn btn-secondary">Limpar Filtros</a>
            </div>
        </form>

        <!-- Tabela de Resultados -->
        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } else { ?>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Email</th>
                        <th>IP</th>
                        <th>MAC Address</th>
                        <th>Data de Conexão</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($result) > 0) {
                        foreach ($result as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['cpf']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['ip']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['macaddress']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['data_conexao']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>Nenhum registro encontrado.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- Paginação -->
            <?php if ($total_paginas > 1) { ?>
                <nav aria-label="Paginação">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&nome=<?php echo urlencode($nome_filtro); ?>&cpf=<?php echo urlencode($cpf_filtro); ?>&ip=<?php echo urlencode($ip_filtro); ?>&macaddress=<?php echo urlencode($mac_filtro); ?>&data_conexao=<?php echo urlencode($data_filtro); ?>">Anterior</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_paginas; $i++) { ?>
                            <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $i; ?>&nome=<?php echo urlencode($nome_filtro); ?>&cpf=<?php echo urlencode($cpf_filtro); ?>&ip=<?php echo urlencode($ip_filtro); ?>&macaddress=<?php echo urlencode($mac_filtro); ?>&data_conexao=<?php echo urlencode($data_filtro); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php } ?>
                        <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&nome=<?php echo urlencode($nome_filtro); ?>&cpf=<?php echo urlencode($cpf_filtro); ?>&ip=<?php echo urlencode($ip_filtro); ?>&macaddress=<?php echo urlencode($mac_filtro); ?>&data_conexao=<?php echo urlencode($data_filtro); ?>">Próximo</a>
                        </li>
                    </ul>
                </nav>
            <?php } ?>
        <?php } ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
