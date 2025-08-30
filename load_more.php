<?php
// Updated load_more.php - Adjusted for dropdown, post owner delete comments, etc.
include 'db.php';
session_start();

if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
    $limit = 5;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT p.id, p.content, p.image, p.created_at, u.id as user_id, u.username, u.profile_photo 
            FROM posts p JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";
    $posts = $conn->query($sql);

    while($post = $posts->fetch_assoc()) {
        echo '<div class="post" data-post-id="' . $post['id'] . '">';
        echo '<div class="post-header">';
        echo '<img src="' . $post['profile_photo'] . '" alt="Foto de perfil" class="profile-photo">';
        echo '<div>';
        echo '<a href="profile.php?user_id=' . $post['user_id'] . '"><strong>' . $post['username'] . '</strong></a>';
        echo '<span> - ' . $post['created_at'] . '</span>';
        echo '</div>';
        if ($post['username'] == $_SESSION['username']) {
            echo '<div class="post-options">';
            echo '<button class="options-btn" onclick="toggleMenu(' . $post['id'] . ')">...</button>';
            echo '<div id="menu-' . $post['id'] . '" class="options-menu">';
            echo '<button onclick="editPost(' . $post['id'] . ')">Editar</button>';
            echo '<button onclick="deletePost(' . $post['id'] . ')">Deletar</button>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '<p id="post-content-' . $post['id'] . '">' . $post['content'] . '</p>';
        if ($post['image']) {
            echo '<img src="' . $post['image'] . '" alt="Imagem do post" class="post-content">';
        }

        // Comments
        echo '<div class="comments" id="comments-' . $post['id'] . '">';
        $post_id = $post['id'];
        $comment_sql = "SELECT c.id, c.content, c.created_at, u.id as user_id, u.username, u.profile_photo 
                        FROM comments c JOIN users u ON c.user_id = u.id 
                        WHERE c.post_id = $post_id ORDER BY c.created_at DESC";
        $comments = $conn->query($comment_sql);
        $is_post_owner = ($post['user_id'] == $_SESSION['user_id']);
        while($comment = $comments->fetch_assoc()) {
            $can_delete_comment = ($comment['username'] == $_SESSION['username'] || $is_post_owner);
            echo '<div class="comment" data-comment-id="' . $comment['id'] . '">';
            echo '<div class="comment-header">';
            echo '<img src="' . $comment['profile_photo'] . '" alt="Foto de perfil" class="profile-photo">';
            echo '<div>';
            echo '<a href="profile.php?user_id=' . $comment['user_id'] . '"><strong>' . $comment['username'] . '</strong></a>';
            echo '<span> - ' . $comment['created_at'] . '</span>';
            echo '</div>';
            if ($can_delete_comment) {
                echo '<div class="post-options">';
                echo '<button class="options-btn" onclick="toggleCommentMenu(' . $comment['id'] . ')">...</button>';
                echo '<div id="comment-menu-' . $comment['id'] . '" class="options-menu">';
                if ($comment['username'] == $_SESSION['username']) {
                    echo '<button onclick="editComment(' . $comment['id'] . ')">Editar</button>';
                }
                echo '<button onclick="deleteComment(' . $comment['id'] . ', ' . $post['id'] . ')">Deletar</button>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
            echo '<p id="comment-content-' . $comment['id'] . '">' . $comment['content'] . '</p>';
            echo '</div>';
        }
        echo '</div>';

        // Comment form
        echo '<form class="comment-form" onsubmit="addComment(event, ' . $post['id'] . ')">';
        echo '<input type="hidden" name="post_id" value="' . $post['id'] . '">';
        echo '<textarea name="comment_content" placeholder="Escreva um comentário..." required></textarea>';
        echo '<button type="submit">Comentar</button>';
        echo '</form>';

        echo '</div>';
    }

    $next_offset = $offset + $limit;
    $total_sql = "SELECT COUNT(*) as total FROM posts";
    $total_result = $conn->query($total_sql);
    $total_posts = $total_result->fetch_assoc()['total'];
    if ($next_offset >= $total_posts) {
        echo '<script>document.getElementById("load-more").style.display = "none";</script>';
    }
}
?>