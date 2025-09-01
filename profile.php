<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$viewed_user_id = isset($_GET['user_id']) && filter_var($_GET['user_id'], FILTER_VALIDATE_INT) ? (int)$_GET['user_id'] : $_SESSION['user_id'];
$is_own_profile = ($viewed_user_id == $_SESSION['user_id']);

// Fetch user
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $viewed_user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: error.php?message=Usuário não encontrado");
    exit();
}

// Handle photo upload
if ($is_own_profile && isset($_POST['update_photo'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token inválido.");
    }
    if (isset($_FILES['new_photo']) && $_FILES['new_photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024;
        $file_type = mime_content_type($_FILES['new_photo']['tmp_name']);
        $file_size = $_FILES['new_photo']['size'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            $target_dir = "uploads/";
            $file_ext = pathinfo($_FILES['new_photo']['name'], PATHINFO_EXTENSION);
            $unique_name = uniqid('photo_') . '.' . $file_ext;
            $profile_photo = $target_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['new_photo']['tmp_name'], $profile_photo)) {
                $sql = "UPDATE users SET profile_photo = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $profile_photo, $viewed_user_id);
                $stmt->execute();
                $stmt->close();
                header("Location: profile.php?user_id=$viewed_user_id");
                exit();
            }
        }
    }
    echo "Erro ao atualizar a foto.";
}

// Fetch posts
$post_sql = "SELECT p.id, p.content, p.image, p.created_at FROM posts p WHERE p.user_id = ? ORDER BY p.created_at DESC";
$stmt = $conn->prepare($post_sql);
$stmt->bind_param("i", $viewed_user_id);
$stmt->execute();
$user_posts = $stmt->get_result();
$stmt->close();
$conn->close();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/scripts.js"></script>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($user['profile_photo'] ?: 'uploads/default_profile.jpg'); ?>" alt="Foto de perfil" class="profile-photo-large" loading="lazy">
            <h1><?php echo htmlspecialchars($user['username']); ?></h1>
            <?php if ($is_own_profile): ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="file" name="new_photo" accept="image/jpeg,image/png,image/gif" required>
                    <button type="submit" name="update_photo">Atualizar Foto</button>
                </form>
            <?php endif; ?>
        </div>
        <a href="home.php">Voltar para Home</a>
        <h2>Publicações de <?php echo htmlspecialchars($user['username']); ?></h2>
        <?php while($post = $user_posts->fetch_assoc()): ?>
            <div class="post">
                <p><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></p>
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <?php if ($post['image']): ?>
                    <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Imagem do post" class="post-content" loading="lazy">
                <?php endif; ?>
                <?php if ($is_own_profile): ?>
                    <a href="edit_post.php?post_id=<?php echo $post['id']; ?>">Editar</a>
                    <a href="delete_post.php?post_id=<?php echo $post['id']; ?>" onclick="return confirm('Tem certeza?')">Excluir</a>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>