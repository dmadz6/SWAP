<?php
// Start the session
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

$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$connect) {
    die('Could not connect: ' . mysqli_connect_errno());
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
   header("Location:  ../login.php");
   exit();
}

// Get faculty school code
$faculty_id = $_SESSION['session_identification_code'] ?? '';
$school_code = '';
if ($faculty_id) {
    $school_query = $connect->prepare("SELECT school_code FROM faculty WHERE faculty_identification_code = ?");
    $school_query->bind_param('s', $faculty_id);
    $school_query->execute();
    $school_query->bind_result($school_code);
    $school_query->fetch();
    $school_query->close();
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }
}

function assign_grade($course_score) {
    // Check if course_score is numeric
    if (!is_numeric($course_score)) {
        return 'X'; // Return 'X' if the score is not a number
    }

    if ($course_score == 4.0) {
        return 'A';
    } elseif ($course_score >= 3.5 && $course_score < 4.0) {
        return 'B+';
    } elseif ($course_score >= 3.0 && $course_score < 3.5) {
        return 'B';
    } elseif ($course_score >= 2.5 && $course_score < 3.0) {
        return 'C+';
    } elseif ($course_score >= 2.0 && $course_score < 2.5) {
        return 'C';
    } elseif ($course_score >= 1.5 && $course_score < 2.0) {
        return 'D+';
    } elseif ($course_score >= 1.0 && $course_score < 1.5) {
        return 'D';
    } elseif ($course_score >= 0.0 && $course_score < 1.0) {
        return 'F';
    } else {
        return 'X';
    }
}


