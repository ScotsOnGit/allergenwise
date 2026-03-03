<?php
/**
 * AllergenWise Newsletter Signup Handler
 * Saves newsletter subscriptions to a CSV file
 */

// Configuration
$log_file = __DIR__ . '/newsletter_subscribers.csv';
$admin_email = 'info@allergenwise.com.au';

// Set response format
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
error_reporting(0);
ini_set('display_errors', 0);

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Honeypot check (anti-spam)
if (!empty($_POST['website'])) {
    echo json_encode(['success' => true, 'message' => 'Thank you for subscribing!']);
    exit;
}

// Sanitize email
$email = filter_var(trim($_POST['newsletter_email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

// Get current date/time
$timestamp = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// Prepare CSV row
$row = [$timestamp, $email, $ip];

// Check if file exists, if not create with headers
$file_exists = file_exists($log_file);
$handle = fopen($log_file, 'a');

if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Unable to save. Please try again later.']);
    exit;
}

// Add header row if new file
if (!$file_exists) {
    fputcsv($handle, ['Timestamp', 'Email', 'IP Address']);
}

// Check for duplicate email
$duplicates = [];
if ($file_exists) {
    $handle_read = fopen($log_file, 'r');
    // Skip header
    fgetcsv($handle_read);
    while (($data = fgetcsv($handle_read)) !== false) {
        if (isset($data[1]) && strtolower($data[1]) === strtolower($email)) {
            $duplicates[] = $email;
            break;
        }
    }
    fclose($handle_read);
}

if (!empty($duplicates)) {
    echo json_encode(['success' => true, 'message' => 'You\'re already subscribed!']);
    fclose($handle);
    exit;
}

// Write the new subscriber
fputcsv($handle, $row);
fclose($handle);

// Optional: Send confirmation email to subscriber (uncomment if desired)
/*
$subject = 'Welcome to AllergenWise!';
$message = "Thank you for subscribing to AllergenWise updates!\n\n";
$message .= "We'll keep you informed about allergen regulations, tips for hospitality venues, and news from AllergenWise.\n\n";
$message .= "If you didn't subscribe, please ignore this email.\n\n";
$headers = "From: AllergenWise <noreply@allergenwise.com.au>\r\n";
@mail($email, $subject, $message, $headers);
*/

// Optional: Notify admin of new subscriber
$admin_subject = 'New AllergenWise Newsletter Subscriber';
$admin_message = "New newsletter subscription:\n\nEmail: $email\nDate: $timestamp\nIP: $ip";
$admin_headers = "From: AllergenWise Website <noreply@allergenwise.com.au>\r\n";
@mail($admin_email, $admin_subject, $admin_message, $admin_headers);

echo json_encode(['success' => true, 'message' => 'Thank you for subscribing!']);
