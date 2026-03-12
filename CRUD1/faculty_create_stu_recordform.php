<?php
session_start();
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

// Check if the user is logged in and has the correct role (faculty role: 1)
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    header("Location: ../login.php");
    exit();
}

// Fetch faculty's full name from the session for display purposes
// Get faculty's school code (NEW CODE)
$faculty_id = $_SESSION['session_identification_code'];
$school_stmt = $con->prepare("SELECT school_code FROM faculty WHERE faculty_identification_code = ?");
$school_stmt->bind_param('s', $faculty_id);
$school_stmt->execute();
$school_result = $school_stmt->get_result();
$school_row = $school_result->fetch_assoc();
$faculty_school_code = $school_row['school_code'] ?? '';
$school_stmt->close();

// Fetch diplomas only for faculty's school 
$diploma_query = "SELECT diploma_code, diploma_name 
                  FROM diploma 
                  WHERE school_code = ?";
$diploma_stmt = $con->prepare($diploma_query);
$diploma_stmt->bind_param('s', $faculty_school_code);
$diploma_stmt->execute();
$diploma_result = $diploma_stmt->get_result();


// Fetch classes only for faculty's school
$class_query = "
    SELECT c.class_code, co.course_name, d.diploma_code 
    FROM class c
    JOIN course co ON c.course_code = co.course_code
    JOIN diploma d ON co.diploma_code = d.diploma_code
    WHERE d.school_code = ?";
$class_stmt = $con->prepare($class_query);
$class_stmt->bind_param('s', $faculty_school_code);
$class_stmt->execute();
$class_result = $class_stmt->get_result();


// Organize class codes
$class_codes = [];
if ($class_result && mysqli_num_rows($class_result) > 0) {
    while ($row = mysqli_fetch_assoc($class_result)) {
        $class_codes[] = [
            'class_code' => $row['class_code'],
            'course_name' => $row['course_name'],
            'diploma_code' => $row['diploma_code'] // Ensure diploma_code is added here
        ];
    }
}


