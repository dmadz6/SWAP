<?php
session_start();
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

// Establish a connection to the database
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");

// Check for database connection errors
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and has the correct role (Admin role: 1)
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 3) {
    header("Location: ../login.php");
    exit();
}

// Fetch admin's full name from the session for display purposes
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";
$student_id_code = $_SESSION['session_identification_code'] ?? "";

$query = "
    SELECT 
        u.full_name, u.phone_number, u.email, u.identification_code, 
        d.diploma_name, s.diploma_code, sch.school_name,
        c.class_code, c.class_type, co.course_name, co.status 
    FROM user u
    JOIN student s ON u.identification_code = s.identification_code
    JOIN diploma d ON s.diploma_code = d.diploma_code
    JOIN school sch ON d.school_code = sch.school_code
    LEFT JOIN class c ON s.class_code = c.class_code
    LEFT JOIN course co ON c.course_code = co.course_code
    WHERE u.identification_code = ?
";

$stmt = $con->prepare($query);
$stmt->bind_param('s', $student_id_code);
$stmt->execute();
$result = $stmt->get_result();

$student_data = [];
while ($row = $result->fetch_assoc()) {
    // Handle NULL values
    $row['class_code'] = $row['class_code'] ?? 'No Class';
    $row['class_type'] = $row['class_type'] ?? 'N/A';
    $row['course_name'] = $row['course_name'] ?? 'No Course Assigned';
    $row['status'] = $row['status'] ?? 'N/A';
    
    $student_data[] = $row;
}
$stmt->close();

if (empty($student_data)) {
    $error_message = "No student records found.";
}

// Close the database connection
$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
            <a href="stu_dashboard.php">Home</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <img src="user_profile.png" alt="Profile Picture" class="profile-picture" style="display: block; margin: 0 auto; border-radius: 50%; width: 150px; height: 150px;">
            <h2 style="text-align: center;">My Profile</h2>
            <?php if (!empty($student_data)): ?>
                <table class="profile-table" border="1" bgcolor="white" align="center">
                    <tr>
                        <th>Full Name</th>
                        <td><?php echo htmlspecialchars($student_data[0]['full_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone Number</th>
                        <td><?php echo htmlspecialchars($student_data[0]['phone_number']); ?></td>
                    </tr>
                    <tr>
                        <th>Identification Code</th>
                        <td><?php echo htmlspecialchars($student_data[0]['identification_code']); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($student_data[0]['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Diploma</th>
                        <td><?php echo htmlspecialchars($student_data[0]['diploma_name']); ?> (<?php echo htmlspecialchars($student_data[0]['diploma_code']); ?>)</td>
                    </tr>
                    <tr>
                        <th>School Name</th>
                        <td><?php echo htmlspecialchars($student_data[0]['school_name']); ?></td>
                    </tr>
                </table>
                <h3>Class and Course Details</h3>
                <table border="1" bgcolor="white" align="center">
                    <tr>
                        <th>Class Code</th>
                        <th>Class Type</th>
                        <th>Course Name</th>
                        <th>Course Status</th>
                    </tr>
                    <?php foreach ($student_data as $class): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                            <td><?php echo htmlspecialchars($class['class_type']); ?></td>
                            <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['status'] ? $class['status'] : 'No Status'); ?></td>
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
            window.location.href = "../logout.php";
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
