<?php
// Start the session
session_start();
session_regenerate_id(true);

// Connect to the database 'xyz polytechnic_danial'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // Redirect to login page if the user is not logged in or not an admin
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }
    return true;
}

// Check if the delete request is made
if (isset($_GET['operation']) && $_GET['operation'] == 'delete') {
    $csrf_token = $_GET["csrf_token"] ?? '';
    if (!check_csrf_token($csrf_token)) {
        // If CSRF token is invalid, display the error message
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }

    // Get the identification_code from the URL parameters
    $identification_code = $_GET["identification_code"] ?? '';

    if ($identification_code) {
        // Delete the record from the database
        $query = $connect->prepare("DELETE FROM student_score WHERE identification_code=?");
        $query->bind_param('s', $identification_code); // Bind the parameter
        if ($query->execute()) {
                // Regenerate CSRF token after form submission
            unset($_SESSION['csrf_token']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            // If the record was deleted, redirect to the admin page
            header("Location: admin_gpa.php?success=3");
            exit();
        } else {
            // If deletion failed, set the error message
            header("Location: admin_gpa.php?error=" . urlencode("Unable to delete GPA record."));
            exit();
        }
    } else {
        // If no ID is provided in the URL, set an error message and show the error page
        header("Location: admin_gpa.php?error=" . urlencode("No ID provided for deletion."));
        exit();
    }
} else {
    // If the delete operation is not specified, set an error message and show the error page
    header("Location: admin_gpa.php?error=" . urlencode("Error executing DELETE query."));
    exit();
}
?> 
