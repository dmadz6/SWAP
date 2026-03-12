<?php
session_start();
session_regenerate_id(true);
define('SESSION_TIMEOUT', 600);
define('WARNING_TIME', 60);
define('FINAL_WARNING_TIME', 3);

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > SESSION_TIMEOUT) return;
    }
    $_SESSION['last_activity'] = time();
}

checkSessionTimeout();

$remaining_time = isset($_SESSION['last_activity']) 
    ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity']) 
    : SESSION_TIMEOUT;

$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) die('Could not connect: ' . mysqli_connect_errno());

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    header("Location: login.php");
    exit();
}

// Session variable stores the unique id code of the currently logged-in faculty member
// Retrieve the row where faculty_identification_code matches the logged-in faculty ID
$faculty_id = $_SESSION['session_identification_code'];
$school_query = "SELECT school_code FROM faculty WHERE faculty_identification_code = ?";
$school_stmt = $con->prepare($school_query);
$school_stmt->bind_param('s', $faculty_id);
$school_stmt->execute();
$school_result = $school_stmt->get_result();
$school_row = $school_result->fetch_assoc();
$school_code = $school_row['school_code'];
// Retrieve the school code as an array

// Fetch course codes for dropdown (filtered by school)
// Retrieves the course_code column from the course table
// Course table is then joined with the diploma table as the course table does not contain school_code but diploma table does
// The join ensures courses are filtered based on school
$course_query = "SELECT c.course_code 
                FROM course c 
                JOIN diploma d ON c.diploma_code = d.diploma_code 
                WHERE d.school_code = ?";
$course_stmt = $con->prepare($course_query);
$course_stmt->bind_param('s', $school_code);
$course_stmt->execute();
$course_result = $course_stmt->get_result();

// Fetch faculty for the dropdown (filtered by school)
// The faculty_identification_code in the user table is joined with the identification_code in the user table
// With the tables joined, faculty can be filtered based on school
// Filters faculty members to only include those from a specific school.
$faculty_query = "SELECT u.identification_code, u.full_name 
                FROM user u 
                JOIN faculty f ON u.identification_code = f.faculty_identification_code 
                WHERE f.school_code = ? AND u.role_id = 2";
$faculty_stmt = $con->prepare($faculty_query);
$faculty_stmt->bind_param('s', $school_code);
$faculty_stmt->execute();
$faculty_result = $faculty_stmt->get_result();

// Validate class_code parameter
if (!isset($_GET["class_code"]) || empty($_GET["class_code"])) {
    header("Location: faculty_class_create_form.php");
    exit();
}

$edit_classcode = htmlspecialchars($_GET["class_code"]);
$full_name = $_SESSION['session_full_name'] ?? "";

// Fetch class records (filtered by school)
// cl.* ensures only columns from the class table is selected, avoiding confusion with columns from faculty
// Joins class table with faculty table, ensuring that only classes assigned to a faculty is fetched
// When joined, classes are then filtered based on school
// Filters query results to only include classes that belong to the faculty’s school and class_code
$class_query = "SELECT cl.* 
               FROM class cl 
               JOIN faculty f ON cl.faculty_identification_code = f.faculty_identification_code 
               WHERE cl.class_code = ? AND f.school_code = ?";
$class_stmt = $con->prepare($class_query);
$class_stmt->bind_param('ss', $edit_classcode, $school_code);
$class_stmt->execute();
$class_result = $class_stmt->get_result();

if ($class_result->num_rows === 0) {
    header("Location: faculty_class_create_form.php?error=" . urlencode("Class code \"$edit_classcode\" does not exist."));
    exit();
}

$class_row = $class_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Class</title>
    <link rel="stylesheet" href="../styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="../faculty_dashboard">Home</a>
            <a href="../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Class Management</h2>
            <p>Update class records.</p>
            <?php
            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            // If ?success=2 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="success-message">Class updated successfully.</div>';
            }
            ?>
        </div>

        <div class="card">
            <h3>Update Class Details</h3>
            <form method="POST" action="faculty_class_update.php">
                <input type="hidden" name="original_classcode" value="<?php echo htmlspecialchars($class_row['class_code']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label class="label" for="class_code">Class Code</label>
                    <input type="text" name="upd_classcode" value="<?php echo htmlspecialchars($class_row['class_code']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <select name="upd_coursecode" required>
                        <option value="" disabled>Select Course Code</option>
                        <!-- Checks if the current course in the loop ($course_row['course_code']) matches the course code of the class being edited ($class_row['course_code']) -->
                        <?php while ($course_row = $course_result->fetch_assoc()): 
                            $selected = $course_row['course_code'] === $class_row['course_code'] ? 'selected' : ''; ?>
                            <option value="<?= htmlspecialchars($course_row['course_code']) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($course_row['course_code']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="class_type">Class Type</label>
                    <select name="upd_classtype" required>
                        <!-- Checks if the current class type ($class_row['class_type']) is already set to Semester/Term -->
                        <!-- If true, it adds selected, making Semester/Term the default selected option, else nothing is done -->
                        <option value="Semester" <?= $class_row['class_type'] === 'Semester' ? 'selected' : '' ?>>Semester</option>
                        <option value="Term" <?= $class_row['class_type'] === 'Term' ? 'selected' : '' ?>>Term</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="faculty_identification_code">Assigned Faculty</label>
                    <select name="upd_facultycode" required>
                        <option value="" disabled>Select Faculty</option>
                        <!-- Checks if the current faculty in the loop ($faculty_row['identification_code']) matches the faculty assigned to the class ($class_row['faculty_identification_code']) -->
                        <?php while ($faculty_row = $faculty_result->fetch_assoc()): 
                            $selected = $faculty_row['identification_code'] === $class_row['faculty_identification_code'] ? 'selected' : ''; ?>
                            <option value="<?= htmlspecialchars($faculty_row['identification_code']) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($faculty_row['full_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit">Update Class</button>
            </form>
        </div>
    </div>

    <div id="logoutWarningModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="logoutWarningMessage"></p>
            <button id="logoutWarningButton">OK</button>
        </div>
    </div>

    <script>
        // Remaining time in seconds (calculated in PHP)
        const remainingTime = <?php echo $remaining_time; ?>;
        const warningTime = <?php echo WARNING_TIME; ?>; // 1 minute before session ends
        const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>; // Final warning 3 seconds before logout

        // Function to show the logout warning modal
        function showLogoutWarning(message, redirectUrl = null) {
            const modal = document.getElementById("logoutWarningModal");
            const modalMessage = document.getElementById("logoutWarningMessage");
            const modalButton = document.getElementById("logoutWarningButton");

            modalMessage.innerText = message;
            modal.style.display = "flex";

            modalButton.onclick = function () {
                modal.style.display = "none";
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            };
        }

        // Notify user 1 minute before logout
        if (remainingTime > warningTime) {
            setTimeout(() => {
                showLogoutWarning(
                    "You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in."
                );
            }, (remainingTime - warningTime) * 1000);
        }

        // Final notification 3 seconds before logout
        if (remainingTime > finalWarningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out due to inactivity.", "logout.php");
            }, (remainingTime - finalWarningTime) * 1000);
        }
        setTimeout(function() {
        const messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.style.display = 'none';
        }
        }, 10000);

        // Automatically log the user out when the session expires
        setTimeout(() => {
            window.location.href = "logout.php";
        }, remainingTime * 1000);
    </script>

</body>
</html>