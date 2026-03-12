<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] != "POST") exit();

// CSRF Validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token.');
}

$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) die('Connection failed: ' . mysqli_connect_error());

// Get logged-in faculty's school
$faculty_id = $_SESSION['session_identification_code'];
$school_stmt = $con->prepare("SELECT school_code FROM faculty WHERE faculty_identification_code = ?");
$school_stmt->bind_param('s', $faculty_id);
$school_stmt->execute();
$school_result = $school_stmt->get_result();
$school_row = $school_result->fetch_assoc();
$school_code = $school_row['school_code'];

// Validate inputs
$original_coursecode = $_POST['original_coursecode'] ?? '';
$upd_coursecode = $_POST['upd_coursecode'] ?? '';
$upd_coursename = $_POST['upd_coursename'] ?? '';
$upd_diplomacode = $_POST['upd_diplomacode'] ?? '';
$upd_startdate = $_POST['upd_startdate'] ?? '';
$upd_enddate = $_POST['upd_enddate'] ?? '';
$upd_status = $_POST['upd_status'] ?? '';

if (empty($upd_startdate)) {
    $upd_startdate = NULL;
} else {
    $upd_startdate = $upd_startdate;
}

if (empty($end_date)) {
    $upd_enddate = NULL;
} else {
    $upd_enddate = $upd_enddate;
}


if (empty($upd_coursecode) || empty($upd_coursename) || empty($upd_diplomacode) ) {
    header("Location: faculty_course_update_form.php?error=" . urlencode("All fields required.") . "&course_code=$original_coursecode");
    exit();
}

// Validate course code format
if (!preg_match("/^[A-Z]{1}\d{2}$/", $upd_coursecode)) {
    header("Location: faculty_course_update_form.php?error=" . urlencode("Invalid course code format.") . "&course_code=$original_coursecode");
    exit();
}

// Validate dates
if (!empty($start_date) && !empty($end_date) && strtotime($end_date) <= strtotime($start_date)) {
    header("Location: faculty_course_update_form.php?error=" . urlencode("End date must be after start date.") . "&course_code=$original_coursecode");
    exit();
}

// Validate diploma belongs to school
$diploma_check = $con->prepare("SELECT school_code FROM diploma WHERE diploma_code = ? AND school_code = ?");
$diploma_check->bind_param('ss', $upd_diplomacode, $school_code);
$diploma_check->execute();
$diploma_result = $diploma_check->get_result();

if ($diploma_result->num_rows === 0) {
    header("Location: faculty_course_update_form.php?error=" . urlencode("Invalid diploma selection") . "&course_code=$original_coursecode");
    exit();
}

// Check for duplicate course code
if ($upd_coursecode !== $original_coursecode) {
    $course_check = $con->prepare("SELECT course_code FROM course WHERE course_code = ?");
    $course_check->bind_param('s', $upd_coursecode);
    $course_check->execute();
    if ($course_check->get_result()->num_rows > 0) {
        header("Location: faculty_course_update_form.php?error=" . urlencode("Course code already exists") . "&course_code=$original_coursecode");
        exit();
    }
}

// Update course
$update_stmt = $con->prepare("UPDATE course SET 
    course_code = ?, 
    course_name = ?, 
    diploma_code = ?, 
    course_start_date = NULLIF(?, ''),
    course_end_date = NULLIF(?, ''),
    status = NULLIF(?, '')
    WHERE course_code = ?");

$update_stmt->bind_param('sssssss', 
    $upd_coursecode, 
    $upd_coursename, 
    $upd_diplomacode, 
    $upd_startdate,
    $upd_enddate,
    $upd_status,
    $original_coursecode
);

if ($update_stmt->execute()) {
    // Regenerate CSRF token after form submission
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: faculty_course_create_form.php?success=2");
} else {
    header("Location: faculty_course_update_form.php?error=" . urlencode("Update failed: " . $con->error) . "&course_code=$original_coursecode");
}

$update_stmt->close();
$con->close();
?>