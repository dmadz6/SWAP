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
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }

    // Get the ID from the URL parameters
    $id = $_GET["id"] ?? '';

    if ($id) {
        // Fetch the course_code from semester_gpa_to_course_code table
        $query = $connect->prepare("SELECT course_code FROM semester_gpa_to_course_code WHERE grade_id = ?");
        $query->bind_param('i', $id);
        $query->execute();
        $query->bind_result($course_code);
        $query->fetch();
        $query->close();

        if (!$course_code) {
            header("Location: admin_score.php?error=" . urlencode("No course found for the given student score."));
            exit();
        }

        // Check if the course status is "Ended"
        $course_query = $connect->prepare("SELECT status FROM course WHERE course_code = ?");
        $course_query->bind_param('s', $course_code);
        $course_query->execute();
        $course_query->bind_result($course_status);
        $course_query->fetch();
        $course_query->close();

        if ($course_status !== 'Ended') {
            // Prevent deletion if the course is still in progress
            header("Location: admin_score.php?error=" . urlencode("Cannot delete student score. Course is still in progress."));
            exit();
        }

        // If course has ended, proceed with deletion
        $delete_query = $connect->prepare("DELETE FROM semester_gpa_to_course_code WHERE grade_id=?");
        $delete_query->bind_param('i', $id);
        if ($delete_query->execute()) {
            // Regenerate CSRF token after form submission
            unset($_SESSION['csrf_token']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: admin_score.php?success=3");
            exit();
        } else {
            header("Location: admin_score.php?error=" . urlencode("Unable to delete record."));
            exit();
        }
    } else {
        header("Location: admin_score.php?error=" . urlencode("No ID provided for deletion."));
        exit();
    }
} else {
    header("Location: admin_score.php?error=" . urlencode("Error executing DELETE query."));
    exit();
}
?>