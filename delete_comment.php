<?php
// Updated delete_comment.php - Allow post owner to delete any comment on their post
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $comment_id = $_POST['comment_id'];
    $user_id = $_SESSION['user_id'];

    // Get post_id from comment
    $post_sql = "SELECT p.user_id as post_owner_id FROM comments c JOIN posts p ON c.post_id = p.id WHERE c.id = $comment_id";
    $post_owner = $conn->query($post_sql)->fetch_assoc()['post_owner_id'];

    // Check if user is comment owner or post owner
    $sql_check = "SELECT user_id FROM comments WHERE id = $comment_id";
    $comment_owner = $conn->query($sql_check)->fetch_assoc()['user_id'];

    if ($comment_owner == $user_id || $post_owner == $user_id) {
        $sql = "DELETE FROM comments WHERE id = $comment_id";
        $conn->query($sql);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>