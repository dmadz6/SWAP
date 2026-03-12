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
    $upd_classcode = isset($_POST["upd_classcode"]) ? htmlspecialchars($_POST["upd_classcode"]) : "";
    $upd_coursecode = isset($_POST["upd_coursecode"]) ? htmlspecialchars($_POST["upd_coursecode"]) : "";
    $upd_classtype = isset($_POST["upd_classtype"]) ? htmlspecialchars($_POST["upd_classtype"]) : "";
    $upd_facultycode = isset($_POST["upd_facultycode"]) ? htmlspecialchars($_POST["upd_facultycode"]) : "";
    $original_classcode = isset($_POST["original_classcode"]) ? htmlspecialchars($_POST["original_classcode"]) : "";

    // Check for empty inputs
    if (empty($upd_classcode) || empty($upd_coursecode) || empty($upd_classtype) || empty($upd_facultycode)) {
        header("Location: admin_class_update_form.php?error=" . urlencode("All fields are required.") . "&class_code=" . urlencode($original_classcode));
        exit();
    }

    // Regex pattern for validating class_code
    $class_code_pattern = "/^[A-Z]{2}[0-9]{2}$/"; // Must start with 2 letters followed by exactly 2 digits

    // Validate the format of updated class_code using regex
    if (!preg_match($class_code_pattern, $upd_classcode)) {
        // Ensures the class_code is included in the redirect URL as admin_class_update_form.php relies on it to fetch data.
        header("Location: admin_class_update_form.php?error=" . urlencode("Invalid class code format. Ensure the class code entered starts with 2 capital letters followed by exactly 2 digits.") . "&class_code=" . urlencode($original_classcode));
    }  
    else {
        // Check if the updated class_code is already used by another class
        // The query ignores the current class being updated.
        // If the class_code exists but belongs to another class, it prevents duplication.
        $class_check_stmt = $con->prepare("SELECT * FROM class WHERE class_code = ? AND class_code != ?");
        $class_check_stmt->bind_param('ss', $upd_classcode, $original_classcode);
        $class_check_stmt->execute();
        $class_check_result = $class_check_stmt->get_result();

        // Ensure no duplicate class_code
        if ($class_check_result->num_rows > 0) {
            // Ensures the class_code is included in the redirect URL as admin_class_update_form.php relies on it to fetch data.
            header("Location: admin_class_update_form.php?error=" . urlencode("The class code \"$upd_classcode\" already exists. Please use a unique class code.") . "&class_code=" . urlencode($original_classcode));
            exit();
        } else {
            // Prepare the SQL statement for updating the record
            $stmt = $con->prepare("UPDATE class SET class_code = ?, course_code = ?, class_type = ?, faculty_identification_code = ? WHERE class_code = ?");
            $stmt->bind_param('sssss', $upd_classcode, $upd_coursecode, $upd_classtype, $upd_facultycode, $original_classcode);

            // Execute the query
            if ($stmt->execute()) {
                    // Regenerate CSRF token after form submission
                unset($_SESSION['csrf_token']);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header("Location: admin_class_create_form.php?success=2");
                exit();
            } else {
                header("Location: admin_class_update_form.php?error=" . urlencode("Error executing UPDATE query: " . $stmt->error));
                exit();
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
?>

