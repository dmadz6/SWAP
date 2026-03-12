<?php
session_start();
session_regenerate_id(true);
define('SESSION_TIMEOUT', 600); // 600 seconds = 10 minutes
define('WARNING_TIME', 60); // 60 seconds (1 minute before session ends)
define('FINAL_WARNING_TIME', 3); // Final warning 3 seconds before logout

// Function to check and handle session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > SESSION_TIMEOUT) {
            return;
        }
    }
    $_SESSION['last_activity'] = time();
}

checkSessionTimeout();

$remaining_time = (isset($_SESSION['last_activity'])) 
    ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity']) 
    : SESSION_TIMEOUT;

$con = mysqli_connect("localhost","root","","xyz polytechnic");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Check faculty role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    header("Location: ../login.php");
    exit();
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";
$faculty_id = $_SESSION['session_identification_code'];
$school_query = $con->prepare("
    SELECT f.school_code 
    FROM faculty f 
    WHERE f.faculty_identification_code = ?
");
$school_query->bind_param('s', $faculty_id);
$school_query->execute();
$school_result = $school_query->get_result();

// Check if faculty is assigned to a school
if ($school_result->num_rows === 0) {
    header("Location: ../faculty_dashboard.php?error=" . urlencode("Faculty is not assigned to a school. Please contact administrator."));
    exit();
}

$school_row = $school_result->fetch_assoc();
$school_code = $school_row['school_code'];

// Get diplomas for this school
$diploma_query = $con->prepare("
    SELECT diploma_code, diploma_name 
    FROM diploma 
    WHERE school_code = ?
");
$diploma_query->bind_param('s', $school_code);
$diploma_query->execute();
$diplomas = $diploma_query->get_result();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management</title>
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
            <a href="../faculty_dashboard.php">Home</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Course Management</h2>
            <p>Create and manage courses.</p>
            <?php
                if (isset($_GET['error'])) {
                    echo '<div id="message" style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</div>';
                }
                if (isset($_GET['success']) && $_GET['success'] == 2) {
                    echo '<div id="message" class="success-message">Course updated successfully.</div>';
                }
            ?>
        </div>

        <div class="card">
            <h3>Course Details</h3>
            <form method="POST" action="faculty_course_create.php">
                <div class="form-group">
                    <label>Course Code</label>
                    <input type="text" name="course_code" pattern="[A-Z]{1}\d{2}" title="Format: 1 uppercase letter followed by 2 digits" required>
                </div>
                <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="course_name" required>
                </div>

                <div class="form-group">
                    <label>Diploma Code</label>
                    <select name="diploma_code">
                        <?php while($row = $diplomas->fetch_assoc()): ?>
                            <option value="<?= $row['diploma_code'] ?>"><?= $row['diploma_code'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label">Start Date</label>
                    <input type="date" name="course_start_date" 
                        value="<?php echo $course_row['course_start_date'] ? htmlspecialchars($course_row['course_start_date']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="label">End Date</label>
                    <input type="date" name="course_end_date" 
                        value="<?php echo $course_row['course_end_date'] ? htmlspecialchars($course_row['course_end_date']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="No Status" selected>No Status</option>
                        <option value="To start">To Start</option>
                        <option value="In-progress">In Progress</option>
                        <option value="Ended">Ended</option>
                    </select>
                </div>

                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit">Create Course</button>
            </form>
        </div>

        <div class="card">
            <h3>Course Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src="../scroll_up.png" alt="Scroll to top"></button>
            <?php
            // Fetch courses for the faculty's school
            $course_query = "SELECT c.*, d.diploma_name 
                           FROM course c 
                           JOIN diploma d ON c.diploma_code = d.diploma_code 
                           WHERE d.school_code = ?
                           ORDER BY c.course_code";
            $course_stmt = $con->prepare($course_query);
            $course_stmt->bind_param('s', $school_code);
            $course_stmt->execute();
            $course_result = $course_stmt->get_result();

            if ($course_result->num_rows > 0) {
                echo '<table border="1" bgcolor="white" align="center">';
                echo '<tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Diploma</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Operations</th>
                    </tr>';

                while ($course_row = $course_result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($course_row['course_code']) . '</td>';
                    echo '<td>' . htmlspecialchars($course_row['course_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($course_row['diploma_name']) . '</td>';
                    echo '<td>' . ($course_row['course_start_date'] ? htmlspecialchars(date('Y-m-d', strtotime($course_row['course_start_date']))) : 'No Start Date') . '</td>';
                    echo '<td>' . ($course_row['course_end_date'] ? htmlspecialchars(date('Y-m-d', strtotime($course_row['course_end_date']))) : 'No End Date') . '</td>';
                    echo '<td>' . htmlspecialchars($course_row['status']) . '</td>';
                    echo '<td><a href="faculty_course_update_form.php?course_code=' . htmlspecialchars($course_row['course_code']) . '">Edit</a></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="text-center">No courses found for your school.</p>';
            }
            ?>
        </div>
    </div>
</body>
<footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
</footer>
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
                showLogoutWarning("You will be logged out due to inactivity.", "../logout.php");
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
</html>