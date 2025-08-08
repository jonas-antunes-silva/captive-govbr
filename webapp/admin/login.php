<?php
require_once 'db_connect.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        try {
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = :username");
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuário ou senha inválidos.';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao autenticar: ' . $e->getMessage();
        }
    } else {
        $error = 'Preencha todos os campos.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Relatório de Conexões</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Menu Bootstrap -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Relatório de Conexões</a>
        </div>
    </nav>

    <div class="container mt-5 login-container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <h1 class="text-center mb-4">Login</h1>
                <?php if ($error) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php } ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuário</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
