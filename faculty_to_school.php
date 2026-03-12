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

if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    header("Location: ../login.php");
    exit();
}

$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$faculty_query = "SELECT identification_code, full_name FROM user WHERE role_id = 2";
$faculty_result = mysqli_query($con, $faculty_query);

$school_query = "SELECT school_code, school_name FROM school";
$school_result = mysqli_query($con, $school_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: faculty_to_school.php?error=" . urlencode("Invalid CSRF token"));
        exit();
    }

    $faculty_id = htmlspecialchars($_POST['faculty_identification_code'] ?? '');
    $school_code = htmlspecialchars($_POST['school_code'] ?? '');

    if (empty($faculty_id) || empty($school_code)) {
        header("Location: faculty_to_school.php?error=" . urlencode("All fields are required"));
        exit();
    }

    // Check for existing faculty assignment
    $check_stmt = $con->prepare("SELECT faculty_identification_code FROM faculty WHERE faculty_identification_code = ?");
    $check_stmt->bind_param('s', $faculty_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        header("Location: faculty_to_school.php?error=" . urlencode("Faculty already assigned to a school"));
        exit();
    }

    $insert_query = "INSERT INTO faculty (faculty_identification_code, school_code) VALUES (?, ?)";
    $stmt = $con->prepare($insert_query);
    $stmt->bind_param('ss', $faculty_id, $school_code);

    if ($stmt->execute()) {
        header("Location: faculty_to_school.php?success=1");
        exit();
    } else {
        header("Location: faculty_to_school.php?error=" . urlencode("Error: " . $stmt->error));
        exit();
    }
    
    $stmt->close();
    $check_stmt->close();
}

$full_name = $_SESSION['session_full_name'] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Faculty to School</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="navbar">
    <div class="navbar-brand">
        <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
        <h1>XYZ Polytechnic Management</h1>
    </div>
    <nav>
        <a href="admin_dashboard.php">Home</a>
        <a href="logout.php">Logout</a>
        <a><?php echo htmlspecialchars($full_name); ?></a>
    </nav>
</div>

<div class="container">
    <div class="card">
        <h2>Assign Faculty to School</h2>
        <?php
        if (isset($_GET['success']) && $_GET['success'] == 1) {
            echo '<div id="message" class="success-message">Faculty assigned successfully.</div>';
        }
        if (isset($_GET['error'])) {
            echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="faculty_identification_code">Select Faculty</label>
                <select name="faculty_identification_code" required>
                    <option value="" disabled selected>Select a Faculty</option>
                    <?php
                    if ($faculty_result && mysqli_num_rows($faculty_result) > 0) {
                        while ($row = mysqli_fetch_assoc($faculty_result)) {
                            echo "<option value='" . htmlspecialchars($row['identification_code']) . "'>" . htmlspecialchars($row['full_name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="school_code">Select School</label>
                <select name="school_code" required>
                    <option value="" disabled selected>Select a School</option>
                    <?php
                    if ($school_result && mysqli_num_rows($school_result) > 0) {
                        while ($row = mysqli_fetch_assoc($school_result)) {
                            echo "<option value='" . htmlspecialchars($row['school_code']) . "'>" . htmlspecialchars($row['school_name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button type="submit">Assign Faculty</button>
        </form>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2025 XYZ Polytechnic Student Management System. All rights reserved.</p>
</footer>

<script>
    setTimeout(function () {
        const messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.style.display = 'none';
        }
    }, 10000);
</script>
</body>
</html>