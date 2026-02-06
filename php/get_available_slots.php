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
    
    if (empty($input['doctor']) || empty($input['date'])) {
        echo json_encode(['success' => false, 'message' => 'Doctor and date are required']);
        exit;
    }
    
    $doctor = sanitizeInput($input['doctor']);
    $date = $input['date'];
    
    // Validate date
    if (!validateDate($date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date']);
        exit;
    }
    
    // Get available time slots
    $availableSlots = getAvailableTimeSlots($doctor, $date);
    
    echo json_encode([
        'success' => true,
        'slots' => array_values($availableSlots),
        'date' => $date,
        'doctor' => $doctor
    ]);
    
} catch (Exception $e) {
    error_log("Error getting available slots: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching available slots']);
}
?>