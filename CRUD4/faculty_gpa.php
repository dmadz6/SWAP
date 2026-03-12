<?php
// Start the session
session_start();
session_regenerate_id(true);
define('SESSION_TIMEOUT', 600);
define('WARNING_TIME', 60);
define('FINAL_WARNING_TIME', 3);
 
// Session timeout function remains the same
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

$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$connect) die('Connection failed: ' . mysqli_connect_error());

// Get faculty school code
$school_code = '';
if (isset($_SESSION['session_identification_code'])) {
    $faculty_id = $_SESSION['session_identification_code'];
    $school_query = $connect->prepare("SELECT school_code FROM faculty WHERE faculty_identification_code = ?");
    $school_query->bind_param('s', $faculty_id);
    $school_query->execute();
    $school_query->bind_result($school_code);
    $school_query->fetch();
    $school_query->close();
}

// CSRF token handling remains the same
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$full_name = $_SESSION['session_full_name'] ?? "";

function check_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
}

// Modified GPA view functionality
if (isset($_POST["view_button"])) {
    check_csrf_token($_POST["csrf_token"] ?? '');
    
    $identification_code = $_POST["identification_code"];
    
    if (empty($identification_code)) {
        header("Location: faculty_gpa.php?error=" . urlencode("Please select an Identification Code"));
        exit();
    }

    // Verify student belongs to faculty's school
    $student_check = $connect->prepare("
        SELECT COUNT(*) 
        FROM student s
        JOIN diploma d ON s.diploma_code = d.diploma_code
        WHERE s.identification_code = ? AND d.school_code = ?
    ");
    $student_check->bind_param('ss', $identification_code, $school_code);
    $student_check->execute();
    $student_check->bind_result($valid_student);
    $student_check->fetch();
    $student_check->close();

    if (!$valid_student) {
        header("Location: faculty_gpa.php?error=" . urlencode("Student not in your school"));
        exit();
    }

    // Calculate GPA with school filter
    $gpa_query = $connect->prepare("
        SELECT AVG(sg.course_score) 
        FROM semester_gpa_to_course_code sg
        JOIN student s ON sg.identification_code = s.identification_code
        JOIN diploma d ON s.diploma_code = d.diploma_code
        WHERE sg.identification_code = ? AND d.school_code = ?
    ");
    $gpa_query->bind_param('ss', $identification_code, $school_code);
    $gpa_query->execute();
    $gpa_query->bind_result($gpa);
    $gpa_query->fetch();
    $gpa_query->close();

    if (!$gpa) {
        header("Location: faculty_gpa.php?error=" . urlencode("No GPA data found"));
        exit();
    }

    // Update student_score table
    // Delete existing entry for the identification code
    $delete_query = $connect->prepare("DELETE FROM student_score WHERE identification_code = ?");
    $delete_query->bind_param('s', $identification_code);
    $delete_success = $delete_query->execute();
    $delete_query->close();

    if ($delete_success) {
    // Insert new GPA
        $insert_query = $connect->prepare("
            INSERT INTO student_score (identification_code, semester_gpa) 
            VALUES (?, ?)
        ");
        $insert_query->bind_param('sd', $identification_code, $gpa);
        if ($insert_query->execute()) {
            // Regenerate CSRF token after form submission
            unset($_SESSION['csrf_token']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: faculty_gpa.php?success=2");
        } else {
            header("Location: faculty_gpa.php?error=" . urlencode("Database error on insert"));
        }
        $insert_query->close();
    } else {
        header("Location: faculty_gpa.php?error=" . urlencode("Database error on delete"));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student GPA</title>
    <link rel="stylesheet" href=" ../styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>

    <div class="navbar">
        <div class="navbar-brand">
            <img src=" ../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href=" ../faculty_dashboard.php">Home</a>
            <a href=" ../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Student GPA</h2>
            <p>View student's Grade Point Average. <a href="faculty_score.php">VIEW STUDENT SCORES</a></p>
            <?php
            // If ?success=1 is set in the URL, display a success message
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div id="message" class="success-message">Student grade created successfully.</div>';
            }            

            // If ?success=2 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="success-message">Student grade updated successfully.</div>';
            }

            // If ?success=3 is set in the URL, display a delete message
            if (isset($_GET['success']) && $_GET['success'] == 3) {
                echo '<div id="message" class="success-message">Student grade deleted successfully.</div>';
            }

            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="error-message" style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>
        </div>

        <div class="card">
            <h3>Student GPA Details</h3>
            <form method="post" action="faculty_gpa.php">
                <div class="form-group">
                    <label>Student Identification Code</label>
                    <select name="identification_code" required>
                        <option value="">Select Identification Code</option>
                        <?php
                        $students_query = $connect->prepare("
                            SELECT s.identification_code 
                            FROM student s
                            JOIN diploma d ON s.diploma_code = d.diploma_code
                            WHERE d.school_code = ?
                        ");
                        $students_query->bind_param('s', $school_code);
                        $students_query->execute();
                        $result = $students_query->get_result();
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['identification_code']) . "'>" 
                                 . htmlspecialchars($row['identification_code']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" name="view_button">View GPA</button>
            </form>
        </div>

        <div class="card">
            <h3>Student GPA Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src=" ../scroll_up.png" alt="Scroll to top"></button>
            <?php
            $records_query = $connect->prepare("
                SELECT ss.identification_code, ss.semester_gpa 
                FROM student_score ss
                JOIN student s ON ss.identification_code = s.identification_code
                JOIN diploma d ON s.diploma_code = d.diploma_code
                WHERE d.school_code = ?
            ");
            $records_query->bind_param('s', $school_code);
            $records_query->execute();
            $result = $records_query->get_result();

            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr><th>Identification Code</th><th>GPA</th></tr>';
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>" . htmlspecialchars($row['identification_code']) . "</td>
                    <td>" . number_format($row['semester_gpa'], 2) . "</td>
                </tr>";
            }
            echo "</table>";
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
                showLogoutWarning("You will be logged out due to inactivity.", " ../logout.php");
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
            window.location.href = " ../logout.php";
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