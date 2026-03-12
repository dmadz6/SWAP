<?php
session_start();

// Validate CSRF token
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }

    // Connect to the database
    $con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
    if (!$con) {
        die('Could not connect: ' . mysqli_connect_errno());
    }

    // Retrieve form data and sanitize inputs
    $student_name = strtoupper(htmlspecialchars(trim($_POST["student_name"])));
    $phone_number = htmlspecialchars(trim($_POST["phone_number"]));
    $student_id_code = strtoupper(htmlspecialchars(trim($_POST["student_id_code"])));
    $class_codes = [
        strtoupper(htmlspecialchars(trim($_POST["class_code_1"]))),
        strtoupper(htmlspecialchars(trim($_POST["class_code_2"]))),
        strtoupper(htmlspecialchars(trim($_POST["class_code_3"])))
    ];
    $diploma_code = htmlspecialchars(trim($_POST["diploma_code"]));

    // Validation
    if (empty($student_name) || empty($phone_number) || empty($student_id_code) || empty($diploma_code)) {
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("All fields are required except class codes."));
        exit();
    }

    if (!preg_match('/^[a-zA-Z ]+$/', $student_name)) {
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Student name must contain only alphabets and spaces."));
        exit();
    }

    if (!preg_match('/^\d{8}$/', $phone_number)) {
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Phone number must be exactly 8 numbers."));
        exit();
    }

    if (!preg_match('/^S\d{3}$/', $student_id_code)) {
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Invalid Student ID format. It must start with letter 'S' followed by 3 numbers."));
        exit();
    }

    $non_null_class_codes = array_filter($class_codes);
    if (count($non_null_class_codes) !== count(array_unique($non_null_class_codes))) {
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Ensure that all classes are unique."));
        exit();
    }

    // Check for existing phone number
    $stmt = $con->prepare("SELECT COUNT(*) FROM user WHERE phone_number = ?");
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $stmt->bind_result($phone_exists);
    $stmt->fetch();
    $stmt->close();

    if ($phone_exists > 0) {
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Phone number already exists."));
        exit();
    }

    // Check for existing student ID
    $stmt = $con->prepare("SELECT COUNT(*) FROM user WHERE identification_code = ?");
    $stmt->bind_param("s", $student_id_code);
    $stmt->execute();
    $stmt->bind_result($id_exists);
    $stmt->fetch();
    $stmt->close();

    if ($id_exists > 0) {
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Student ID code already exists."));
        exit();
    }

    // Check if all class codes exist in the `class` table
    foreach ($class_codes as $class_code) {
        if (!empty($class_code)) {
            // Validate class code format
            if (!preg_match('/^[A-Z]{2}\d{2}$/', $class_code)) {
                header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Invalid class code format for Class Code. Each must be 2 uppercase letters followed by 2 digits.") . "&student_id=" . urlencode($student_id_code));
                exit();
            }

            // Check if the class exists in the database
            $stmt = $con->prepare("SELECT COUNT(*) FROM class WHERE class_code = ?");
            $stmt->bind_param('s', $class_code);
            $stmt->execute();
            $stmt->bind_result($class_exists);
            $stmt->fetch();
            $stmt->close();

            if ($class_exists == 0) {
                header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Class $class_code does not exist.") . "&student_id=" . urlencode($student_id_code));
                exit();
            }
        }
    }

    // Check if diploma code exists
    $stmt = $con->prepare("SELECT COUNT(*) FROM diploma WHERE diploma_code = ?");
    $stmt->bind_param("s", $diploma_code);
    $stmt->execute();
    $stmt->bind_result($diploma_exists);
    $stmt->fetch();
    $stmt->close();

    if ($diploma_exists == 0) {
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Diploma code does not exist."));
        exit();
    }

    // Insert data into the database
    $con->begin_transaction();
    $success = true;

    // Step 1: Insert into `user` table
    $stmt = $con->prepare("INSERT INTO `user` (identification_code, email, phone_number, full_name, role_id, password) VALUES (?, ?, ?, ?, ?, ?)");
    $role_id = 3;
    $default_password = password_hash("xyzpassword123!" . $student_id_code, PASSWORD_DEFAULT);
    $email = $student_id_code . "@gmail.com";
    $stmt->bind_param("ssisis", $student_id_code, $email, $phone_number, $student_name, $role_id, $default_password);

    if (!$stmt->execute()) {
        $success = false;
        $con->rollback();
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Error inserting into `user` table: " . $stmt->error));
        exit();
    }
    $stmt->close();

    // Step 2: Insert into `student` table (Valid Class Codes First, NULL Last)
    $valid_class_codes = [];
    $null_class_codes = [];

    foreach ($class_codes as $class_code) {
        if (!empty($class_code)) {
            $valid_class_codes[] = $class_code;
        } else {
            $null_class_codes[] = null;
        }
    }

    // Insert valid class codes first
    foreach ($valid_class_codes as $class_code) {
        $stmt = $con->prepare("INSERT INTO student (identification_code, class_code, diploma_code) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $student_id_code, $class_code, $diploma_code);

        if (!$stmt->execute()) {
            $success = false;
            $con->rollback();
            header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Error inserting into `student` table: " . $stmt->error));
            exit();
        }
        $stmt->close();
    }

    // Insert NULL class codes last
    foreach ($null_class_codes as $null_class_code) {
        $stmt = $con->prepare("INSERT INTO student (identification_code, class_code, diploma_code) VALUES (?, NULL, ?)");
        $stmt->bind_param("ss", $student_id_code, $diploma_code);

        if (!$stmt->execute()) {
            $success = false;
            $con->rollback();
            header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Error inserting into `student` table: " . $stmt->error));
            exit();
        }
        $stmt->close();
    }

    if ($success) {
        $con->commit();
            // Regenerate CSRF token after form submission
        unset($_SESSION['csrf_token']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: faculty_create_stu_recordform.php?success=1");
        exit();
    }
}
?>
