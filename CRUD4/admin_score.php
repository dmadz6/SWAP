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


// Connect to the database 'xyz polytechnic'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$connect) {
    die('Could not connect: ' . mysqli_connect_errno());
}


// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    //Redirect to login page if the user is not logged in or not an admin
   header("Location: ../login.php");
   exit();
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name']:"";


// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }
}

// Function to assign grades based on course score
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


// Insert record functionality for 'testing' database
if (isset($_POST["insert_button"])) {
    if ($_POST["insert"] == "yes") {
         //Validate CSRF token
        $csrf_token = $_POST["csrf_token"] ?? '';
        check_csrf_token($csrf_token);


        $identification_code = $_POST["identification_code"];
        $course_code = $_POST["course_code"];
        $course_score_input = $_POST["course_score"];

        // Strict validation for course_score
        if (!is_numeric($course_score_input)) {
            header("Location: admin_score.php?error=" . urlencode("Invalid input. Course score must be a number."));
            exit();
        }

        $course_score = (float)$course_score_input; // Cast to float after validation
        $grade = assign_grade($course_score);

        // Continue with existing logic
        if ($grade == 'X') {
            header("Location: admin_score.php?error=" . urlencode("Invalid score (0-4 only)"));
            exit();
        }

        // Input validation: Check if all inputs are filled  'Error: All fields must be filled out!'
        if (empty($identification_code) || empty($course_code) || empty($course_score) || empty($grade)) {
            header("Location: admin_score.php?error=" . urlencode("All fields are required."));
            exit();
        } else {
            // Check if the combination of identification_code and course_code already exists
            $check_query = $connect->prepare("SELECT COUNT(*) FROM semester_gpa_to_course_code WHERE identification_code = ? AND course_code = ?");
            $check_query->bind_param('ss', $identification_code, $course_code);
            $check_query->execute();
            $check_query->bind_result($count);
            $check_query->fetch();
            $check_query->close();

            if ($count > 0) {
                // If the combination exists, display an error message
                header("Location: admin_score.php?error=" . urlencode("Identification Code and Course Code already exist!"));
                exit();
            } else {
                // If the combination doesn't exist, proceed with the insertion
                $query = $connect->prepare("INSERT INTO semester_gpa_to_course_code (grade_id, identification_code, course_code, course_score, grade) VALUES (NULL, ?, ?, ?, ?)");
                $query->bind_param('ssds', $identification_code, $course_code, $course_score, $grade); // Bind the parameters
                if ($query->execute()) {
                    // Regenerate CSRF token after form submission
                    unset($_SESSION['csrf_token']);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header("Location: admin_score.php?success=1");
                    exit();
                }
            }
        }
    }
}

