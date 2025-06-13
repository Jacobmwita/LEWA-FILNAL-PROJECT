<?php

if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// BASE_URL 
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];


define('BASE_URL', "{$protocol}://{$host}/lewa");

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/user_login.php');
    exit();
}

$allowed_admin_types = [
    'workshop_manager',
    'admin',
    'administrator',
    'manager'
];
if (!isset($_SESSION['user_type']) || !in_array(strtolower($_SESSION['user_type']), array_map('strtolower', $allowed_admin_types))) {
    error_log("Unauthorized access attempt to job card page by user: " . ($_SESSION['username'] ?? 'unknown') . " (User ID: " . ($_SESSION['user_id'] ?? 'unknown') . ")");
    header('Location: ' . BASE_URL . '/user_login.php?error=no_access');
    exit();
}


require_once __DIR__ . '/../db_connect.php'; 

if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));


    die("<h1>Service Unavailable</h1><p>We are experiencing technical difficulties. Please try again later.</p>");
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * FETCHE ALL MECHANICS
 * @param 
 * @return array 
 */
function getMechanics(mysqli $conn): array {
    $mechanics = [];
    
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE LOWER(user_type) = 'mechanic'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $mechanics[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare getMechanics statement: " . $conn->error);
    }
    return $mechanics;
}

$mechanics_lookup = [];
$all_mechanics = getMechanics($conn);
foreach ($all_mechanics as $mechanic) {
    $mechanics_lookup[$mechanic['user_id']] = $mechanic['full_name'];
}

