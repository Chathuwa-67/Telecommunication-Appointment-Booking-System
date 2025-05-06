<?php
function redirect($url) {
    header("Location: " . BASE_URL . "/$url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatDate($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

function formatTime($time, $format = 'H:i') {
    return date($format, strtotime($time));
}

function getAvailableTimeSlots($staffId, $date, $serviceDuration, $conn) {
    $dayOfWeek = date('w', strtotime($date));
    $workingHours = $conn->query("SELECT * FROM working_hours 
                                WHERE staff_id = $staffId AND day_of_week = $dayOfWeek AND is_working = 1")->fetch_assoc();
    
    if (!$workingHours) return [];
    
    $appointments = [];
    $result = $conn->query("SELECT start_time, end_time FROM appointments 
                           WHERE staff_id = $staffId AND appointment_date = '$date' AND status IN ('confirmed', 'pending')");
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    $timeSlots = [];
    $start = strtotime($workingHours['start_time']);
    $end = strtotime($workingHours['end_time']);
    $current = $start;
    
    while ($current + ($serviceDuration * 60) <= $end) {
        $slotStart = date('H:i:s', $current);
        $slotEnd = date('H:i:s', $current + ($serviceDuration * 60));
        
        $isAvailable = true;
        
        foreach ($appointments as $appt) {
            if ((strtotime($slotStart) < strtotime($appt['end_time'])) && (strtotime($slotEnd) > strtotime($appt['start_time']))) {
                $isAvailable = false;
                break;
            }
        }
        
        if ($isAvailable) {
            $timeSlots[] = [
                'start' => $slotStart,
                'end' => $slotEnd,
                'display' => date('g:i A', strtotime($slotStart)) . ' - ' . date('g:i A', strtotime($slotEnd))
            ];
        }
        
        $current += 15 * 60;
    }
    
    return $timeSlots;
}
?>