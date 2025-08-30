<?php
// Updated home.php - Added category filter sidebar, Markdown support, fixed dropdown
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';
require 'lib/Parsedown.php'; // Include Parsedown

$Parsedown = new Parsedown();
$Parsedown->setSafeMode(true); // Enable safe mode to prevent XSS

$limit = 5;
$page = 1;
$offset = ($page - 1) * $limit;

// Verificar se o usuário é admin
$is_admin = false;
$user_sql = "SELECT is_admin FROM users WHERE id = " . $_SESSION['user_id'];
$user_result = $conn->query($user_sql);
if ($user_result->num_rows > 0) {
    $is_admin = $user_result->fetch_assoc()['is_admin'] == 1;
}

// Create post (only for admin)
if (isset($_POST['create_post']) && $is_admin) {
    $content = $_POST['content'];
    $category = $_POST['category'];
    $user_id = $_SESSION['user_id'];

    $image = NULL;
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $target_dir = "Uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image = $target_dir . basename($_FILES["post_image"]["name"]);
        move_uploaded_file($_FILES["post_image"]["tmp_name"], $image);
    }

    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image, category) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $content, $image, $category);
    if ($stmt->execute()) {
        header("Location: home.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Fetch posts with user photos and category
$sql = "SELECT p.id, p.content, p.image, p.category, p.created_at, u.id as user_id, u.username, u.profile_photo 
        FROM posts p JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";
$posts = $conn->query($sql);

$total_sql = "SELECT COUNT(*) as total FROM posts";
$total_result = $conn->query($total_sql);
$total_posts = $total_result->fetch_assoc()['total'];
$has_more = ($total_posts > $limit);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/scripts.js"></script>
</head>
<body>
    <div class="container">
        <h1>Bem-vindo, <?php echo $_SESSION['username']; ?></h1>
        <a href="profile.php">Meu Perfil</a> | <a href="logout.php">Sair</a>

        <div class="content-wrapper">
            <div class="sidebar">
                <h3>Categorias</h3>
                <button class="category-btn active" onclick="filterPosts('all')">Todos</button>
                <button class="category-btn" onclick="filterPosts('Receitas Doces')">Receitas Doces</button>
                <button class="category-btn" onclick="filterPosts('Receitas Salgadas')">Receitas Salgadas</button>
                <button class="category-btn" onclick="filterPosts('Dicas de Cozinha')">Dicas de Cozinha</button>
                <button class="category-btn" onclick="filterPosts('Receitas Veganas')">Receitas Veganas</button>
            </div>
            <div class="main-content">
                <?php if ($is_admin): ?>
                <form method="POST" enctype="multipart/form-data">
                    <textarea name="content" placeholder="No que você está pensando? (Suporta Markdown)" required></textarea>
                    <select name="category" required>
                        <option value="" disabled selected>Selecione uma categoria</option>
                        <option value="Receitas Doces">Receitas Doces</option>
                        <option value="Receitas Salgadas">Receitas Salgadas</option>
                        <option value="Dicas de Cozinha">Dicas de Cozinha</option>
                        <option value="Receitas Veganas">Receitas Veganas</option>
                    </select>
                    <input type="file" name="post_image" accept="image/*">
                    <button type="submit" name="create_post">Publicar</button>
                </form>
                <?php else: ?>
                <p>Apenas administradores podem criar posts.</p>
                <?php endif; ?>

                <h2>Feed</h2>
                <div id="feed">
                    <?php 
                    if ($posts->num_rows > 0): 
                        while($post = $posts->fetch_assoc()): 
                            $post_id = $post['id'];
                            $comment_sql = "SELECT COUNT(*) as comment_count FROM comments WHERE post_id = $post_id";
                            $comment_result = $conn->query($comment_sql);
                            $comment_count = $comment_result->fetch_assoc()['comment_count'];
                    ?>
                        <div class="post" data-post-id="<?php echo $post['id']; ?>" data-category="<?php echo $post['category']; ?>">
                            <div class="post-header">
                                <img src="<?php echo $post['profile_photo']; ?>" alt="Foto de perfil" class="profile-photo">
                                <div>
                                    <a href="profile.php?user_id=<?php echo $post['user_id']; ?>"><strong><?php echo $post['username']; ?></strong></a>
                                    <span> - <?php echo $post['created_at']; ?></span>
                                    <p><small>Categoria: <?php echo $post['category']; ?></small></p>
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
                            <div class="post-content" id="post-content-<?php echo $post['id']; ?>" data-raw="<?php echo htmlspecialchars($post['content']); ?>"><?php echo $Parsedown->text($post['content']); ?></div>
                            <?php if ($post['image']): ?>
                                <img src="<?php echo $post['image']; ?>" alt="Imagem do post" class="post-image">
                            <?php endif; ?>

                            <!-- Comments Section -->
                            <div class="comments-section">
                                <div class="comments-header">
                                    <button class="toggle-comments-btn" onclick="toggleComments(<?php echo $post['id']; ?>)" data-post-id="<?php echo $post['id']; ?>">
                                        <span class="comment-count"><?php echo $comment_count; ?></span> comentário(s)
                                        <span class="toggle-arrow">▼</span>
                                    </button>
                                </div>
                                
                                <div class="comments-container" id="comments-container-<?php echo $post['id']; ?>" style="display: none;">
                                    <div class="comments" id="comments-<?php echo $post['id']; ?>">
                                        <?php
                                        $comments_sql = "SELECT c.id, c.content, c.created_at, u.id as user_id, u.username, u.profile_photo 
                                                        FROM comments c JOIN users u ON c.user_id = u.id 
                                                        WHERE c.post_id = $post_id ORDER BY c.created_at DESC LIMIT 3";
                                        $comments = $conn->query($comments_sql);
                                        $is_post_owner = ($post['user_id'] == $_SESSION['user_id']);
                                        
                                        if ($comments->num_rows > 0): 
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
                                                <div class="comment-content" id="comment-content-<?php echo $comment['id']; ?>" data-raw="<?php echo htmlspecialchars($comment['content']); ?>"><?php echo $Parsedown->text($comment['content']); ?></div>
                                            </div>
                                        <?php 
                                            endwhile; 
                                        else: 
                                        ?>
                                            <div class="no-comments">
                                                <p>Nenhum comentário ainda.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <form class="comment-form" onsubmit="addComment(event, <?php echo $post['id']; ?>)">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <textarea name="comment_content" placeholder="Escreva um comentário... (Suporta Markdown)" required></textarea>
                                        <button type="submit">Comentar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <div class="no-posts-message">
                            <p>Não há posts ainda. <?php echo $is_admin ? 'Seja o primeiro a publicar!' : 'Aguarde os administradores publicarem conteúdo.'; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($has_more): ?>
                    <button id="load-more" onclick="loadMorePosts()">Mostrar mais</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        let currentPage = 1;
        const limit = <?php echo $limit; ?>;
    </script>
</body>
</html>