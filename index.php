<?php
require_once 'config.php';

// Parse URL
$request = $_SERVER['REQUEST_URI'];
$basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$request = str_replace($basePath, '', $request);
$request = explode('?', $request)[0];
$request = trim($request, '/');
$segments = $request ? explode('/', $request) : [];

// Default route for root URL
if (empty($segments)) {
    if (isLoggedIn()) {
        if (hasRole('admin')) {
            redirect('admin/dashboard');
        } else {
            redirect('appointments');
        }
    } else {
        redirect('login');
    }
    exit;
}

// Route mapping
$routes = [
    'login' => ['AuthController', 'login'],
    'register' => ['AuthController', 'register'],
    'logout' => ['AuthController', 'logout'],
    'appointments' => ['AppointmentController', 'listAppointments'],
    'appointments/book' => ['AppointmentController', 'bookAppointment'],
    'appointments/view/(\d+)' => ['AppointmentController', 'viewAppointment'],
    'appointments/cancel/(\d+)' => ['AppointmentController', 'cancelAppointment'],
    'appointments/staff' => ['AppointmentController', 'getAvailableStaff'],
    'appointments/time-slots' => ['AppointmentController', 'getAvailableTimeSlots'],
    'admin/dashboard' => ['AdminController', 'dashboard'],
    'admin/appointments' => ['AdminController', 'manageAppointments'],
    'admin/staff' => ['AdminController', 'manageStaff'],
    'admin/calendar' => ['AdminController', 'calendarView']
];

// Find matching route
$matched = false;
foreach ($routes as $pattern => $handler) {
    $regex = '#^' . preg_replace('#\(\\\\d\+\)#', '(\d+)', $pattern) . '$#';
    if (preg_match($regex, $request, $matches)) {
        $matched = true;
        array_shift($matches); 
        
        $controllerName = $handler[0];
        $methodName = $handler[1];
        
        require_once "controllers/$controllerName.php";
        $controller = new $controllerName();
        
        if (method_exists($controller, $methodName)) {
            call_user_func_array([$controller, $methodName], $matches);
        } else {
            die("Method $methodName not found in controller $controllerName");
        }
        break;
    }
}

// 404 if no route matched
if (!$matched) {
    header("HTTP/1.0 404 Not Found");
    include 'views/errors/404.php';
}
?>