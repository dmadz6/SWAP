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
$original_classcode = $_POST['original_classcode'] ?? '';
$upd_classcode = $_POST['upd_classcode'] ?? '';
$upd_coursecode = $_POST['upd_coursecode'] ?? '';
$upd_classtype = $_POST['upd_classtype'] ?? '';
$upd_facultycode = $_POST['upd_facultycode'] ?? '';

if (empty($upd_classcode) || empty($upd_coursecode) || empty($upd_classtype) || empty($upd_facultycode)) {
    header("Location: faculty_class_update_form.php?error=" . urlencode("All fields required.") . "&class_code=$original_classcode");
    exit();
}

// Validate class code format
if (!preg_match("/^[A-Z]{2}[0-9]{2}$/", $upd_classcode)) {
    header("Location: faculty_class_update_form.php?error=" . urlencode("Invalid class code format.") . "&class_code=$original_classcode");
    exit();
}

// Validate course belongs to school
$course_check = $con->prepare("SELECT d.school_code 
                              FROM course c 
                              JOIN diploma d ON c.diploma_code = d.diploma_code 
                              WHERE c.course_code = ?");
$course_check->bind_param('s', $upd_coursecode);
$course_check->execute();
$course_result = $course_check->get_result();

if ($course_result->num_rows === 0 || $course_result->fetch_assoc()['school_code'] !== $school_code) {
    header("Location: faculty_class_update_form.php?error=" . urlencode("Invalid course selection") . "&class_code=$original_classcode");
    exit();
}

// Validate faculty belongs to school
$faculty_check = $con->prepare("SELECT school_code FROM faculty WHERE faculty_identification_code = ?");
$faculty_check->bind_param('s', $upd_facultycode);
$faculty_check->execute();
$faculty_result = $faculty_check->get_result();

if ($faculty_result->num_rows === 0 || $faculty_result->fetch_assoc()['school_code'] !== $school_code) {
    header("Location: faculty_class_update_form.php?error=" . urlencode("Invalid faculty selection") . "&class_code=$original_classcode");
    exit();
}

// Check for duplicate class code
if ($upd_classcode !== $original_classcode) {
    $class_check = $con->prepare("SELECT class_code FROM class WHERE class_code = ?");
    $class_check->bind_param('s', $upd_classcode);
    $class_check->execute();
    if ($class_check->get_result()->num_rows > 0) {
        header("Location: faculty_class_update_form.php?error=" . urlencode("Class code already exists") . "&class_code=$original_classcode");
        exit();
    }
}

// Update class
$update_stmt = $con->prepare("UPDATE class SET 
    class_code = ?, 
    course_code = ?, 
    class_type = ?, 
    faculty_identification_code = ? 
    WHERE class_code = ?");

$update_stmt->bind_param('sssss', $upd_classcode, $upd_coursecode, $upd_classtype, $upd_facultycode, $original_classcode);

if ($update_stmt->execute()) {
    // Regenerate CSRF token after form submission
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: faculty_class_create_form.php?success=2");
} else {
    header("Location: faculty_class_update_form.php?error=" . urlencode("Update failed: " . $con->error) . "&class_code=$original_classcode");
}

$update_stmt->close();
$con->close();
?>