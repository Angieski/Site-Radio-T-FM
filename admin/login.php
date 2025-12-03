<?php
session_start();
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['email'];
    $password = $_POST['senha'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Comparação direta (senhas em texto plano)
        if ($user && $password === $user['senha']) {
            $_SESSION['loggedin'] = true;
            header('Location: index.php');
            exit;
        } else {
            $error = "Credenciais inválidas!";
        }
    } catch (PDOException $e) {
        $error = "Erro: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5 px-2" style="max-width: 400px;">
        <h2 class="mb-4 text-center text-md-start">Painel Admin</h2>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <input type="text" name="email" class="form-control py-2" placeholder="Usuário" required>
            </div>
            <div class="mb-3">
                <input type="password" name="senha" class="form-control py-2" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Entrar</button>
        </form>
    </div>
</body>
</html>