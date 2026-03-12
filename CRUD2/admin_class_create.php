<?php
session_start();

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

    // Retrieve form data
    $class_code = isset($_POST["class_code"]) ? htmlspecialchars($_POST["class_code"]) : "";
    $course_code = isset($_POST["course_code"]) ? htmlspecialchars($_POST["course_code"]) : "";
    $class_type = isset($_POST["class_type"]) ? htmlspecialchars($_POST["class_type"]) : "";
    $faculty_identification_code = isset($_POST["faculty_identification_code"]) ? htmlspecialchars($_POST["faculty_identification_code"]) : "";

    // Check for empty inputs
    if (empty($class_code) || empty($course_code) || empty($class_type) || empty($faculty_identification_code)) {
        header("Location: admin_class_create_form.php?error=" . urlencode("All fields are required."));
        exit();
    }

    // Regex pattern for validating class_code
    $class_code_pattern = "/^[A-Z]{2}[0-9]{2}$/"; // Must start with 2 letters followed by exactly 2 digits
   
    // Validate the format of class_code using regex
    if (!preg_match($class_code_pattern, $class_code)) {
        header("Location: admin_class_create_form.php?error=" . urlencode("Invalid class code format. Ensure the class code entered starts with 2 capital letters followed by exactly 2 digits."));
        exit();
    } 
    else {
        // Check if the class_code already exists in the `class` table
        // If the entered class code already exists in the database, prevent duplication
        $class_check_stmt = $con->prepare("SELECT * FROM class WHERE class_code = ?");
        $class_check_stmt->bind_param('s', $class_code);
        $class_check_stmt->execute();
        $class_check_result = $class_check_stmt->get_result();

        // If a row exists, it means the class_code is already taken
        if ($class_check_result->num_rows > 0) {
            header("Location: admin_class_create_form.php?error=" . urlencode("The class code \"$class_code\" already exists. Please use a unique class code."));
            exit();
        } else {
            // Prepare the SQL statement for insertion
            // If no duplicates are found, carry on with the class creation
            $stmt = $con->prepare("INSERT INTO `class` (`class_code`, `course_code`, `class_type`, `faculty_identification_code`) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $class_code, $course_code, $class_type, $faculty_identification_code);
            // Execute the query
            if ($stmt->execute()) {
                    // Regenerate CSRF token after form submission
                unset($_SESSION['csrf_token']);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header("Location: admin_class_create_form.php?success=1");
                exit();
            } else {
                header("Location: admin_class_create_form.php?error=" . urlencode("Error executing INSERT query: " . $stmt->error));
            }

            // Close the statement
            $stmt->close();
        }

        // Close additional statements
        $class_check_stmt->close();
    }

    // Close the database connection
    $con->close();
}