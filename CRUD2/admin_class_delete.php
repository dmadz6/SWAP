<?php
session_start(); // Start the session

// Connect to database
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Verify CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token. Possible CSRF attack detected.');
}

// Prepare the statement 
$stmt = $con->prepare("DELETE FROM class WHERE class_code=?");

$del_classcode = htmlspecialchars($_GET["class_code"]);

$stmt->bind_param('s', $del_classcode); // Bind the parameters
if ($stmt->execute()) {
    // Regenerate CSRF token after form submission
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    echo "Delete Query executed.";
    header("Location: admin_class_create_form.php?success=3");
    exit();
} else {
    echo "Error executing DELETE query.";
}
?>
