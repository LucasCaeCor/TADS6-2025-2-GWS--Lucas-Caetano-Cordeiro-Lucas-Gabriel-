<?php
// Updated update_post.php - Handle post edit via AJAX (no changes needed, but included for completeness)
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    $sql = "UPDATE posts SET content = '$content' WHERE id = $post_id AND user_id = $user_id";
    $conn->query($sql);
}
?>