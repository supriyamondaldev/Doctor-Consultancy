<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Simple authentication (in production, use proper authentication)
session_start();
$admin_password = 'admin123'; // Change this in production

if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        // Show login form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Login - MediConsult</title>
            <link rel="stylesheet" href="css/style.css">
        </head>
        <body>
            <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f8f9fa;">
                <form method="POST" style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <h2 style="text-align: center; margin-bottom: 2rem;">Admin Login</h2>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%;">Login</button>
                    <p style="text-align: center; margin-top: 1rem; color: #666; font-size: 0.9rem;">
                        Default password: admin123
                    </p>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Handle status updates
if (isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];
    updateAppointmentStatus($appointment_id, $new_status);
    header('Location: admin.php');
    exit;
}

// Get all appointments
$appointments = getAllAppointments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - MediConsult</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        .admin-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .appointments-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #2c5aa0;
            color: white;
            padding: 1.5rem 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #1f2937;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-form {
            display: inline-block;
            margin: 0 0.25rem;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-confirm {
            background: #10b981;
            color: white;
        }
        
        .btn-complete {
            background: #3b82f6;
            color: white;
        }
        
        .btn-cancel {
            background: #ef4444;
            color: white;
        }
        
        .btn-small:hover {
            opacity: 0.8;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c5aa0;
        }
        
        .stat-label {
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        .no-appointments {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            table {
                font-size: 0.875rem;
            }
            
            th, td {
                padding: 0.5rem;
            }
            
            .action-form {
                display: block;
                margin: 0.25rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1>⚕️ MediConsult Admin Panel</h1>
                <p>Manage appointments and view statistics</p>
            </div>
            <div>
                <a href="index.php" class="btn-primary">Back to Website</a>
                <a href="?logout=1" class="btn-secondary" style="margin-left: 1rem;">Logout</a>
            </div>
        </div>
        
        <?php
        // Calculate statistics
        $total_appointments = count($appointments);
        $pending = count(array_filter($appointments, fn($a) => $a['status'] === 'pending'));
        $confirmed = count(array_filter($appointments, fn($a) => $a['status'] === 'confirmed'));
        $completed = count(array_filter($appointments, fn($a) => $a['status'] === 'completed'));
        $cancelled = count(array_filter($appointments, fn($a) => $a['status'] === 'cancelled'));
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_appointments; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $confirmed; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $completed; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        
        <div class="appointments-table">
            <div class="table-header">
                <h2>Recent Appointments</h2>
            </div>
            
            <?php if (empty($appointments)): ?>
                <div class="no-appointments">
                    <p>No appointments found.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td>#<?php echo str_pad($appointment['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['email']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['phone']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['doctor_name'] ?: $appointment['doctor']); ?></td>
                            <td>
                                <?php 
                                echo date('M j, Y', strtotime($appointment['appointment_date'])) . '<br>';
                                echo date('g:i A', strtotime($appointment['appointment_time']));
                                ?>
                            </td>
                            <td><?php echo ucfirst($appointment['consultation_type']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($appointment['status'] === 'pending'): ?>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" name="update_status" class="btn-small btn-confirm">Confirm</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($appointment['status'] === 'confirmed'): ?>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" name="update_status" class="btn-small btn-complete">Complete</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if (in_array($appointment['status'], ['pending', 'confirmed'])): ?>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" name="update_status" class="btn-small btn-cancel" 
                                                onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}
?>