// Fetch faculty's name
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile Management</title>
    <link rel="stylesheet" href="/SWAP/styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="../faculty_dashboard.php">Home</a>
            <a href="../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>
    <div class="container">
        <div class="card">
            <h2>Student Profile Management</h2>
            <p>Add and view student profiles.</p>
            <?php
            // If ?success=1 is set in the URL, display a success message
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div id="message" class="success-message">Student record created successfully.</div>';
            }

            // If ?success=1 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="success-message">Student record updated successfully.</div>';
            }

            // If ?success=1 is set in the URL, display a delete message
            if (isset($_GET['success']) && $_GET['success'] == 3) {
                echo '<div id="message" class="success-message">Student record deleted successfully.</div>';
            }

            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>
        </div>

        <div class="card">
            <h3>Create Student Profile Form</h3>
            <form method="POST" action="faculty_create_stu_record.php">
                <div class="form-group">
                    <label class="label" for="student_name">Student Name</label>
                    <input type="text" name="student_name" placeholder="Enter Student Name" required>
                </div>
                <div class="form-group">
                    <label class="label" for="phone_number">Phone Number</label>
                    <input type="text" name="phone_number" placeholder="Enter Phone Number" maxlength="8" required>
                </div>
                <div class="form-group">
                    <label class="label" for="student_id_code">Student ID Code</label>
                    <p>Student Email Format: Student ID + @gmail.com</p>
                    <input type="text" name="student_id_code" placeholder="Enter Student ID Code" maxlength="4" required>
                </div>
                <div class="form-group">
                    <label class="label" for="diploma_code">Diploma Name</label>
                    <select name="diploma_code" required>
                        <option value="" disabled selected>Select a Diploma Name</option>
                        <?php
                        if ($diploma_result && mysqli_num_rows($diploma_result) > 0) {
                            while ($row = mysqli_fetch_assoc($diploma_result)) {
                                echo "<option value='" . htmlspecialchars($row['diploma_code']) . "'>" . htmlspecialchars($row['diploma_name']) . "</option>";
                            }
                        }                        
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="class_code_1">Class 1</label>
                    <select name="class_code_1">
                        <option value="" selected>No Class</option>
                        <?php
                        foreach ($class_codes as $class) {
                            echo "<option value='" . htmlspecialchars($class['class_code']) . "'>" . htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) . " (" . htmlspecialchars($class['diploma_code']) . ")" ."</option>";
                        }        
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="class_code_2">Class 2</label>
                    <select name="class_code_2">
                        <option value="" selected>No Class</option>
                        <?php
                        foreach ($class_codes as $class) {
                            echo "<option value='" . htmlspecialchars($class['class_code']) . "'>" . htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) . " (" . htmlspecialchars($class['diploma_code']) . ")" ."</option>";
                        }        
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="class_code_3">Class 3</label>
                    <select name="class_code_3">
                        <option value="" selected>No Class</option>
                        <?php
                        foreach ($class_codes as $class) {
                            echo "<option value='" . htmlspecialchars($class['class_code']) . "'>" . htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) . " (" . htmlspecialchars($class['diploma_code']) . ")" ."</option>";
                        }        
                        ?>
                    </select>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit">Submit</button>
            </form>
        </div>

        <div class="card">
            <h3>Student Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src="../scroll_up.png" alt="Scroll to top"></button>

            <?php
            // Modified student records query
            $stmt = $con->prepare("
                SELECT 
                    u.identification_code,
                    u.full_name,
                    u.phone_number,
                    s.class_code,
                    co.course_name,
                    d.diploma_code,
                    d.diploma_name
                FROM 
                    user u
                JOIN 
                    student s ON u.identification_code = s.identification_code
                JOIN
                    diploma d ON s.diploma_code = d.diploma_code
                LEFT JOIN
                    class c ON s.class_code = c.class_code
                LEFT JOIN
                    course co ON c.course_code = co.course_code
                WHERE d.school_code = ?
            ");
            $stmt->bind_param('s', $faculty_school_code);
            $stmt->execute();
            $result = $stmt->get_result();


            // Organize student data into an array for display
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $student_id = $row['identification_code'];
                
                // Check if the student is already in the array
                if (!isset($students[$student_id])) {
                    // Initialize the student's data in the array
                    $students[$student_id] = [
                        'identification_code' => $row['identification_code'],
                        'full_name' => $row['full_name'],
                        'phone_number' => $row['phone_number'],
                        'diploma_code' => $row['diploma_code'],
                        'diploma_name' => $row['diploma_name'],
                        'class_code_1' => null, // Placeholder for first class
                        'class_code_2' => null, // Placeholder for second class
                        'class_code_3' => null  // Placeholder for third class
                    ];
                }
            
                // Assign class codes and course names to available slots
                // Check if the first slot is empty and if the current row has a valid class code
                if (empty($students[$student_id]['class_code_1']) && !empty($row['class_code'])) {
                    $students[$student_id]['class_code_1'] = $row['class_code'] . ": " . $row['course_name'] . " (" . $row['diploma_code'] . ")";
                } 
                // Check if the second slot is empty and if the current row has a valid class code
                elseif (empty($students[$student_id]['class_code_2']) && !empty($row['class_code'])) {
                    $students[$student_id]['class_code_2'] = $row['class_code'] . ": " . $row['course_name'] . " (" . $row['diploma_code'] . ")";
                } 
                // Check if the third slot is empty and if the current row has a valid class code
                elseif (empty($students[$student_id]['class_code_3']) && !empty($row['class_code'])) {
                    $students[$student_id]['class_code_3'] = $row['class_code'] . ": " . $row['course_name'] . " (" . $row['diploma_code'] . ")";
                }
            }

            // Start HTML table to display student records
            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr>
                    <th>Student ID</th>        
                    <th>Name</th>             
                    <th>Phone Number</th>    
                    <th>Class Code 1</th>        
                    <th>Class Code 2</th>        
                    <th>Class Code 3</th>        
                    <th>Diploma Name</th>        
                    <th colspan="2">Operations</th> 
                </tr>';

            // Display each student record in the table
            foreach ($students as $student) {
                if (preg_match('/^S\d{3}$/', $student['identification_code'])) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($student['identification_code']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['full_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['phone_number']) . '</td>';
                    echo '<td>' . (!empty($student['class_code_1']) ? htmlspecialchars($student['class_code_1']) : 'No Class') . '</td>';
                    echo '<td>' . (!empty($student['class_code_2']) ? htmlspecialchars($student['class_code_2']) : 'No Class') . '</td>';
                    echo '<td>' . (!empty($student['class_code_3']) ? htmlspecialchars($student['class_code_3']) : 'No Class') . '</td>';
                    echo '<td>' . htmlspecialchars($student['diploma_name']) . '</td>';
                    echo '<td> <a href="faculty_update_stu_recordform.php?student_id=' . htmlspecialchars($student['identification_code']) . '">Edit</a> </td>';
                    echo '</tr>';
                }
            }

            // Close the HTML table
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
