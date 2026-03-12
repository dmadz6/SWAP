<?php
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
        if ($inactive_time > SESSION_TIMEOUT) {
            header("Location: logout.php"); // Redirect to logout
            exit();
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

// Establish a connection to the database
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");

// Check for database connection errors
if (!$con) {
    die('Could not connect: ' . mysqli_connect_error());
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and has the correct role (Faculty role: 2)
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    header("Location: login.php");
    exit();
}

// Fetch faculty ID from the session
$faculty_id_code = $_SESSION['session_identification_code'] ?? "";

if (empty($faculty_id_code)) {
    header("Location: login.php?error=" . urlencode("Session expired. Please log in again."));
    exit();
}

// Query to fetch faculty details and assigned classes, courses, and diplomas
$query = "
    SELECT 
        u.full_name, 
        u.phone_number, 
        u.email, 
        u.identification_code, 
        c.class_code, 
        c.class_type, 
        co.course_code, 
        co.course_name, 
        co.status, 
        d.diploma_code, 
        d.diploma_name,
        s.school_name,
        s.school_code
    FROM user u
    JOIN class c ON u.identification_code = c.faculty_identification_code
    JOIN course co ON c.course_code = co.course_code
    JOIN diploma d ON co.diploma_code = d.diploma_code
    JOIN school s ON d.school_code = s.school_code
    WHERE u.identification_code = ?
";
$stmt = $con->prepare($query);
$stmt->bind_param('s', $faculty_id_code);
$stmt->execute();
$result = $stmt->get_result();

$faculty_data = [];
while ($row = $result->fetch_assoc()) {
    $faculty_data[] = $row;
}
$stmt->close();

if (empty($faculty_data)) {
    $error_message = "No faculty records found.";
}

// Close the database connection
$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Profile</title>
    <link rel="stylesheet" href="/SWAP/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <div class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="faculty_dashboard.php">Home</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <img src="user_profile.png" alt="Profile Picture" class="profile-picture" style="display: block; margin: 0 auto; border-radius: 50%; width: 150px; height: 150px;">
            <h2 style="text-align: center;">My Profile</h2>
            <?php if (!empty($faculty_data)): ?>
                <table class="profile-table" border="1" bgcolor="white" align="center">
                    <tr>
                        <th>Full Name</th>
                        <td><?php echo htmlspecialchars($faculty_data[0]['full_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone Number</th>
                        <td><?php echo htmlspecialchars($faculty_data[0]['phone_number']); ?></td>
                    </tr>
                    <tr>
                        <th>Identification Code</th>
                        <td><?php echo htmlspecialchars($faculty_data[0]['identification_code']); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($faculty_data[0]['email']); ?></td>
                    </tr>
                    <tr>
                        <th>School</th> <!-- Added School Name Row -->
                        <td><?php echo htmlspecialchars($faculty_data[0]['school_name']) . " (" . htmlspecialchars($faculty_data[0]['school_code']) . ")"; ?></td>
                    </tr>
                </table>
                <h3>Class and Courses</h3>
                <table border="1" bgcolor="white" align="center">
                    <tr>
                        <th>Class Code</th>
                        <th>Class Type</th>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Course Status</th>
                        <th>Diploma Name</th>
                        <th>Diploma Code</th>
                    </tr>
                    <?php foreach ($faculty_data as $class): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                            <td><?php echo htmlspecialchars($class['class_type']); ?></td>
                            <td><?php echo htmlspecialchars($class['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['status'] ? $class['status'] : 'No Status'); ?></td>
                            <td><?php echo htmlspecialchars($class['diploma_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['diploma_code']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: red;"><?php echo $error_message ?? "No data available."; ?></p>
            <?php endif; ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="password_reset_request.php">
                    <button type="button" class="btn">Change Password</button>
                </a>
            </div>
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
