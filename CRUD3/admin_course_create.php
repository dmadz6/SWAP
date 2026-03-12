<?php
session_start();

if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Verify CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF token");
}

$con = mysqli_connect("localhost","root","","xyz polytechnic");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Validate inputs
$course_code = $_POST['course_code'] ?? '';
$course_name = $_POST['course_name'] ?? '';
$diploma_code = $_POST['diploma_code'] ?? '';
$start_date = $_POST['course_start_date'] ?? '';
$end_date = $_POST['course_end_date'] ?? '';
$status = $_POST['status'] ?? '';

if (empty($start_date)) {
    $start_date = NULL;
} else {
    $start_date = $start_date;
}

if (empty($end_date)) {
    $end_date = NULL;
} else {
    $end_date = $end_date;
}


// Change this section
if (empty($course_code) || empty($course_name) || empty($diploma_code)) {
    header("Location: admin_course_create_form.php?error=" . urlencode("Course code, name and diploma are required."));
    exit();
}

// Course code validation
if (!preg_match("/^[A-Z]{1}\d{2}$/", $course_code)) {
    header("Location: admin_course_create_form.php?error=" . urlencode("Invalid course code format"));
    exit();
}


// And change this section
if (!empty($start_date) && !empty($end_date) && strtotime($end_date) <= strtotime($start_date)) {
    header("Location: admin_course_create_form.php?error=" . urlencode("End date must be after start date"));
    exit();
}

// Check if diploma exists
$diploma_check = $con->prepare("SELECT 1 FROM diploma WHERE diploma_code = ?");
$diploma_check->bind_param('s', $diploma_code);
$diploma_check->execute();

if ($diploma_check->get_result()->num_rows === 0) {
    header("Location: admin_course_create_form.php?error=" . urlencode("Invalid diploma code"));
    exit();
}

// Check duplicates
$course_check = $con->prepare("SELECT course_code FROM course WHERE course_code = ?");
$course_check->bind_param('s', $course_code);
$course_check->execute();

if ($course_check->get_result()->num_rows > 0) {
    header("Location: admin_course_create_form.php?error=" . urlencode("Course code already exists"));
    exit();
}

// Insert course
$insert = $con->prepare("
    INSERT INTO course 
    (course_code, course_name, diploma_code, course_start_date, course_end_date, status)
    VALUES (?, ?, ?, ?, ?, ?)
");
$insert->bind_param('ssssss', $course_code, $course_name, $diploma_code, $start_date, $end_date, $status);

if ($insert->execute()) {
    // Regenerate CSRF token after form submission
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: admin_course_create_form.php?success=1");
} else {
    header("Location: admin_course_create_form.php?error=" . urlencode("Error creating course: " . $con->error));
}

$con->close();
?>