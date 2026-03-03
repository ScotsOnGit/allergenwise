<?php
/**
 * AllergenWise Contact Form Handler
 * Receives form submissions and emails to info@allergenwise.com.au
 */

// Configuration
$to = 'info@allergenwise.com.au';
$subject_prefix = 'AllergenWise Website Enquiry:';

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

// Honeypot field check (anti-spam)
if (!empty($_POST['website'])) {
    // Silent success for bots
    echo json_encode(['success' => true, 'message' => 'Thank you for your enquiry!']);
    exit;
}

// Sanitize and validate input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

$venue = sanitize($_POST['Venue'] ?? '');
$suburb = sanitize($_POST['Suburb'] ?? '');
$name = sanitize($_POST['Name'] ?? '');
$email = sanitize($_POST['Email'] ?? '');
$menu = sanitize($_POST['Menu'] ?? '');
$message = sanitize($_POST['Message'] ?? '');

// Validate required fields
$errors = [];
if (empty($name)) {
    $errors[] = 'Contact name is required';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email address is required';
}
if (empty($message)) {
    $errors[] = 'Message is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
    exit;
}

// Build email content
$email_subject = $subject_prefix . ' ' . ($venue ?: 'General Enquiry');

$email_body = "New enquiry from AllergenWise website\n";
$email_body .= "=====================================\n\n";
$email_body .= "Venue: " . ($venue ?: 'Not provided') . "\n";
$email_body .= "Suburb: " . ($suburb ?: 'Not provided') . "\n";
$email_body .= "Contact Name: " . $name . "\n";
$email_body .= "Email: " . $email . "\n";
$email_body .= "Menu Link: " . ($menu ?: 'Not provided') . "\n\n";
$email_body .= "Message:\n" . $message . "\n\n";
$email_body .= "-----------------------------------\n";
$email_body .= "Submitted: " . date('d/m/Y H:i:s') . "\n";

// Set email headers
$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'From: AllergenWise Website <noreply@allergenwise.com.au>';
$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
$headers[] = 'X-Mailer: PHP/' . phpversion();

// Send email
$mail_sent = @mail($to, $email_subject, $email_body, implode("\r\n", $headers));

if ($mail_sent) {
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you for your enquiry! We will be in touch soon.'
    ]);
} else {
    // Fallback: log error and return success to user (to not reveal system issues)
    $log_error = date('Y-m-d H:i:s') . " - Email failed to send to $to\n";
    @file_put_contents(__DIR__ . '/form_errors.log', $log_error, FILE_APPEND);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you for your enquiry! We will be in touch soon.'
    ]);
}
