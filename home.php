<?php
// Updated home.php - Added dropdown for edit/delete, dynamic comment handling via AJAX, post owner can delete any comment
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

$limit = 5;
$page = 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT p.id, p.content, p.image, p.created_at, u.id as user_id, u.username, u.profile_photo 
        FROM posts p JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";
$posts = $conn->query($sql);

$total_sql = "SELECT COUNT(*) as total FROM posts";
$total_result = $conn->query($total_sql);
$total_posts = $total_result->fetch_assoc()['total'];
$has_more = ($total_posts > $limit);

// Create post (with redirect)
if (isset($_POST['create_post'])) {
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    $image = NULL;
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image = $target_dir . basename($_FILES["post_image"]["name"]);
        move_uploaded_file($_FILES["post_image"]["tmp_name"], $image);
    }

    $sql = "INSERT INTO posts (user_id, content, image) VALUES ($user_id, '$content', " . ($image ? "'$image'" : "NULL") . ")";
    if ($conn->query($sql) === TRUE) {
        header("Location: home.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="styles.css">
    <script src="scripts.js"></script>
</head>
<body>
    <div class="container">
        <h1>Bem-vindo, <?php echo $_SESSION['username']; ?></h1>
        <a href="profile.php">Meu Perfil</a> | <a href="logout.php">Sair</a>

        <form method="POST" enctype="multipart/form-data">
            <textarea name="content" placeholder="No que você está pensando?" required></textarea>
            <input type="file" name="post_image" accept="image/*">
            <button type="submit" name="create_post">Publicar</button>
        </form>

        <h2>Feed</h2>
        <div id="feed">
            <?php while($post = $posts->fetch_assoc()): ?>
                <div class="post" data-post-id="<?php echo $post['id']; ?>">
                    <div class="post-header">
                        <img src="<?php echo $post['profile_photo']; ?>" alt="Foto de perfil" class="profile-photo">
                        <div>
                            <a href="profile.php?user_id=<?php echo $post['user_id']; ?>"><strong><?php echo $post['username']; ?></strong></a>
                            <span> - <?php echo $post['created_at']; ?></span>
                        </div>
                        <?php if ($post['username'] == $_SESSION['username']): ?>
                            <div class="post-options">
                                <button class="options-btn" onclick="toggleMenu(<?php echo $post['id']; ?>)">...</button>
                                <div id="menu-<?php echo $post['id']; ?>" class="options-menu">
                                    <button onclick="editPost(<?php echo $post['id']; ?>)">Editar</button>
                                    <button onclick="deletePost(<?php echo $post['id']; ?>)">Deletar</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p id="post-content-<?php echo $post['id']; ?>"><?php echo $post['content']; ?></p>
                    <?php if ($post['image']): ?>
                        <img src="<?php echo $post['image']; ?>" alt="Imagem do post" class="post-content">
                    <?php endif; ?>

                    <!-- Comments -->
                    <div class="comments" id="comments-<?php echo $post['id']; ?>">
                        <?php
                        $post_id = $post['id'];
                        $comment_sql = "SELECT c.id, c.content, c.created_at, u.id as user_id, u.username, u.profile_photo 
                                        FROM comments c JOIN users u ON c.user_id = u.id 
                                        WHERE c.post_id = $post_id ORDER BY c.created_at DESC";
                        $comments = $conn->query($comment_sql);
                        $is_post_owner = ($post['user_id'] == $_SESSION['user_id']);
                        while($comment = $comments->fetch_assoc()): 
                            $can_delete_comment = ($comment['username'] == $_SESSION['username'] || $is_post_owner);
                        ?>
                            <div class="comment" data-comment-id="<?php echo $comment['id']; ?>">
                                <div class="comment-header">
                                    <img src="<?php echo $comment['profile_photo']; ?>" alt="Foto de perfil" class="profile-photo">
                                    <div>
                                        <a href="profile.php?user_id=<?php echo $comment['user_id']; ?>"><strong><?php echo $comment['username']; ?></strong></a>
                                        <span> - <?php echo $comment['created_at']; ?></span>
                                    </div>
                                    <?php if ($can_delete_comment): ?>
                                        <div class="post-options">
                                            <button class="options-btn" onclick="toggleCommentMenu(<?php echo $comment['id']; ?>)">...</button>
                                            <div id="comment-menu-<?php echo $comment['id']; ?>" class="options-menu">
                                                <?php if ($comment['username'] == $_SESSION['username']): ?>
                                                    <button onclick="editComment(<?php echo $comment['id']; ?>)">Editar</button>
                                                <?php endif; ?>
                                                <button onclick="deleteComment(<?php echo $comment['id']; ?>, <?php echo $post['id']; ?>)">Deletar</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p id="comment-content-<?php echo $comment['id']; ?>"><?php echo $comment['content']; ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <form class="comment-form" onsubmit="addComment(event, <?php echo $post['id']; ?>)">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <textarea name="comment_content" placeholder="Escreva um comentário..." required></textarea>
                        <button type="submit">Comentar</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
        <?php if ($has_more): ?>
            <button id="load-more" onclick="loadMorePosts()">Mostrar mais</button>
        <?php endif; ?>
    </div>
    <script>
        let currentPage = 1;
        const limit = <?php echo $limit; ?>;
    </script>
</body>
</html>