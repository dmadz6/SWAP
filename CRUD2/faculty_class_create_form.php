<?php
session_start(); // Start the session
session_regenerate_id(true);
define('SESSION_TIMEOUT', 600); // 600 seconds = 10 minutes
define('WARNING_TIME', 60); // 60 seconds (1 minute before session ends)
define('FINAL_WARNING_TIME', 3); // Final warning 3 seconds before logout

// Function to check and handle session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        // Calculate the elapsed time since the last activity
        $inactive_time = time() - $_SESSION['last_activity'];

        // If the elapsed time exceeds the timeout duration, just return
        if ($inactive_time > SESSION_TIMEOUT) {
            return; // Let JavaScript handle logout
        }
    }

    // Update 'last_activity' timestamp for session tracking
    $_SESSION['last_activity'] = time();
}

// Call the session timeout check at the beginning
checkSessionTimeout();

// Calculate remaining session time for the user
$remaining_time = (isset($_SESSION['last_activity'])) 
    ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity']) 
    : SESSION_TIMEOUT;

$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    // Redirect to login page if the user is not logged as a faculty
    header("Location: ../login.php");
    exit();
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Session variable stores the unique id code of the currently logged-in faculty member
// Retrieve the row where faculty_identification_code matches the logged-in faculty ID
$faculty_id = $_SESSION['session_identification_code'];
$school_query = "SELECT school_code FROM faculty WHERE faculty_identification_code = ?";
$school_stmt = $con->prepare($school_query);
$school_stmt->bind_param('s', $faculty_id);
$school_stmt->execute();
$school_result = $school_stmt->get_result();
$school_row = $school_result->fetch_assoc();
// Retrieve the school code as an array


// Check if faculty is assigned to a school e.g. BUS
if (!$school_row) {
    // Redirect with an error message if the faculty is not assigned to a school
    header("Location: ../faculty_dashboard.php?error=" . urlencode("Faculty is not assigned to a school. Please contact administrator."));
    exit();
}
// Retrives the school_code value from school_row
$school_code = $school_row['school_code'];

// Fetch course codes for the dropdown (filtered by school)
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>
    <link rel="stylesheet" href="../styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="../faculty_dashboard.php">Home</a>
            <a href="../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Class Management</h2>
            <p>Add, update, and organize class records.</p>
            <?php
            // If ?success=1 is set in the URL, display a create success message 
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div id="message" class="success-message">Class created successfully.</div>';
            }

            // If ?success=2 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="success-message">Class updated successfully.</div>';
            }

            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>
        </div>

        <div class="card">
            <h3>Class Details</h3>
            <form method="POST" action="faculty_class_create.php">
                <div class="form-group">
                    <label class="label" for="class_code">Class Code</label>
                    <input type="text" name="class_code" placeholder="Enter Class code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <select name="course_code" required>
                        <option value="" disabled selected>Select a Course Code</option>
                        <?php
                        if ($course_result->num_rows > 0) {
                            while ($row = $course_result->fetch_assoc()) {
                                // course_code is printed twice: once for the 'value' attribute and the other is for the dropdown options
                                echo "<option value='" . htmlspecialchars($row['course_code']) . "'>" . htmlspecialchars($row['course_code']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="class_type">Class Type</label>
                    <select name="class_type" required>
                        <option value="" disabled selected>Select a Class Type</option>
                        <option value="Semester">Semester</option>
                        <option value="Term">Term</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="faculty_identification_code">Assigned Faculty</label>
                    <select name="faculty_identification_code" required>
                        <option value="" disabled selected>Select a Faculty</option>
                        <?php
                        if ($faculty_result->num_rows > 0) {
                            while ($row = $faculty_result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['identification_code']) . "'>" . htmlspecialchars($row['full_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit">Submit</button>
            </form>
        </div>

        <div class="card">
            <h3>Class Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src="../scroll_up.png" alt="Scroll to top"></button>
            <?php
            // Fetch faculty for the dropdown (filtered by school)
            // The faculty_identification_code in the user table is joined with the identification_code in the user table
            // With the tables joined, faculty can be filtered based on school
            // Filters faculty members to only include those from a specific school.
            $faculty_map_query = "SELECT u.identification_code, u.full_name 
                                 FROM user u 
                                 JOIN faculty f ON u.identification_code = f.faculty_identification_code 
                                 WHERE f.school_code = ?";
            $faculty_map_stmt = $con->prepare($faculty_map_query);
            $faculty_map_stmt->bind_param('s', $school_code);
            $faculty_map_stmt->execute();
            $faculty_map_result = $faculty_map_stmt->get_result();

            $faculty_map = [];
            while ($row = $faculty_map_result->fetch_assoc()) {
                $faculty_map[$row['identification_code']] = $row['full_name'];
            }

            // Fetch class records (filtered by school)
            // cl.* ensures only columns from the class table is selected, avoiding confusion with columns from faculty
            // Joins class table with faculty table, ensuring that only classes assigned to a faculty is fetched
            // When joined, classes are then filtered based on school
            // Filters query results to only include classes that belong to the faculty’s school
            $class_query = "SELECT cl.* 
                           FROM class cl 
                           JOIN faculty f ON cl.faculty_identification_code = f.faculty_identification_code 
                           WHERE f.school_code = ?";
            $class_stmt = $con->prepare($class_query);
            $class_stmt->bind_param('s', $school_code);
            $class_stmt->execute();
            $class_result = $class_stmt->get_result();

            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr><th>Class Code</th><th>Course Code</th><th>Class Type</th><th>Assigned Faculty</th><th colspan="2">Operations</th></tr>';

            while ($class_row = $class_result->fetch_assoc()) {
                // Looks up faculty name and if the identification code matches, assign that faculty
                // If faculty is not found, Unknown Faculty is given as a result
                $faculty_name = $faculty_map[$class_row['faculty_identification_code']] ?? "Unknown Faculty";
                echo '<tr>';
                echo '<td>' . htmlspecialchars($class_row['class_code']) . '</td>';
                echo '<td>' . htmlspecialchars($class_row['course_code']) . '</td>';
                echo '<td>' . htmlspecialchars($class_row['class_type']) . '</td>';
                echo '<td>' . htmlspecialchars($faculty_name) . '</td>';
                echo '<td> <a href="faculty_class_update_form.php?class_code=' . htmlspecialchars($class_row['class_code']) . '">Edit</a> </td>';
                echo '</tr>';
            }
            echo '</table>';
            ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>

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

        // Scroll to top functionality
        function scroll_to_top() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>

</body>
</html>