// Insert record with school validation
if (isset($_POST["insert_button"])) {
    if ($_POST["insert"] == "yes") {
        $csrf_token = $_POST["csrf_token"] ?? '';
        check_csrf_token($csrf_token);

        $identification_code = $_POST["identification_code"];
        $course_code = $_POST["course_code"];
        $course_score_input = $_POST["course_score"];

        // Strict validation for course_score
        if (!is_numeric($course_score_input)) {
            header("Location: faculty_score.php?error=" . urlencode("Invalid input. Course score must be a number."));
            exit();
        }

        $course_score = (float)$course_score_input; // Cast to float after validation
        $grade = assign_grade($course_score);

        // Continue with existing logic
        if ($grade == 'X') {
            header("Location: faculty_score.php?error=" . urlencode("Invalid score (0-4 only)"));
            exit();
        }


        // Validate student belongs to faculty's school
        $student_check = $connect->prepare("
            SELECT COUNT(*) 
            FROM student s
            JOIN diploma d ON s.diploma_code = d.diploma_code 
            WHERE s.identification_code = ? AND d.school_code = ?
        ");
        $student_check->bind_param('ss', $identification_code, $school_code);
        $student_check->execute();
        $student_check->bind_result($student_count);
        $student_check->fetch();
        $student_check->close();

        if ($student_count == 0) {
            header("Location: faculty_score.php?error=" . urlencode("Student not in your school"));
            exit();
        }

        // Validate course belongs to faculty's school
        $course_check = $connect->prepare("
            SELECT COUNT(*) 
            FROM course c
            JOIN diploma d ON c.diploma_code = d.diploma_code 
            WHERE c.course_code = ? AND d.school_code = ?
        ");
        $course_check->bind_param('ss', $course_code, $school_code);
        $course_check->execute();
        $course_check->bind_result($course_count);
        $course_check->fetch();
        $course_check->close();

        if ($course_count == 0) {
            header("Location: faculty_score.php?error=" . urlencode("Course not in your school"));
            exit();
        }

        $check_query = $connect->prepare("SELECT COUNT(*) FROM semester_gpa_to_course_code WHERE identification_code = ? AND course_code = ?");
        $check_query->bind_param('ss', $identification_code, $course_code);
        $check_query->execute();
        $check_query->bind_result($count);
        $check_query->fetch();
        $check_query->close();

        if ($count > 0) {
            header("Location: faculty_score.php?error=" . urlencode("Duplicate entry"));
            exit();
        } else {
            $query = $connect->prepare("INSERT INTO semester_gpa_to_course_code (grade_id, identification_code, course_code, course_score, grade) VALUES (NULL, ?, ?, ?, ?)");
            $query->bind_param('ssds', $identification_code, $course_code, $course_score, $grade);
            if ($query->execute()) {
                // Regenerate CSRF token after form submission
                unset($_SESSION['csrf_token']);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header("Location: faculty_score.php?success=1");
                exit();
            }
        }
    }
}

// Update record with school validation
if (isset($_POST["update_button"])) {
    $csrf_token = $_POST["csrf_token"] ?? '';
    check_csrf_token($csrf_token);

    $id = $_POST["id"];
    $identification_code = $_POST["identification_code"];
    $course_code = $_POST["course_code"];
    $course_score = $_POST["course_score"];
    $grade = assign_grade($course_score);


    $query = $connect->prepare("UPDATE semester_gpa_to_course_code SET identification_code=?, course_code=?, course_score=?, grade=? WHERE grade_id=?");
    $query->bind_param('ssdsi', $identification_code, $course_code, $course_score, $grade, $id);
    $query->execute();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Record</title>
    <link rel="stylesheet" href=" ../styles.css">
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
            <h2>Student Grading System</h2>
            <p>Add, update, and organize student score records. <a href="faculty_gpa.php">VIEW GPA</a></p>
            <?php
            if (isset($_GET['success'])) {
                $messages = [
                    1 => 'Student grade created successfully.',
                    2 => 'Student grade updated successfully.',
                    3 => 'Student grade deleted successfully.'
                ];
                echo '<div id="message" class="success-message">' . $messages[$_GET['success']] . '</div>';
            }
            if (isset($_GET['error'])) {
                echo '<div id="error-message" style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>
        </div>

        <div class="card">
            <h3>Student Score Details</h3>
            <form method="post" action="faculty_score.php">
                <div class="form-group">
                    <label class="label" for="identification_code">Student Identification Code</label>
                    <select name="identification_code" required>
                        <option value="">Select Identification Code</option>
                        <?php
                        $student_query = $connect->prepare("
                            SELECT DISTINCT s.identification_code 
                            FROM student s
                            JOIN diploma d ON s.diploma_code = d.diploma_code 
                            WHERE d.school_code = ?
                                AND s.class_code IS NOT NULL 
                                AND s.class_code != ''  -- Exclude empty strings
                        ");
                        $student_query->bind_param('s', $school_code);
                        $student_query->execute();
                        $result = $student_query->get_result();
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['identification_code']) . "'>" . htmlspecialchars($row['identification_code']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <select name="course_code" required>
                        <option value="">Select Course Code</option>
                        <?php
                        $course_query = $connect->prepare("
                            SELECT c.course_code 
                            FROM course c
                            JOIN diploma d ON c.diploma_code = d.diploma_code 
                            WHERE d.school_code = ?
                        ");
                        $course_query->bind_param('s', $school_code);
                        $course_query->execute();
                        $result = $course_query->get_result();
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['course_code']) . "'>" . htmlspecialchars($row['course_code']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="course_score">Course Score</label>
                    <td><input type="text" name="course_score" value="<?php echo isset($_POST['course_score']) ? htmlspecialchars($_POST['course_score'], ENT_QUOTES, 'UTF-8') : ''; ?>" required/>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="insert" value="yes">
                <button type="submit" name="insert_button">Insert Score</button>
            </form>
        </div>

        <div class="card">
            <h3>Student Score Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src=" ../scroll_up.png" alt="Scroll to top"></button>
            <?php
            $query = $connect->prepare("
                SELECT sg.* 
                FROM semester_gpa_to_course_code sg
                JOIN student s ON sg.identification_code = s.identification_code
                JOIN diploma d ON s.diploma_code = d.diploma_code
                WHERE d.school_code = ?
            ");
            $query->bind_param('s', $school_code);
            $query->execute();
            $result = $query->get_result();

            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr><th>ID Code</th><th>Course</th><th>Score</th><th>Grade</th><th colspan="2">Operations</th></tr>';
            
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo "<td>" . htmlspecialchars($row['identification_code']) . "</td>";
                echo "<td>" . htmlspecialchars($row['course_code']) . "</td>";
                echo "<td>" . htmlspecialchars($row['course_score']) . "</td>";
                echo "<td>" . htmlspecialchars($row['grade']) . "</td>";
                echo "<td><a href='faculty_edit.php?operation=edit&id=" . $row['grade_id'] . "'>Edit</a></td>";
                echo '</tr>';
            }
            echo '</table>';
            ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic. All rights reserved.</p>
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