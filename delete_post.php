<?php
// New delete_post.php - AJAX for deleting post
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    $sql = "DELETE FROM posts WHERE id = $post_id AND user_id = $user_id";
    if ($conn->query($sql) === TRUE) {
        // Also delete comments
        $conn->query("DELETE FROM comments WHERE post_id = $post_id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>