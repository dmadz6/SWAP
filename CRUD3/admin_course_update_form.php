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

$remaining_time = isset($_SESSION['last_activity']) 
    ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity']) 
    : SESSION_TIMEOUT;

$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) die('Could not connect: ' . mysqli_connect_errno());

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    header("Location: login.php");
    exit();
}

// Fetch all diplomas (no school restriction for admin)
$diploma_query = "SELECT diploma_code, diploma_name FROM diploma";
$diploma_result = $con->query($diploma_query);

// Validate course_code parameter
if (!isset($_GET["course_code"]) || empty($_GET["course_code"])) {
    header("Location: admin_course_create_form.php");
    exit();
}

$edit_coursecode = htmlspecialchars($_GET["course_code"]);
$full_name = $_SESSION['session_full_name'] ?? "";

// Fetch course details (no school restriction)
$course_query = "SELECT c.* FROM course c WHERE c.course_code = ?";
$course_stmt = $con->prepare($course_query);
$course_stmt->bind_param('s', $edit_coursecode);
$course_stmt->execute();
$course_result = $course_stmt->get_result();

if ($course_result->num_rows === 0) {
    header("Location: admin_course_create_form.php");
    exit();
}

$course_row = $course_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course - Admin</title>
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
            <a href="../admin_dashboard">Home</a>
            <a href="../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Course Management</h2>
            <p>Update course details.</p>
            <?php
            if (isset($_GET['error'])) {
                echo '<div id="message" style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            if (isset($_GET['success'])) {
                if ($_GET['success'] == 2) {
                    echo '<div id="message" class="success-message">Course updated successfully.</div>';
                } else if ($_GET['success'] == 1) {
                    echo '<div id="message" class="success-message">Course created successfully.</div>';
                }
            }
            ?>
        </div>

        <div class="card">
            <h3>Update Course Details</h3>
            <form method="POST" action="admin_course_update.php" onsubmit="return validateForm()">
                <input type="hidden" name="original_coursecode" value="<?php echo htmlspecialchars($course_row['course_code']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label class="label">Course Code</label>
                    <input type="text" name="upd_coursecode" pattern="[A-Z]{1}\d{2}" 
                           title="One uppercase letter followed by two digits" 
                           value="<?php echo htmlspecialchars($course_row['course_code']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="label">Course Name</label>
                    <input type="text" name="upd_coursename" maxlength="50"
                           value="<?php echo htmlspecialchars($course_row['course_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="label">Diploma</label>
                    <select name="upd_diplomacode" required>
                        <?php 
                        mysqli_data_seek($diploma_result, 0);
                        while ($diploma_row = $diploma_result->fetch_assoc()):
                            $selected = $diploma_row['diploma_code'] === $course_row['diploma_code'] ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($diploma_row['diploma_code']) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($diploma_row['diploma_code'] . ' - ' . $diploma_row['diploma_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label">Start Date</label>
                    <input type="date" name="upd_startdate" 
                        value="<?php echo $course_row['course_start_date'] ? htmlspecialchars($course_row['course_start_date']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="label">End Date</label>
                    <input type="date" name="upd_enddate" 
                        value="<?php echo $course_row['course_end_date'] ? htmlspecialchars($course_row['course_end_date']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="label">Status</label>
                    <select name="upd_status">
                        <option value="No Status" <?= $course_row['status'] === 'No Status' ? 'selected' : '' ?>>No Status</option>
                        <option value="To start" <?= $course_row['status'] === 'To start' ? 'selected' : '' ?>>To Start</option>
                        <option value="In-progress" <?= $course_row['status'] === 'In-progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Ended" <?= $course_row['status'] === 'Ended' ? 'selected' : '' ?>>Ended</option>
                    </select>
                </div>

                <button type="submit">Update Course</button>
            </form>

            <div class="delete-section" style="margin-top: 20px;">
                <button onclick="confirmDelete('<?php echo htmlspecialchars($course_row['course_code']); ?>')" 
                        class="delete-button">Delete Course</button>
            </div>
        </div>
    </div>

    <div id="logoutWarningModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="logoutWarningMessage"></p>
            <button id="logoutWarningButton">OK</button>
        </div>
    </div>

    <script>
        function validateForm() {
            const startDate = document.getElementsByName('upd_startdate')[0].value;
            const endDate = document.getElementsByName('upd_enddate')[0].value;
            
            if (new Date(endDate) <= new Date(startDate)) {
                alert('End date must be after start date');
                return false;
            }
            return true;
        }

        function confirmDelete(courseCode) {
            if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                window.location.href = 'admin_course_delete.php?course_code=' + courseCode + 
                                     '&csrf_token=<?php echo $_SESSION["csrf_token"]; ?>';
            }
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
    </script>
</body>
</html>