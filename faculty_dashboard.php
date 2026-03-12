<?php
session_start();
unset($_SESSION['csrf_token']);
define('SESSION_TIMEOUT', 600); // 600 seconds = 10 minutes
define('WARNING_TIME', 60); // 60 seconds (1 minute before session ends)
define('FINAL_WARNING_TIME', 3); // Final warning 3 seconds before logout
session_regenerate_id(true);
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
$con = mysqli_connect("localhost","root","","xyz polytechnic"); //connect to database
if (!$con){
	die('Could not connect: ' . mysqli_connect_errno()); //return error is connect fail
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    // Redirect to login page if the user is not logged in or not a faculty
    header("Location: login.php");
    exit();
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";
$identification_code = isset($_SESSION['session_identification_code']) ? $_SESSION['session_identification_code'] : "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css"> <!-- Link to the CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
    <title>Faculty Dashboard</title>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <div class="logout-button">
            <a href="logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </div>
    </nav>

    

    <!-- Faculty Dashboard Content -->
    <div class="card-grid-container">
    
        <?php
            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message faculty-dashboard">' . htmlspecialchars($_GET['error']) . '</div>';
            }
        ?>
    <!-- Welcome Message -->
        <div class="welcome-message">
            <h2>Welcome <?php echo htmlspecialchars($full_name); ?>, <?php echo htmlspecialchars($identification_code); ?></h2>
        </div>
    
        <!-- User Management Widget -->
        <a href="CRUD1/faculty_create_stu_recordform.php" class="widget-card">
            <h2>Student Management</h2>
            <p>Manage students and their details here.</p>
        </a>

        <!-- Course Management Widget -->
        <a href="CRUD3/faculty_course_create_form.php" class="widget-card">
            <h2>Course Management</h2>
            <p>Manage courses and their details here.</p>
        </a>

        <!-- Class Management Widget -->
        <a href="CRUD2/faculty_class_create_form.php" class="widget-card">
            <h2>Class Management</h2>
            <p>Manage class schedules and related info here.</p>
        </a>

        <!-- Grades Management Widget -->
        <a href="CRUD4/faculty_score.php" class="widget-card">
            <h2>Grades Management</h2>
            <p>Manage and view student grades here.</p>
        </a>
        <a href="faculty_profile.php" class="widget-card">
            <h2>Profile</h2>
            <p>View your details here.</p>
        </a>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Management. All Rights Reserved.</p>
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
    </script>
</body>
</html>
