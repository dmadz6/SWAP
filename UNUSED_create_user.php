<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Replace with actual password
$dbname = "xyz polytechnic"; // Replace with actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identification_code = trim($_POST['identification_code']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role_id = intval($_POST['role_id']);
    $phone_number = trim($_POST['phone_number']);
    $full_name = trim($_POST['full_name']);
    $login_tracker = 0; // Default value for login_tracker

    // Input validation
    if (empty($identification_code) || empty($email) || empty($password) || empty($role_id) || empty($phone_number) || empty($full_name)) {
        $message = "All fields are required.";
    } else {
        // Hash password securely
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Prepare SQL statement to insert user
        $stmt = $conn->prepare(
            "INSERT INTO user (identification_code, email, password, role_id, phone_number, full_name, login_tracker) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssissi", $identification_code, $email, $hashed_password, $role_id, $phone_number, $full_name, $login_tracker);

        if ($stmt->execute()) {
            $message = "User created successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
</head>
<body>
    <h2>Create User</h2>
    <?php if (!empty($message)) { echo "<p style='color:blue;'>$message</p>"; } ?>
    <form method="POST" action="">
        <label for="identification_code">Identification Code:</label><br>
        <input type="text" id="identification_code" name="identification_code" required><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br>

        <label for="role_id">Role ID (1: Admin, 2: Faculty, 3: Student):</label><br>
        <select id="role_id" name="role_id" required>
            <option value="1">Admin</option>
            <option value="2">Faculty</option>
            <option value="3">Student</option>
        </select><br>

        <label for="phone_number">Phone Number:</label><br>
        <input type="text" id="phone_number" name="phone_number" required><br>

        <label for="full_name">Full Name:</label><br>
        <input type="text" id="full_name" name="full_name" required><br><br>

        <button type="submit">Create User</button>
    </form>
</body>
</html>
