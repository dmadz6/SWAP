<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = htmlspecialchars($_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: reset_password.php?token=" . urlencode($token) . "&error=" . urlencode("Passwords do not match."));
        exit();
    }

    $new_password = password_hash($password, PASSWORD_DEFAULT);

    // Verify token
    $stmt = $con->prepare("SELECT * FROM password_reset WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];

        // Update password
        $stmt = $con->prepare("UPDATE user SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $new_password, $email);
        $stmt->execute();

        // Delete token
        $stmt = $con->prepare("DELETE FROM password_reset WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Update the login_tracker in the database
        $update_stmt = $con->prepare("UPDATE user SET login_tracker = 1 WHERE email = ?");
        $update_stmt->bind_param("s", $email);
        $update_stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2 class="text-center" style="margin-bottom: 20px;">Password Reset Request</h2> 
            <?php
            if (isset($_GET['token'])) {
                $token = htmlspecialchars($_GET['token']);
                echo '<form method="POST" action="reset_password.php">
                        <input type="hidden" name="token" value="' . $token . '">
                        <div class="form-group">
                            <input type="password" id="password" name="password" style="border-radius: 12px" placeholder="New password" required>
                        </div>
                        <div class="form-group">
                            <input type="password" id="confirm_password" name="confirm_password" style="border-radius: 12px" placeholder="Confirm password" required>';
                
                // Display the error message if passwords do not match
                if (isset($_GET['error'])) {
                    echo '<div id="message" style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</div>';
                }

                echo '</div>
                      <button type="submit" class="button">Reset Password</button>
                      </form>';
                } else if (isset($result) && $result->num_rows > 0) {
                    echo '<p style="color: green; font-weight: bold;">
                            Password has been successfully updated. Redirecting to login...
                          </p>';
                    echo '<script>
                            setTimeout(function() {
                                window.location.href = "hamizanlogin.php";
                            }, 5000); // Redirects after 5 seconds
                          </script>';
                } else {
                    echo '<p style="color: red; font-weight: bold;">Invalid token.</p>';
                }
            ?>
        </div>
    </div>
    <script>
        setTimeout(function() {
        const messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.style.display = 'none';
        }
        }, 10000);
    
    </script>
</body>
</html>
