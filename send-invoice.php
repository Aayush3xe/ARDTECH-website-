<?php
/**
 * =====================================================
 * ARD TECH - Invoice Email Sender
 * =====================================================
 * 
 * This file receives order data and sends an invoice
 * email to support.ardtech@gmail.com
 * 
 * NOTE: Free hosting may block emails. If emails don't
 * work, use WhatsApp Cloud API or third-party services.
 * 
 * =====================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests allowed']);
    exit;
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

// Extract order data
$invoiceNumber = $input['invoice'] ?? 'ARDTECH-000000';
$date = $input['date'] ?? date('d/m/Y');
$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';
$service = $input['service'] ?? '';
$price = $input['price'] ?? '';

// Validate required fields
if (empty($name) || empty($email) || empty($service)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Email recipient
$to = 'support.ardtech@gmail.com';

// Email subject
$subject = "New Order - Invoice {$invoiceNumber} - {$service}";

// Build HTML email body
$body = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0a0e14 0%, #1a2234 100%); padding: 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 28px; background: linear-gradient(135deg, #ffffff 0%, #00a6e2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .header p { margin: 5px 0 0 0; color: #00a6e2; font-size: 14px; }
        .body { padding: 30px; }
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .info-box { flex: 1; }
        .info-box h3 { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0; }
        .info-box p { margin: 5px 0; font-size: 14px; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .invoice-table th { background: #0a0e14; color: white; padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; }
        .invoice-table td { padding: 15px 12px; border-bottom: 1px solid #eee; }
        .invoice-table td:last-child { text-align: right; font-weight: bold; color: #00a6e2; }
        .total-box { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: right; }
        .total-box .label { font-size: 14px; color: #666; }
        .total-box .amount { font-size: 28px; font-weight: bold; color: #00a6e2; margin-top: 5px; }
        .payment-section { background: linear-gradient(135deg, #0a0e14 0%, #1a2234 100%); padding: 25px; border-radius: 8px; margin-top: 20px; }
        .payment-section h3 { color: #00a6e2; margin: 0 0 15px 0; font-size: 14px; text-transform: uppercase; }
        .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .payment-item { display: flex; justify-content: space-between; color: white; font-size: 13px; }
        .payment-item span:first-child { color: #aaa; }
        .payment-item span:last-child { font-weight: bold; }
        .terms { background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #ffc107; }
        .terms h4 { color: #856404; margin: 0 0 8px 0; font-size: 13px; }
        .terms p { color: #856404; margin: 0; font-size: 12px; line-height: 1.5; }
        .footer { text-align: center; padding: 20px; background: #f8f9fa; font-size: 12px; color: #666; }
        .footer a { color: #00a6e2; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ARD TECH</h1>
            <p>Your Trusted Tech Repair & Solutions Partner</p>
        </div>
        <div class="body">
            <div class="info-grid">
                <div class="info-box">
                    <h3>Invoice</h3>
                    <p><strong>Invoice #:</strong> ' . $invoiceNumber . '</p>
                    <p><strong>Date:</strong> ' . $date . '</p>
                </div>
                <div class="info-box">
                    <h3>Bill To</h3>
                    <p><strong>Name:</strong> ' . $name . '</p>
                    <p><strong>Email:</strong> ' . $email . '</p>
                    <p><strong>Phone:</strong> ' . $phone . '</p>
                </div>
            </div>
            
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th style="text-align: right;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . $service . '</td>
                        <td>' . $price . '</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="total-box">
                <div class="label">TOTAL</div>
                <div class="amount">' . $price . '</div>
            </div>
            
            <div class="payment-section">
                <h3>Payment Details</h3>
                <div class="payment-grid">
                    <div class="payment-item"><span>Bank:</span><span>Capitec</span></div>
                    <div class="payment-item"><span>Account Holder:</span><span>MR AAYUSH DEEPLALL</span></div>
                    <div class="payment-item"><span>Business Name:</span><span>ARDTECH</span></div>
                    <div class="payment-item"><span>Account Type:</span><span>Entrepreneur Account</span></div>
                    <div class="payment-item"><span>Account Number:</span><span>2531004366</span></div>
                    <div class="payment-item"><span>Branch Code:</span><span>470010</span></div>
                    <div class="payment-item"><span>Payment Method:</span><span>Immediate EFT</span></div>
                </div>
            </div>
            
            <div class="terms">
                <h4>Payment Instructions</h4>
                <p>Please make payment to the bank details above. Full payment is required before service delivery. Software installations come with a 30-day guarantee.</p>
            </div>
        </div>
        <div class="footer">
            <p><strong>ARD Tech</strong> - Your Trusted Tech Repair & Solutions Partner</p>
            <p>Customer Email: ' . $email . ' | Phone: ' . $phone . '</p>
            <p>Email: support.ardtech@gmail.com | Phone: 065 354 7585</p>
        </div>
    </div>
</body>
</html>';

// Email headers
$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: ARD Tech <noreply@ardtech.page.gd>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion()
];

// Try to send email
$sent = mail($to, $subject, $body, implode("\r\n", $headers));

// Log the attempt
$logFile = __DIR__ . '/invoice-emails.log';
$timestamp = date('Y-m-d H:i:s');
$logEntry = "[{$timestamp}] Invoice {$invoiceNumber} - To: {$to} - Name: {$name} - Service: {$service} - Price: {$price} - Status: " . ($sent ? 'SENT' : 'FAILED') . "\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Return response
if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Invoice sent to your email']);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Email could not be sent. You can still view your invoice on the next page.'
    ]);
}
?>
