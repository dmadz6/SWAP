<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token.');
    }

    $con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
    if (!$con) die('Connection failed: ' . mysqli_connect_error());
    // Regenerate CSRF token after form submission
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    // Get logged-in faculty's school
    $faculty_id = $_SESSION['session_identification_code'];
    $school_stmt = $con->prepare("SELECT school_code FROM faculty WHERE faculty_identification_code = ?");
    $school_stmt->bind_param('s', $faculty_id);
    $school_stmt->execute();
    $school_result = $school_stmt->get_result();
    $school_row = $school_result->fetch_assoc();

    // Check if faculty is assigned to a school
    if (!$school_row) {
        header("Location: ../faculty_dashboard.php?error=" . urlencode("Faculty is not assigned to a school. Please contact administrator."));
        exit();
    }
    $school_code = $school_row['school_code'];

    // Validate inputs
    $class_code = $_POST["class_code"];
    $course_code = $_POST["course_code"];
    $class_type = $_POST["class_type"];
    $faculty_id_code = $_POST["faculty_identification_code"];

    if (empty($class_code) || empty($course_code) || empty($class_type) || empty($faculty_id_code)) {
        header("Location: faculty_class_create_form.php?error=" . urlencode("All fields are required."));
        exit();
    }

    // Validate course belongs to school
    // The course table contains course details, including course_code and diploma_code.
    // Join links the course table with the diploma table, allowing access to the school_code associated with the diploma
    // Final query checks only the selected course
    $course_check = $con->prepare("SELECT d.school_code 
                                  FROM course c 
                                  JOIN diploma d ON c.diploma_code = d.diploma_code 
                                  WHERE c.course_code = ?");
    $course_check->bind_param('s', $course_code);
    $course_check->execute();
    $course_result = $course_check->get_result();
    
    // This line validates whether the selected course belongs to the school of the logged-in faculty
    if ($course_result->num_rows === 0 || $course_result->fetch_assoc()['school_code'] !== $school_code) {
        header("Location: faculty_class_create_form.php?error=" . urlencode("Invalid course selection"));
        exit();
    }

    // Validate faculty belongs to school
    // Retrieves the school_code of the selected faculty 
    // Checks only the faculty that was selected
    $faculty_check = $con->prepare("SELECT school_code FROM faculty WHERE faculty_identification_code = ?");
    $faculty_check->bind_param('s', $faculty_id_code);
    $faculty_check->execute();
    $faculty_result = $faculty_check->get_result();
    
    if ($faculty_result->num_rows === 0 || $faculty_result->fetch_assoc()['school_code'] !== $school_code) {
        header("Location: faculty_class_create_form.php?error=" . urlencode("Invalid faculty selection"));
        exit();
    }
    // Fetches the school code associated with the selected faculty member.
    // Compares it with the logged-in faculty's school ($school_code).


    // Validate class code format
    if (!preg_match("/^[A-Z]{2}[0-9]{2}$/", $class_code)) {
        header("Location: faculty_class_create_form.php?error=" . urlencode("Invalid class code format. Ensure the class code entered starts with 2 capital letters followed by exactly 2 digits."));
        exit();
    }

    // Check for existing class
    $class_check = $con->prepare("SELECT class_code FROM class WHERE class_code = ?");
    $class_check->bind_param('s', $class_code);
    $class_check->execute();
    if ($class_check->get_result()->num_rows > 0) {
        header("Location: faculty_class_create_form.php?error=" . urlencode("The class code \"$class_code\" already exists. Please use a unique class code."));
        exit();
    }

    // Insert new class
    $insert_stmt = $con->prepare("INSERT INTO class (class_code, course_code, class_type, faculty_identification_code) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param('ssss', $class_code, $course_code, $class_type, $faculty_id_code);
    
    if ($insert_stmt->execute()) {
            // Regenerate CSRF token after form submission
        unset($_SESSION['csrf_token']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: faculty_class_create_form.php?success=1");
    } else {
        header("Location: faculty_class_create_form.php?error=" . urlencode("Error creating class: " . $con->error));
    }
    
    $insert_stmt->close();
    $con->close();
    exit();
}