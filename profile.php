<?php
// Updated profile.php - Now accepts user_id to view any profile, shows posts of the user
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

$viewed_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = $viewed_user_id";
$user = $conn->query($sql)->fetch_assoc();

if (!$user) {
    echo "Usuário não encontrado.";
    exit();
}

$is_own_profile = ($viewed_user_id == $_SESSION['user_id']);

// Handle profile photo update (only for own profile)
if ($is_own_profile && isset($_POST['update_photo'])) {
    if (isset($_FILES['new_photo']) && $_FILES['new_photo']['error'] == 0) {
        $target_dir = "uploads/";
        $profile_photo = $target_dir . basename($_FILES["new_photo"]["name"]);
        move_uploaded_file($_FILES["new_photo"]["tmp_name"], $profile_photo);
        $sql = "UPDATE users SET profile_photo = '$profile_photo' WHERE id = $viewed_user_id";
        $conn->query($sql);
        header("Location: profile.php?user_id=$viewed_user_id");
        exit();
    }
}

// Fetch user's posts
$post_sql = "SELECT p.id, p.content, p.image, p.created_at FROM posts p WHERE p.user_id = $viewed_user_id ORDER BY p.created_at DESC";
$user_posts = $conn->query($post_sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?php echo $user['username']; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <img src="<?php echo $user['profile_photo']; ?>" alt="Foto de perfil" class="profile-photo-large">
            <h1><?php echo $user['username']; ?></h1>
            <?php if ($is_own_profile): ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="new_photo" accept="image/*">
                    <button type="submit" name="update_photo">Atualizar Foto</button>
                </form>
            <?php endif; ?>
        </div>
        <a href="home.php">Voltar para Home</a>

        <h2>Publicações de <?php echo $user['username']; ?></h2>
        <?php while($post = $user_posts->fetch_assoc()): ?>
            <div class="post">
                <p><?php echo $post['created_at']; ?></p>
                <p><?php echo $post['content']; ?></p>
                <?php if ($post['image']): ?>
                    <img src="<?php echo $post['image']; ?>" alt="Imagem do post" class="post-content">
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>