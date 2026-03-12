<?php
session_start();
session_regenerate_id(true);
define('SESSION_TIMEOUT', 600);
define('WARNING_TIME', 60);
define('FINAL_WARNING_TIME', 3);

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
$remaining_time = isset($_SESSION['last_activity']) ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity']) : SESSION_TIMEOUT;

$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET["class_code"]) || empty($_GET["class_code"])) {
    header("Location: admin_class_create_form.php");
    exit();
}

$full_name = $_SESSION['session_full_name'] ?? "";

// Fetch course codes
$course_query = "SELECT course_code FROM course";
$course_codes_result = mysqli_query($con, $course_query);

// Match faculty ID in 'faculty' with user ID in 'user' to get faculty names
// Filter only users who are faculty (role_id = 2)
$faculty_query = "SELECT f.faculty_identification_code, u.full_name 
                 FROM faculty f 
                 JOIN user u ON f.faculty_identification_code = u.identification_code 
                 WHERE u.role_id = 2";
$faculty_result = mysqli_query($con, $faculty_query);

// Gets the class_code from the URL query string
// SQL query to select all columns from the class table where class_code matches the given value
// $class_row holds all the details of the selected class in the array
$edit_classcode = htmlspecialchars($_GET["class_code"]);
$stmt = $con->prepare("SELECT * FROM class WHERE class_code = ?");
$stmt->bind_param('s', $edit_classcode);
$stmt->execute();
$result = $stmt->get_result();

// Error message for non-existant classes
if ($result->num_rows === 0) {
    header("Location: admin_class_create_form.php?error=" . urlencode("Class code \"$edit_classcode\" does not exist."));
    exit();
}

$class_row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Class</title>
    <link rel="stylesheet" href="../styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1> XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="../admin_dashboard.php">Home</a>
            <a href="../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Class Management</h2>
            <p>Update class records.</p>
            <?php
            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            // If ?success=2 is set in the URL, display an update message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="message"">Class updated successfully.</div>';
            }
            ?>
        </div>

        <div class="card">
            <h3>Update Class Details</h3>
            <form method="POST" action="admin_class_update.php">
                <!-- Retrieves the class code from the from the database, from the class query -->
                <!-- Pre-fills the form and tracks which class is being updated -->
                <input type="hidden" name="original_classcode" value="<?php echo htmlspecialchars($class_row['class_code']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label class="label" for="class_code">Class Code</label>
                    <input type="text" name="upd_classcode" value="<?php echo htmlspecialchars($class_row['class_code']); ?>" placeholder="Enter class code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <select name="upd_coursecode" required>
                        <option value="" disabled>Select a Course Code</option>
                        <?php
                        // Loops through each course record retrieved from the database
                        // Checks if the current course matches the selected course for this class
                        // If the course matches the existing class's course, it is marked as 'selected'
                        while ($course_row = mysqli_fetch_assoc($course_codes_result)) {
                            $selected = ($course_row['course_code'] === $class_row['course_code']) ? 'selected' : '';
                            echo "<option value='{$course_row['course_code']}' $selected>{$course_row['course_code']}</option>";
                            // Sets the value of the option to the course code that was selected to be edited
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="class_type">Class Type</label>
                    <select name="upd_classtype" required>
                        <option value="" disabled>Select Class Type</option>
                        <!-- Checks if the current class type ($class_row['class_type']) is already set to Semester/Term -->
                        <!-- If true, it adds selected, making Semester/Term the default selected option, else nothing is done -->
                        <option value="Semester" <?php echo ($class_row['class_type'] === 'Semester') ? 'selected' : ''; ?>>Semester</option>
                        <option value="Term" <?php echo ($class_row['class_type'] === 'Term') ? 'selected' : ''; ?>>Term</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="faculty_identification_code">Assigned Faculty</label>
                    <select name="upd_facultycode" required>
                        <option value="" disabled>Select a Faculty</option>
                        <?php
                        // Loops through each row in $faculty_result, which contains the faculty members retrieved from the database.
                        // Checks if the faculty id code in the loop matches the faculty assigned to the current class
                        // Generate an option for the dropdown, marking it as selected if it matches the assigned faculty
                        while ($faculty_row = mysqli_fetch_assoc($faculty_result)) {
                            $selected = ($faculty_row['faculty_identification_code'] === $class_row['faculty_identification_code']) ? 'selected' : '';
                            echo "<option value='{$faculty_row['faculty_identification_code']}' $selected>{$faculty_row['full_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit">Update Class</button>
            </form>
        </div>
    </div>

    <div id="logoutWarningModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="logoutWarningMessage"></p>
            <button id="logoutWarningButton">OK</button>
        </div>
    </div>

    <script>
        const remainingTime = <?php echo $remaining_time; ?>;
        const warningTime = <?php echo WARNING_TIME; ?>;
        const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>;

        function showLogoutWarning(message, redirectUrl = null) {
            const modal = document.getElementById("logoutWarningModal");
            const modalMessage = document.getElementById("logoutWarningMessage");
            const modalButton = document.getElementById("logoutWarningButton");

            modalMessage.textContent = message;
            modal.style.display = "flex";

            modalButton.onclick = () => {
                modal.style.display = "none";
                if (redirectUrl) window.location.href = redirectUrl;
            };
        }

        if (remainingTime > warningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in.");
            }, (remainingTime - warningTime) * 1000);
        }

        if (remainingTime > finalWarningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out due to inactivity.", "../logout.php");
            }, (remainingTime - finalWarningTime) * 1000);
        }

        setTimeout(() => {
            const messageElement = document.getElementById('message');
            if (messageElement) messageElement.style.display = 'none';
        }, 10000);

        setTimeout(() => {
            window.location.href = "../logout.php";
        }, remainingTime * 1000);
    </script>
</body>
</html>