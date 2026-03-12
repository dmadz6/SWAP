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


// Connect to the database 'testing'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$connect) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name']:"";


// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
        exit();
    }
}

// View GPA functionality for 'testing' database
if (isset($_POST["view_button"])) {
    $csrf_token = $_POST["csrf_token"] ?? '';
    check_csrf_token($csrf_token); // Validate CSRF token

    $identification_code = $_POST["identification_code"];

    // Input validation: Check if identification_code is selected
    if (empty($identification_code)) {
        header("Location: admin_gpa.php?error=" . urlencode("Please select an Identification Code to view the GPA!"));
        exit();
    } else {
        // Query to calculate GPA based on the identification_code
        $query = $connect->prepare("SELECT identification_code, AVG(course_score) AS gpa FROM semester_gpa_to_course_code WHERE identification_code = ? GROUP BY identification_code");
        $query->bind_param('s', $identification_code);
        $query->execute();
        $query->bind_result($identification_code_result, $gpa);
        $query->fetch();
        $query->close();

        // Display the GPA in a JavaScript alert pop-up
        if (!empty($gpa)) {
            // Check for existing record and update or insert accordingly
            $check_query = $connect->prepare("SELECT COUNT(*) FROM student_score WHERE identification_code = ?");
            $check_query->bind_param('s', $identification_code);
            $check_query->execute();
            $check_query->bind_result($exists);
            $check_query->fetch();
            $check_query->close();

            if ($exists > 0) {
                // Update existing record
                $update_query = $connect->prepare("UPDATE student_score SET semester_gpa = ? WHERE identification_code = ?");
                $update_query->bind_param('ds', $gpa, $identification_code);
                if ($update_query->execute()) {
                        // Regenerate CSRF token after form submission
                    unset($_SESSION['csrf_token']);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header("Location: admin_gpa.php?success=2");
                    exit();
                } else {
                    header("Location: admin_gpa.php?error=" . urlencode("Error updating GPA for Identification Code"));
                    exit();
                }
                $update_query->close();
            } else {
                // Insert new record
                $insert_query = $connect->prepare("INSERT INTO student_score (identification_code, semester_gpa) VALUES (?, ?)");
                $insert_query->bind_param('sd', $identification_code, $gpa);
                if ($insert_query->execute()) {
                        // Regenerate CSRF token after form submission
                    unset($_SESSION['csrf_token']);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header("Location: admin_gpa.php?success=2");
                    exit();
                } else {
                    header("Location: admin_gpa.php?error=" . urlencode("Error inserting GPA for Identification Code"));
                    exit();
                }
                $insert_query->close();
            }
        } else {
            header("Location: admin_gpa.php?error=" . urlencode("No GPA data found"));
            exit();
        }
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
            <a href=" ../admin_dashboard.php">Home</a>
            <a href=" ../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Student GPA</h2>
            <p>View student's Grade Point Average. <a href="admin_score.php">VIEW STUDENT SCORES</a></p>
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
            <form method="post" action="admin_gpa.php">
                <div class="form-group">
                    <label class="label" for="identification_code">Student Identification Code</label>
                    <select name="identification_code" required>
                        <option value="">Select Identification Code</option>
                        <?php
                        // Fetch unique identification codes from the database
                        $result = $connect->query("SELECT DISTINCT identification_code FROM semester_gpa_to_course_code");
                        while ($row = $result->fetch_assoc()) {
                            // Check if the current value is selected
                            $selected = isset($_POST['identification_code']) && $_POST['identification_code'] === $row['identification_code'] ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['identification_code']) . "' $selected>" . htmlspecialchars($row['identification_code']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" name="view_button" value="View GPA">View GPA</button>
            </form>
        </div>

        <div class="card">
            <h3>Student GPA Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src=" ../scroll_up.png" alt="Scroll to top"></button>
            <?php
            $con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to the database
            if (!$con) {
                die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
            }

            // Prepare the statement
            $query = $connect->prepare("SELECT identification_code, semester_gpa FROM student_score");
            $query->execute();
            $query->bind_result($identification_code, $gpa);

            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr><th>Identification Code</th><th>GPA</th><th colspan="1">Operations</th></tr>';

            // Extract the data row by row
            while ($query->fetch()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($identification_code) . "</td>";
                echo "<td>" . htmlspecialchars(number_format($gpa, 2)) . "</td>";
                echo "<td><a href='#' onclick='confirmDelete(\"" . htmlspecialchars($identification_code) . "\", \"" . htmlspecialchars($_SESSION['csrf_token']) . "\")'>Delete</a></td>";
                echo "</tr>";
            }
            echo "</table>";

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

    function confirmDelete(identification_code, csrfToken) {
        const modal = document.getElementById("confirmationModal");
        const modalMessage = document.getElementById("confirmationMessage");
        const modalButton = document.getElementById("confirmationButton");

    // Set the message and show the modal
        modalMessage.innerText = "Are you sure you want to delete this?";
        modal.style.display = "flex";

    // Define what happens when the "OK" button is clicked
        modalButton.onclick = function () {
            window.location.href = `admin_delete_gpa.php?operation=delete&identification_code=${identification_code}&csrf_token=${csrfToken}`;
        };
    }

// This function is used to hide the modal if needed
    function hideModal() {
        const modal = document.getElementById("confirmationModal");
        modal.style.display = "none";
    }

    </script>

</body>
</html>
