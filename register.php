<?php
include 'db.php';
include 'config.php';
session_start();

// Gerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Token CSRF inválido.");
    }

    // Sanitizar e validar entradas
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Email inválido.");
    }
    if (strlen($_POST['password']) < 8) {
        die("A senha deve ter pelo menos 8 caracteres.");
    }
    if ($_POST['password'] !== $_POST['confirm_password']) {
        die("As senhas não coincidem.");
    }
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Verificar duplicatas
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        die("Email ou username já está em uso.");
    }
    $stmt->close();

    // Upload de foto
    $profile_photo = 'default.jpg';
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $file_type = mime_content_type($_FILES['profile_photo']['tmp_name']);
        if (!in_array($file_type, ALLOWED_FILE_TYPES) || $_FILES['profile_photo']['size'] > MAX_FILE_SIZE) {
            die("Apenas imagens JPEG, PNG ou GIF com até 2MB são permitidas.");
        }
        $profile_photo = UPLOAD_DIR . uniqid() . '_' . basename($_FILES["profile_photo"]["name"]);
        if (!file_exists(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $profile_photo);
    }

    // Inserir no banco
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, profile_photo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $password, $profile_photo);
    if ($stmt->execute()) {
        echo '<div style="color: green;">Cadastro realizado com sucesso! Redirecionando...</div>';
        header("Refresh: 2; url=login.php");
        exit();
    } else {
        error_log("Erro no cadastro: " . $stmt->error);
        echo '<div style="color: red;">Erro ao cadastrar. Tente novamente.</div>';
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/scripts.js" defer></script>
</head>
<body>
    <div class="container auth-form">
        <h1>Cadastro</h1>
        <div id="message"></div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirmar Senha" required>
            <input type="file" name="profile_photo" accept="image/*">
            <button type="submit">Cadastrar</button>
        </form>
        <a href="login.php">Já tem conta? Login</a>
    </div>
</body>
</html>