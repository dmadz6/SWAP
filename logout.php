<?php
session_start();

// Store the session user's name for display
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "User";

// Unset and destroy the session for logout
session_unset();
session_destroy();

// Redirect after 3 seconds
header('Refresh: 2; url=login.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to the external CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1 style="color: white;">Goodbye, <?php echo htmlspecialchars($full_name); ?>!</h1>
            <p style="margin-top: 20px;">You have successfully logged out. Redirecting you to the login page...</p>
            <p>If you are not redirected, <a href="login.php">click here</a>.</p>
        </div>
    </div>
</body>
</html>
