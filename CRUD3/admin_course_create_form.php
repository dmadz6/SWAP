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

$remaining_time = (isset($_SESSION['last_activity'])) 
    ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity']) 
    : SESSION_TIMEOUT;

$con = mysqli_connect("localhost","root","","xyz polytechnic");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Check admin role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    header("Location: ../login.php");
    exit();
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Get all diplomas (no school restriction for admin)
$diploma_query = "SELECT diploma_code, diploma_name FROM diploma";
$diplomas = $con->query($diploma_query);

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
    <title>Admin Course Management</title>
    <link rel="stylesheet" href="../styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>Admin Course Management</h1>
        </div>
        <nav>
            <a href="../admin_dashboard.php">Home</a>
            <a href="../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Course Management</h2>
            <p>Create and manage courses across all schools.</p>
            <?php
                if (isset($_GET['error'])) {
                    echo '<div id="message" style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</div>';
                }
                if (isset($_GET['success'])) {
                    if ($_GET['success'] == 1) {
                        echo '<div id="message" class="success-message">Course created successfully.</div>';
                    } else if ($_GET['success'] == 2) {
                        echo '<div id="message" class="success-message">Course updated successfully.</div>';
                    } else if ($_GET['success'] == 3) {
                        echo '<div id="message" class="success-message">Course deleted successfully.</div>';
                    }
                }
            ?>
        </div>

        <div class="card">
            <h3>Course Details</h3>
            <form method="POST" action="admin_course_create.php" onsubmit="return validateDates()">
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
                    <select name="diploma_code" required>
                        <?php while($row = $diplomas->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['diploma_code']) ?>">
                                <?= htmlspecialchars($row['diploma_code'] . ' - ' . $row['diploma_name']) ?>
                            </option>
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
            <button id="scrollToTop" class="button" onclick="scroll_to_top()">
                <img src="../scroll_up.png" alt="Scroll to top">
            </button>
            <?php
            // Fetch all courses (no school restriction)
            $course_query = "SELECT c.*, d.diploma_name, s.school_name 
                           FROM course c 
                           JOIN diploma d ON c.diploma_code = d.diploma_code
                           JOIN school s ON d.school_code = s.school_code 
                           ORDER BY c.course_code";
            $course_result = $con->query($course_query);

            if ($course_result->num_rows > 0) {
                echo '<table border="1" bgcolor="white" align="center">';
                echo '<tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Diploma</th>
                        <th>School</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th colspan="2">Operations</th>
                    </tr>';

                while ($course_row = $course_result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($course_row['course_code']) . '</td>';
                    echo '<td>' . htmlspecialchars($course_row['course_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($course_row['diploma_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($course_row['school_name']) . '</td>';
                    echo '<td>' . ($course_row['course_start_date'] ? htmlspecialchars(date('Y-m-d', strtotime($course_row['course_start_date']))) : 'No Start Date') . '</td>';
                    echo '<td>' . ($course_row['course_end_date'] ? htmlspecialchars(date('Y-m-d', strtotime($course_row['course_end_date']))) : 'No End Date') . '</td>';
                    echo '<td>' . htmlspecialchars(($course_row['status'] ? $course_row['status'] : 'No Status')) . '</td>';
                    echo '<td> <a href="admin_course_update_form.php?course_code=' . htmlspecialchars($course_row['course_code']) . '">Edit</a> </td>';
                    echo '<td> <a href="#" onclick="confirmDelete(\'' . htmlspecialchars($course_row['course_code']) . '\')">Delete</a> </td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="text-center">No courses found.</p>';
            }
            ?>
        </div>
    </div>
</body>
<footer class="footer">
    <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
</footer>

<div id="confirmationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <p id="confirmationMessage"></p>
        <button id="confirmationButton">Yes</button>
        <button onclick="hideModal()">Cancel</button>
    </div>
</div>

<script>
    function validateDates() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (new Date(endDate) <= new Date(startDate)) {
            alert('End date must be after start date');
            return false;
        }
        return true;
    }

    function confirmDelete(courseCode, csrfToken) {
        const modal = document.getElementById("confirmationModal");
        const modalMessage = document.getElementById("confirmationMessage");
        const modalButton = document.getElementById("confirmationButton");

    // Set the message and show the modal
        modalMessage.innerText = "Are you sure you want to delete this?";
        modal.style.display = "flex";

    // Define what happens when the "OK" button is clicked
        modalButton.onclick = function () {
            window.location.href = 'admin_course_delete.php?course_code=' + courseCode + '&csrf_token=<?= $_SESSION['csrf_token'] ?>';
        };
    }

// This function is used to hide the modal if needed
    function hideModal() {
        const modal = document.getElementById("confirmationModal");
        modal.style.display = "none";
    }



    const remainingTime = <?php echo $remaining_time; ?>;
    const warningTime = <?php echo WARNING_TIME; ?>;
    const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>;

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

    if (remainingTime > warningTime) {
        setTimeout(() => {
            showLogoutWarning(
                "You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in."
            );
        }, (remainingTime - warningTime) * 1000);
    }

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

    setTimeout(() => {
        window.location.href = "logout.php";
    }, remainingTime * 1000);

    function scroll_to_top() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
</script>
</html>