// Update record functionality 
if (isset($_POST["update_button"])) {
    $csrf_token = $_POST["csrf_token"] ?? '';
    check_csrf_token($csrf_token);

    $id=$_POST["id"];
    $identification_code = $_POST["identification_code"];
    $course_code = $_POST["course_code"];
    $course_score = (float)$_POST["course_score"];
    $grade = assign_grade($course_score); // Automatically assign grade

    $query = $connect->prepare("UPDATE semester_gpa_to_course_code SET identification_code=?, course_code=?, course_score=?, grade=? WHERE grade_id=?");
    $query->bind_param('ssdsi', $identification_code, $course_code, $course_score, $grade, $id); // Bind the parameters
    $query->execute();
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Record</title>
    <link rel="stylesheet" href=" ../styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>

    <div class="navbar">
        <div class="navbar-brand">
            <img src="../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href=" ../admin_dashboard.php">Home</a>
            <a href=" ../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Student Grading System</h2>
            <p>Add, update, and organize student score records. <a href="admin_gpa.php">VIEW GPA</a></p>
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
            <h3>Student Score Details</h3>
            <form method="post" action="admin_score.php">
                <div class="form-group">
                    <label class="label" for="identification_code">Student Identification Code</label>
                    <select name="identification_code" required>
                        <option value="">Select Identification Code</option>
                        <?php
                        // Fetch unique identification codes from the database
                        $result = $connect->query("SELECT DISTINCT identification_code FROM user WHERE identification_code NOT LIKE 'F%' AND identification_code NOT LIKE 'A%'");
                        while ($row = $result->fetch_assoc()) {
                            // Check if the current value is selected
                            $selected = isset($_POST['identification_code']) && $_POST['identification_code'] === $row['identification_code'] ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['identification_code']) . "' $selected>" . htmlspecialchars($row['identification_code']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <select name="course_code" required>
                        <option value="">Select Course Code</option>
                        <?php
                        // Fetch unique course codes from the database
                        $result = $connect->query("SELECT DISTINCT course_code FROM course");
                        while ($row = $result->fetch_assoc()) {
                            // Check if the current value is selected
                            $selected = isset($_POST['course_code']) && $_POST['course_code'] === $row['course_code'] ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['course_code']) . "' $selected>" . htmlspecialchars($row['course_code']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="course_score">Course Score</label>
                    <td><input type="text" name="course_score" value="<?php echo isset($_POST['course_score']) ? htmlspecialchars($_POST['course_score']) : ''; ?>" required/>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>" />
                <input type="hidden" name="insert" value="yes" />
                <button type="submit" name="insert_button" value="Insert Score" >Insert Score</button>
            </form>
        </div>

        <div class="card">
            <h3>Student Score Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src=" ../scroll_up.png" alt="Scroll to top"></button>
            <?php
            $con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to the database
            if (!$con) {
                die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
            }

            // Prepare the statement
            $query = $connect->prepare("SELECT * FROM semester_gpa_to_course_code");
            $query->execute();
            $query->bind_result($id, $identification_code, $course_code, $course_score, $grade);


            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr><th>Identification Code</th><th>Course Code</th><th>Course Score</th><th>Grade</th><th colspan="2">Operations</th></tr>';

            // Extract the data row by row
            while ($query->fetch()) {
                echo '<tr>';
                echo "<td>" . htmlspecialchars($identification_code) . "</td>";
                echo "<td>" . htmlspecialchars($course_code) . "</td>";
                echo "<td>" . htmlspecialchars($course_score) . "</td>";
                echo "<td>" . htmlspecialchars($grade) . "</td>";
                echo "<td><a href='admin_edit.php?operation=edit&id=" . htmlspecialchars($id) . "&identification_code" . htmlspecialchars($identification_code) . "&course_code=" . htmlspecialchars($course_code) . "&course_score=" . htmlspecialchars($course_score) . "&grade=" . htmlspecialchars($grade) . "'>Edit</a></td>";
                echo "<td><a href='#' onclick='confirmDelete(\"" . htmlspecialchars($id) . "\", \"" . htmlspecialchars($_SESSION['csrf_token']) . "\")'>Delete</a></td>";
                echo '</tr>';
            }

            echo '</table>';

            // Close the database connection
            $con->close();
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
    <div id="confirmationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="confirmationMessage"></p>
            <button id="confirmationButton">Yes</button>
            <button onclick="hideModal()">Cancel</button>
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
        
    function confirmDelete(id, csrfToken) {
        const modal = document.getElementById("confirmationModal");
        const modalMessage = document.getElementById("confirmationMessage");
        const modalButton = document.getElementById("confirmationButton");

    // Set the message and show the modal
        modalMessage.innerText = "Are you sure you want to delete this?";
        modal.style.display = "flex";

    // Define what happens when the "OK" button is clicked
        modalButton.onclick = function () {
            window.location.href = `admin_delete.php?operation=delete&id=${id}&csrf_token=${csrfToken}`;
        };
    }

// This function is used to hide the modal if needed
    function hideModal() {
        const modal = document.getElementById("confirmationModal");
        modal.style.display = "none";
    }

// This part assumes you have a modal div in your HTML as described earlier
    </script>

</body>
</html>
