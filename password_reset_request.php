<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['email']);

    // Check if the email exists
    $stmt = $con->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(50)); // Generate token

        // Save token to password_reset table
        $stmt = $con->prepare("INSERT INTO password_reset (email, token) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();

        // Send reset email
        $mail = new PHPMailer(true);
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'xyzpolytechnicadm@gmail.com';
            $mail->Password = 'pges vjob hgjl lfzb'; // App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Email content
            $mail->setFrom('xyzpolytechnicadm@gmail.com', 'XYZ Polytechnic');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4;'>
                    <table align='center' width='600' style='margin: 20px auto; background-color: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
                        <tr>
                            <td style='background-color: #113f84; text-align: center; padding: 20px;'></td>
                        </tr>
                        <tr>
                            <td style='padding: 30px; text-align: center; color: #333;'>
                                <h1 style='font-size: 24px; color: #333; margin: 0 0 20px;'>Password Reset Info</h1>
                                <p style='font-size: 16px; line-height: 1.5; margin: 0 0 20px;'>
                                    Dear User,<br>We received a request to reset your password.
                                </p>
                                <a href='http://localhost/SWAP/reset_password.php?token=$token'
                                   style='display: inline-block; padding: 12px 20px; background-color: #113f84; color: #fff; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 20px 0;'>
                                    Reset your password
                                </a>
                                <p style='font-size: 14px; color: #666; margin: 20px 0 0;'>
                                    This link will expire in 24 hours. If you did not request a password reset, please ignore this message.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>";
            $mail->send();
            header("Location: password_reset_request.php?success=1");
            exit();
        } catch (Exception $e) {
            header("Location: password_reset_request.php?error=" . urlencode("Email could not be sent. Mailer Error: {$mail->ErrorInfo}"));
        }
    } else {
        header("Location: password_reset_request.php?error=" . urlencode("Email not found."));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
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
            <h2 class="text-center">Password Reset Request</h2>
            <form method="POST" action="password_reset_request.php">
                <div class="form-group">
                    <input type="text" id="email" name="email" placeholder="Enter your email" required>
                    <?php
                    // If ?success=1 is set in the URL, display a success message
                    if (isset($_GET['success']) && $_GET['success'] == 1) {
                        echo '<div id="message" class="success-message">A password reset link has been sent to your email address. Please check your inbox to proceed.</div>';
                    }
                
                    // Check if an error parameter was passed
                    if (isset($_GET['error'])) {
                        echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
                    }
                    ?>
                </div>
                <button type="submit" class="button">Send Email</button>
            </form>
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
