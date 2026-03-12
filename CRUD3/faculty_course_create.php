<?php
session_start();

if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
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

// Get faculty's school
$faculty_id = $_SESSION['session_identification_code'];
$school_query = $con->prepare("
    SELECT f.school_code 
    FROM faculty f 
    WHERE f.faculty_identification_code = ?
");
$school_query->bind_param('s', $faculty_id);
$school_query->execute();
$school_result = $school_query->get_result();

if ($school_result->num_rows === 0) {
    die("Faculty not assigned to any school");
}

$school_code = $school_result->fetch_assoc()['school_code'];

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


// Validate diploma belongs to faculty's school
$diploma_check = $con->prepare("
    SELECT 1 
    FROM diploma 
    WHERE diploma_code = ? 
    AND school_code = ?
");
$diploma_check->bind_param('ss', $diploma_code, $school_code);
$diploma_check->execute();

if ($diploma_check->get_result()->num_rows === 0) {
    die("Invalid diploma code for your school");
}

// Course code validation
if (!preg_match("/^[A-Z]{1}\d{2}$/", $course_code)) {
    die("Invalid course code format");
}

// Check duplicates
$course_check = $con->prepare("SELECT course_code FROM course WHERE course_code = ?");
$course_check->bind_param('s', $course_code);
$course_check->execute();

if ($course_check->get_result()->num_rows > 0) {
    die("Course code already exists");
}

// Insert course
$insert = $con->prepare("
    INSERT INTO course 
    (course_code, course_name, diploma_code, course_start_date, course_end_date, status)
    VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))
");

$insert->bind_param('ssssss', $course_code, $course_name, $diploma_code, $start_date, $end_date, $status);

if ($insert->execute()) {
    // Regenerate CSRF token after form submission
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: faculty_course_create_form.php?success=1");
} else {
    echo "Error creating course: " . $con->error;
}

$con->close();
?>