//REQUESTS POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_job_status') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    // CSRF TOKENS
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Invalid CSRF token.';
        echo json_encode($response);
        exit();
    }

    try {
        
        if (empty($_POST['job_id']) || empty($_POST['status'])) {
            throw new Exception("Job ID and status are required.");
        }

        $job_id = (int)$_POST['job_id']; 
        $status = $_POST['status'];
        $mechanic_id = $_POST['mechanic_id'] ?? null;

        
        $allowed_statuses = ['pending', 'in_progress', 'completed', 'on_hold', 'cancelled'];
        if (!in_array($status, $allowed_statuses)) {
            throw new Exception("Invalid status provided.");
        }

        $update_query = "UPDATE job_cards SET status = ?";
        $types = "s";
        $params = [$status];

        if ($status === 'completed') {
            $update_query .= ", completed_at = NOW()";
        } else {
            $update_query .= ", completed_at = NULL";
        }

        if ($mechanic_id !== null && $mechanic_id !== '') {
            $update_query .= ", assigned_to_mechanic_id = ?";
            $types .= "i";
            $params[] = (int)$mechanic_id; 
        } else {
            $update_query .= ", assigned_to_mechanic_id = NULL";
        }

        $update_query .= " WHERE job_card_id = ?";
        $types .= "i";
        $params[] = $job_id;

        $stmt = $conn->prepare($update_query);

        if (!$stmt) {
            throw new Exception("Failed to prepare update statement: " . $conn->error);
        }

        
        $bind_names = [$types];
        for ($i = 0; $i < count($params); $i++) {
            $bind_name = 'param' . $i;
            $$bind_name = &$params[$i]; 
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Job status updated successfully.";
        } else {
            throw new Exception("Failed to update job status: " . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("Job status update failed: " . $e->getMessage()); 
    }

    echo json_encode($response);
    $conn->close();
    exit();
}



$all_job_cards = [];

$sql = "SELECT jc.*, v.registration_number,
                created_user.full_name as created_by_name,
                driver_user.full_name AS driver_full_name
        FROM job_cards jc
        LEFT JOIN vehicles v ON jc.vehicle_id = v.vehicle_id
        LEFT JOIN users created_user ON jc.created_by_name = created_user.user_id 
        LEFT JOIN users driver_user ON jc.driver_id = driver_user.user_id
        ORDER BY jc.created_at DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_job_cards[] = $row;
    }
    $result->free(); 
} else {
    error_log("Failed to fetch job cards: " . $conn->error);
    
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lewa Workshop - All Job Cards</title>
    <style>
    
        :root {
            --primary: #3498db;
            --secondary: #2980b9;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background-color: var(--dark);
            color: white;
            padding: 20px;
        }

        .sidebar h2 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 30px;
        }

        .sidebar nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar nav ul li {
            margin-bottom: 10px;
        }

        .sidebar nav ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .sidebar nav ul li a:hover,
        .sidebar nav ul li a.active {
            background-color: var(--secondary);
        }

        .main-content {
            padding: 20px;
        }

        h1 {
            color: var(--dark);
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--light);
            color: var(--dark);
            font-weight: 600;
        }

        td {
            color: #555;
        }

        .status-pending {
            color: var(--warning);
            font-weight: bold;
        }

        .status-in-progress {
            color: var(--primary);
            font-weight: bold;
        }

        .status-completed {
            color: var(--success);
            font-weight: bold;
        }
        .status-on-hold {
            color: var(--warning); 
            font-weight: bold;
        }
        .status-cancelled {
            color: var(--danger);
            font-weight: bold;
        }
        .action-links button {
            margin-right: 10px;
            color: white; 
            background-color: var(--primary); 
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .action-links button:hover {
            background-color: var(--secondary);
        }

    
        .modal {
            display: none;
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; 
            padding: 30px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
            animation-name: animatetop;
            animation-duration: 0.4s
        }

    
        @keyframes animatetop {
            from {top: -300px; opacity: 0}
            to {top: 0; opacity: 1}
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-content h3 {
            margin-top: 0;
            color: var(--dark);
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .modal-content .form-group {
            margin-bottom: 15px;
        }

        .modal-content label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark);
        }

        .modal-content input[type="text"],
        .modal-content input[type="number"],
        .modal-content select {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
        }

        .modal-content button[type="submit"] {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 1rem;
            margin-top: 20px;
        }

        .modal-content button[type="submit"]:hover {
            background-color: var(--secondary);
        }

        #updateJobStatusFormModal {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Lewa Workshop</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>

            <nav>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>/dashboards/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/dashboards/job_card.php" class="active"><i class="fas fa-clipboard-list"></i> View/Edit Job Cards</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/dashboards/reports.php"><i class="fas fa-chart-line"></i> Generate Reports</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/dashboards/manage_users.php"><i class="fas fa-users-cog"></i> Manage User Roles</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/dashboards/inventory.php"><i class="fas fa-warehouse"></i> Monitor Inventory</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/dashboards/add_vehicle.php"><i class="fas fa-car-alt"></i> Vehicles</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <h1>All Job Cards</h1>

            <table>
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Vehicle Reg</th>
                        <th>Driver Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Assigned Mechanic</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Completed At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($all_job_cards)): ?>
                        <?php foreach ($all_job_cards as $job_card):
                            $mechanic_name = 'N/A';
                            if (!empty($job_card['assigned_to_mechanic_id']) && isset($mechanics_lookup[$job_card['assigned_to_mechanic_id']])) {
                                $mechanic_name = htmlspecialchars($mechanics_lookup[$job_card['assigned_to_mechanic_id']]);
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($job_card['job_card_id']); ?></td>
                                <td><?php echo htmlspecialchars($job_card['registration_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($job_card['driver_full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(strlen($job_card['description']) > 70 ? substr($job_card['description'], 0, 70) . '...' : $job_card['description']); ?></td>
                                <td class="status-<?php echo str_replace('_', '-', htmlspecialchars($job_card['status'])); ?>">
                                    <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($job_card['status']))); ?>
                                </td>
                                <td><?php echo $mechanic_name; ?></td>
                                <td><?php echo htmlspecialchars($job_card['created_by_full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y H:i A', strtotime($job_card['created_at'])); ?></td>
                                <td><?php echo !empty($job_card['completed_at']) ? date('M d, Y H:i A', strtotime($job_card['completed_at'])) : 'N/A'; ?></td>
                                <td class="action-links">
                                    <button class="view-details-btn"
                                            data-job-id="<?php echo htmlspecialchars($job_card['job_card_id']); ?>"
                                            data-current-status="<?php echo htmlspecialchars($job_card['status']); ?>"
                                            data-assigned-mechanic="<?php echo htmlspecialchars($job_card['assigned_to_mechanic_id'] ?? ''); ?>"
                                            data-driver-name="<?php echo htmlspecialchars($job_card['driver_full_name'] ?? 'N/A'); ?>"
                                            title="View/Update Job Card">
                                            <i class="fas fa-edit"></i> Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10">No job cards found in the system.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Update Job Card Status</h3>
            <form id="updateJobStatusFormModal">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="update_job_status">
                <input type="hidden" id="modal-job-id" name="job_id">

                <div class="form-group">
                    <label for="modal-driver-name">Driver Name:</label>
                    <input type="text" id="modal-driver-name" readonly>
                </div>

                <div class="form-group">
                    <label for="modal-current-status">Current Status:</label>
                    <input type="text" id="modal-current-status" readonly>
                </div>

                <div class="form-group">
                    <label for="modal-new-status">New Status:</label>
                    <select id="modal-new-status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="on_hold">On Hold</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="modal-mechanic-id">Assign Mechanic:</label>
                    <select id="modal-mechanic-id" name="mechanic_id">
                        <option value="">-- Select Mechanic --</option>
                        <?php foreach ($all_mechanics as $mechanic): ?>
                            <option value="<?php echo htmlspecialchars($mechanic['user_id']); ?>">
                                <?php echo htmlspecialchars($mechanic['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit"><i class="fas fa-sync-alt"></i> Update Job</button>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('updateStatusModal');
            const closeButton = document.querySelector('.close-button');
            const jobUpdateForm = document.getElementById('updateJobStatusFormModal');

            
            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const jobId = this.dataset.jobId;
                    const currentStatus = this.dataset.currentStatus;
                    const assignedMechanic = this.dataset.assignedMechanic;
                    

                    const driverName = this.dataset.driverName; 

                    document.getElementById('modal-job-id').value = jobId;
                    document.getElementById('modal-driver-name').value = driverName;
                    document.getElementById('modal-current-status').value = currentStatus.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()); // Format status
                    document.getElementById('modal-new-status').value = currentStatus; 

                    const mechanicSelect = document.getElementById('modal-mechanic-id');
                    if (assignedMechanic) {
                        mechanicSelect.value = assignedMechanic;
                    } else {
                        mechanicSelect.value = ''; 
                    }

                    modal.style.display = 'block';
                });
            });

        
            closeButton.addEventListener('click', () => {
                modal.style.display = 'none';
            });


            window.addEventListener('click', (event) => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });

            jobUpdateForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(jobUpdateForm);

                try {
                    const response = await fetch('<?php echo BASE_URL; ?>/dashboards/job_card.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert(result.message);
                        modal.style.display = 'none';
                        location.reload(); 
                    } else {

                        alert('Error: ' + (result.message || 'An unknown error occurred. Please check the console for more details.'));
                        console.error('AJAX Error:', result.message);
                    }
                } catch (error) {
                    console.error('Network or Parse Error:', error);
                    alert('An error occurred. Please check your internet connection and try again.');
                }
            });
        });
    </script>
</body>
</html>