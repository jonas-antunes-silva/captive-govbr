<?php
require_once 'db_connect.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obter IPs e MACs do ipset authenticated_clients
$clients = [];
$error = '';
try {
    // Executar comando ipset list authenticated_clients
    $output = shell_exec('sudo ipset list authenticated_clients');
    if ($output) {
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            // Procurar linhas com IPs e MACs (formato: IP,MAC ou apenas IP/MAC dependendo do tipo de ipset)
            if (preg_match('/(\d+\.\d+\.\d+\.\d+)(?:,([0-9A-Fa-f:]+))?/', $line, $matches)) {
                $ip = $matches[1];
                $mac = isset($matches[2]) ? $matches[2] : 'N/A';
                $clients[] = ['ip' => $ip, 'mac' => $mac];
            }
        }
    } else {
        $error = 'Nenhum cliente autenticado encontrado ou erro ao acessar o ipset.';
    }
} catch (Exception $e) {
    $error = 'Erro ao listar clientes autenticados: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes Autenticados</title>
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
                        <a class="nav-link" href="index.php">Relatório de Conexões</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="authenticated_clients.php">Clientes Autenticados</a>
                    </li>
                </ul>
                <span class="navbar-text me-3">Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn btn-danger">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1 class="mb-4">Clientes Autenticados</h1>

        <!-- Tabela de Clientes Autenticados -->
        <?php if ($error) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } else { ?>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>IP</th>
                        <th>MAC Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($clients) > 0) {
                        foreach ($clients as $client) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($client['ip']) . "</td>";
                            echo "<td>" . htmlspecialchars($client['mac']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2' class='text-center'>Nenhum cliente autenticado encontrado.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php } ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
