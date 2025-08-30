<?php
// New update_comment.php - Handle comment edit via AJAX
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $comment_id = $_POST['comment_id'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    $sql = "UPDATE comments SET content = '$content' WHERE id = $comment_id AND user_id = $user_id";
    $conn->query($sql);
}
?>