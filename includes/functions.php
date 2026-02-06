<?php

// Sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Validate date format and ensure it's not in the past
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return false;
    }
    
    // Check if date is not in the past
    $today = new DateTime();
    $appointmentDate = new DateTime($date);
    
    return $appointmentDate >= $today;
}

// Validate time format
function validateTime($time) {
    $t = DateTime::createFromFormat('H:i', $time);
    return $t && $t->format('H:i') === $time;
}

// Check if appointment slot is available
function isSlotAvailable($doctor, $date, $time) {
    try {
        $pdo = getDBConnection();
        $sql = "SELECT COUNT(*) FROM appointments 
                WHERE doctor = :doctor 
                AND appointment_date = :date 
                AND appointment_time = :time 
                AND status != 'cancelled'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'doctor' => $doctor,
            'date' => $date,
            'time' => $time
        ]);
        
        return $stmt->fetchColumn() == 0;
    } catch (Exception $e) {
        error_log("Error checking slot availability: " . $e->getMessage());
        return false;
    }
}

// Send confirmation email
function sendConfirmationEmail($data, $appointment_id) {
    $to = $data['email'];
    $subject = "Appointment Confirmation - MediConsult";
    
    // Get doctor name from database
    $doctorName = getDoctorNameById($data['doctor']);
    
    $message = "
    <html>
    <head>
        <title>Appointment Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%); color: white; padding: 20px; text-align: center; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 8px; margin: 20px 0; }
            .appointment-details { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; }
            .footer { text-align: center; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>⚕️ MediConsult</h1>
                <h2>Appointment Confirmed!</h2>
            </div>
            
            <div class='content'>
                <p>Dear {$data['first_name']} {$data['last_name']},</p>
                
                <p>Thank you for booking your consultation with us. Your appointment has been successfully scheduled.</p>
                
                <div class='appointment-details'>
                    <h3>Appointment Details:</h3>
                    <p><strong>Appointment ID:</strong> #" . str_pad($appointment_id, 6, '0', STR_PAD_LEFT) . "</p>
                    <p><strong>Doctor:</strong> $doctorName</p>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($data['appointment_date'])) . "</p>
                    <p><strong>Time:</strong> " . date('g:i A', strtotime($data['appointment_time'])) . "</p>
                    <p><strong>Type:</strong> " . ucfirst($data['consultation_type']) . "</p>
                    <p><strong>Contact:</strong> {$data['phone']}</p>
                </div>
                
                <h3>Important Information:</h3>
                <ul>
                    <li>Please arrive 15 minutes early for your appointment</li>
                    <li>Bring a valid ID and insurance card (if applicable)</li>
                    <li>If you need to reschedule, please call us at least 24 hours in advance</li>
                    <li>For video consultations, you will receive a link 30 minutes before your appointment</li>
                </ul>
                
                <p>If you have any questions or need to make changes to your appointment, please contact us at:</p>
                <p>📞 +1 (555) 123-4567<br>📧 info@mediconsult.com</p>
            </div>
            
            <div class='footer'>
                <p>Thank you for choosing MediConsult for your healthcare needs.</p>
                <p>&copy; 2025 MediConsult. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers for HTML email
    $headers = array(
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=UTF-8',
        'From' => 'noreply@mediconsult.com',
        'Reply-To' => 'info@mediconsult.com',
        'X-Mailer' => 'PHP/' . phpversion()
    );
    
    // Convert headers array to string
    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= $key . ': ' . $value . "\r\n";
    }
    
    try {
        $result = mail($to, $subject, $message, $headerString);
        if (!$result) {
            error_log("Failed to send email to: $to");
        }
        return $result;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

// Get doctor name by ID
function getDoctorNameById($doctorId) {
    try {
        $pdo = getDBConnection();
        $sql = "SELECT name FROM doctors WHERE id = :id OR CONCAT('dr-', LOWER(REPLACE(SUBSTRING_INDEX(name, ' ', -1), ' ', ''))) = :doctor_slug";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $doctorId, 'doctor_slug' => $doctorId]);
        
        $result = $stmt->fetchColumn();
        return $result ? $result : 'Doctor';
    } catch (Exception $e) {
        error_log("Error getting doctor name: " . $e->getMessage());
        return 'Doctor';
    }
}

// Get all appointments (for admin panel)
function getAllAppointments($limit = 50) {
    try {
        $pdo = getDBConnection();
        $sql = "SELECT a.*, d.name as doctor_name 
                FROM appointments a 
                LEFT JOIN doctors d ON (d.id = a.doctor OR CONCAT('dr-', LOWER(REPLACE(SUBSTRING_INDEX(d.name, ' ', -1), ' ', ''))) = a.doctor)
                ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting appointments: " . $e->getMessage());
        return [];
    }
}

// Update appointment status
function updateAppointmentStatus($appointment_id, $status) {
    try {
        $pdo = getDBConnection();
        $sql = "UPDATE appointments SET status = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            'status' => $status,
            'id' => $appointment_id
        ]);
    } catch (Exception $e) {
        error_log("Error updating appointment status: " . $e->getMessage());
        return false;
    }
}

// Get available time slots for a doctor on a specific date
function getAvailableTimeSlots($doctor, $date) {
    $allSlots = [
        '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
        '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00'
    ];
    
    try {
        $pdo = getDBConnection();
        $sql = "SELECT appointment_time FROM appointments 
                WHERE doctor = :doctor 
                AND appointment_date = :date 
                AND status != 'cancelled'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['doctor' => $doctor, 'date' => $date]);
        
        $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $bookedTimes = array_map(function($time) {
            return date('H:i', strtotime($time));
        }, $bookedSlots);
        
        return array_diff($allSlots, $bookedTimes);
    } catch (Exception $e) {
        error_log("Error getting available slots: " . $e->getMessage());
        return $allSlots;
    }
}
?>