<?php
// New add_comment.php - AJAX for adding comment
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    $sql = "INSERT INTO comments (post_id, user_id, content) VALUES ($post_id, $user_id, '$content')";
    if ($conn->query($sql) === TRUE) {
        $comment_id = $conn->insert_id;
        $user_sql = "SELECT username, profile_photo FROM users WHERE id = $user_id";
        $user = $conn->query($user_sql)->fetch_assoc();

        echo json_encode([
            'success' => true,
            'comment_id' => $comment_id,
            'user_id' => $user_id,
            'username' => $user['username'],
            'profile_photo' => $user['profile_photo']
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>