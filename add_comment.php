<?php
// Start session and include database connection
session_start();
include 'db.php';

// Define constants
define('MAX_COMMENT_LENGTH', 1000);
define('COMMENTS_TABLE', 'comments');

// Validate user session
if (!isset($_SESSION['user_id']) || !filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Validate request method and inputs
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['post_id']) || empty($_POST['content'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Sanitize and validate inputs
$post_id = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);
$content = trim(htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8'));
if ($post_id === false || $content === '' || strlen($content) > MAX_COMMENT_LENGTH) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid post ID or content']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Check database connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Validate post existence
$sql = "SELECT id FROM posts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
    exit;
}
$stmt->close();

// Insert comment
$sql = "INSERT INTO " . COMMENTS_TABLE . " (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $post_id, $user_id, $content);
if ($stmt->execute()) {
    $comment_id = $conn->insert_id;

    // Fetch user details
    $user_sql = "SELECT username, profile_photo FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    if ($user_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    $user = $user_result->fetch_assoc();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'comment_id' => $comment_id,
        'user_id' => $user_id,
        'username' => $user['username'],
        'profile_photo' => $user['profile_photo'],
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
$stmt->close();
$conn->close();
?>