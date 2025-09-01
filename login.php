<?php
session_start();
include 'db.php';

// Inicializa variáveis de erro
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitiza entradas
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    // Valida entradas
    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        // Usa prepared statement para evitar SQL Injection
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                header("Location: home.php");
                exit();
            } else {
                $error = "Senha inválida.";
            }
        } else {
            $error = "Usuário não encontrado.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/scripts.js" defer></script>
</head>
<body>
    <div class="container auth-form" role="main">
        <h1>Login</h1>
        <?php if (!empty($error)): ?>
            <p class="error" role="alert" style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" aria-labelledby="login-form">
            <div class="form-group">
                <label for="username">Nome de usuário</label>
                <input type="text" id="username" name="username" placeholder="Nome de usuário" required aria-required="true">
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" placeholder="Senha" required aria-required="true">
            </div>
            <button type="submit">Entrar</button>
        </form>
        <p style="text-align: center;"><a href="register.php">Criar uma conta</a></p>
    </div>
</body>
</html>