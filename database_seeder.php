<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
$conn->select_db(DB_NAME);

// Create tables
$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS telecom_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS service_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    specialization VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS staff_services (
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    PRIMARY KEY (staff_id, service_id),
    FOREIGN KEY (staff_id) REFERENCES service_staff(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES telecom_services(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'rescheduled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES service_staff(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES telecom_services(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS working_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_working BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (staff_id) REFERENCES service_staff(id) ON DELETE CASCADE
);
";

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
}

// Insert sample data
$password = password_hash('admin123', PASSWORD_BCRYPT);
$conn->query("INSERT INTO users (username, password, email, role, full_name, phone) 
             VALUES ('admin', '$password', 'admin@telecom.com', 'admin', 'Admin User', '1234567890')");
$adminId = $conn->insert_id;

// Insert technician user
$password = password_hash('tech123', PASSWORD_BCRYPT);
$conn->query("INSERT INTO users (username, password, email, role, full_name, phone) 
             VALUES ('tech1', '$password', 'tech1@telecom.com', 'customer', 'John Technician', '1234567891')");
$techUserId = $conn->insert_id;

// Insert as service staff
$conn->query("INSERT INTO service_staff (user_id, specialization) 
             VALUES ($techUserId, 'Telecom Technician')");
$staffId = $conn->insert_id;

// Insert customer user
$password = password_hash('customer123', PASSWORD_BCRYPT);
$conn->query("INSERT INTO users (username, password, email, role, full_name, phone, address) 
             VALUES ('customer1', '$password', 'customer1@example.com', 'customer', 'Jane Customer', '0987654321', '123 Main St, City')");
$customerId = $conn->insert_id;

// Insert telecom services
$services = [
    ['Mobile services (prepaid & postpaid)', 'Mobile phone services including prepaid and postpaid plans', 30],
    ['International roaming', 'Setup and support for international roaming services', 45],
    ['Fiber broadband', 'Fiber optic broadband installation and support', 60],
    ['ADSL & VDSL broadband', 'DSL broadband services installation and support', 60],
    ['4G LTE fixed wireless internet', 'Wireless internet installation and support', 45],
    ['Public Wi-Fi', 'Public Wi-Fi hotspot setup and configuration', 30]
];

foreach ($services as $service) {
    $conn->query("INSERT INTO telecom_services (name, description, duration) 
                 VALUES ('$service[0]', '$service[1]', $service[2])");
    $serviceIds[] = $conn->insert_id;
}

// Assign services to staff
foreach ($serviceIds as $serviceId) {
    $conn->query("INSERT INTO staff_services (staff_id, service_id) 
                 VALUES ($staffId, $serviceId)");
}

// Set working hours (Mon-Fri 9am-5pm)
for ($i = 1; $i <= 5; $i++) {
    $conn->query("INSERT INTO working_hours (staff_id, day_of_week, start_time, end_time) 
                 VALUES ($staffId, $i, '09:00:00', '17:00:00')");
}

// Insert sample appointment
$conn->query("INSERT INTO appointments (customer_id, staff_id, service_id, appointment_date, start_time, end_time, status) 
             VALUES ($customerId, $staffId, 1, CURDATE() + INTERVAL 1 DAY, '10:00:00', '10:30:00', 'confirmed')");

echo "Telecom Appointment System installed successfully!<br>";
echo "Admin Credentials: admin / admin123<br>";
echo "Technician Credentials: tech1 / tech123<br>";
echo "Customer Credentials: customer1 / customer123";

$conn->close();
?>