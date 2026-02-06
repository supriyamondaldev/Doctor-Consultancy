<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // If no JSON input, try $_POST
    if (empty($input)) {
        $input = $_POST;
    }
    
    // Validate required fields
    $required_fields = ['firstName', 'lastName', 'email', 'phone', 'doctor', 'consultationType', 'date', 'time'];
    
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }
    
    // Sanitize and validate data
    $data = [
        'first_name' => sanitizeInput($input['firstName']),
        'last_name' => sanitizeInput($input['lastName']),
        'email' => filter_var($input['email'], FILTER_VALIDATE_EMAIL),
        'phone' => sanitizeInput($input['phone']),
        'doctor' => sanitizeInput($input['doctor']),
        'consultation_type' => sanitizeInput($input['consultationType']),
        'appointment_date' => $input['date'],
        'appointment_time' => $input['time'],
        'symptoms' => isset($input['symptoms']) ? sanitizeInput($input['symptoms']) : ''
    ];
    
    // Additional validation
    if (!$data['email']) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    if (!validateDate($data['appointment_date'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid date']);
        exit;
    }
    
    if (!validateTime($data['appointment_time'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid time']);
        exit;
    }
    
    // Check if appointment slot is available
    if (!isSlotAvailable($data['doctor'], $data['appointment_date'], $data['appointment_time'])) {
        echo json_encode(['success' => false, 'message' => 'This time slot is not available']);
        exit;
    }
    
    // Insert appointment into database
    $pdo = getDBConnection();
    $sql = "INSERT INTO appointments (first_name, last_name, email, phone, doctor, consultation_type, appointment_date, appointment_time, symptoms) 
            VALUES (:first_name, :last_name, :email, :phone, :doctor, :consultation_type, :appointment_date, :appointment_time, :symptoms)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($data);
    
    if ($result) {
        $appointment_id = $pdo->lastInsertId();
        
        // Send confirmation email
        $email_sent = sendConfirmationEmail($data, $appointment_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked successfully!',
            'appointment_id' => $appointment_id,
            'email_sent' => $email_sent
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to book appointment']);
    }
    
} catch (Exception $e) {
    error_log("Booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while booking your appointment']);
}
?>