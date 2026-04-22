<?php
/**
 * =====================================================
 * ARD TECH - WhatsApp Cloud API Webhook
 * =====================================================
 * 
 * This file receives messages and screenshots from your website
 * chatbot and forwards them to your WhatsApp.
 * 
 * FREE TIER: 1000 messages/month
 * 
 * =====================================================
 * SETUP INSTRUCTIONS
 * =====================================================
 * 
 * STEP 1: Create Meta Developer Account
 * - Go to https://developers.facebook.com/
 * - Click "Create App"
 * - Select "Business" as app type
 * - Enter "ARD Tech" as app name
 * 
 * STEP 2: Add WhatsApp Product
 * - In your app dashboard, click "Add Products"
 * - Find "WhatsApp" and click "Set up"
 * - Select your business account or create one
 * 
 * STEP 3: Get Your Credentials
 * - Go to WhatsApp > API Setup
 * - Copy these values:
 *   - Phone Number ID
 *   - Temporary Access Token (you'll verify it later)
 * 
 * STEP 4: Configure This File
 * - Open this file in a text editor
 * - Replace the placeholder values below:
 * 
 * $CONFIG = [
 *     'PHONE_NUMBER_ID' => 'YOUR_PHONE_NUMBER_ID',  // e.g., '1234567890'
 *     'ACCESS_TOKEN' => 'YOUR_ACCESS_TOKEN',         // Long string from Meta
 *     'VERIFY_TOKEN' => 'YOUR_VERIFY_TOKEN',         // Create a random password
 *     'ADMIN_PHONE' => '27653547585',               // Your WhatsApp number
 *     'BUSINESS_NAME' => 'ARD Tech'
 * ];
 * 
 * STEP 5: Upload to Your Server
 * - Upload this file to your InfinityFree hosting
 * - The file should be accessible at: yourdomain.com/whatsapp-webhook.php
 * 
 * STEP 6: Configure Webhook in Meta
 * - In WhatsApp API Setup, click "Edit"
 * - Callback URL: https://yourdomain.com/whatsapp-webhook.php
 * - Verify Token: (same as YOUR_VERIFY_TOKEN above)
 * - Click "Verify and Save"
 * 
 * STEP 7: Subscribe to Webhooks
 * - In Meta Developer Console
 * - Webhooks > WhatsApp Webhooks
 * - Subscribe to: messages
 * 
 * =====================================================
 * TESTING YOUR SETUP
 * =====================================================
 * 
 * 1. Open your website in a browser
 * 2. Click the chat button
 * 3. Send a message or attach a screenshot
 * 4. Check your WhatsApp for the forwarded message
 * 
 * =====================================================
 */

// ============== YOUR CONFIGURATION ==============
// Fill in your values below:
$CONFIG = [
    'PHONE_NUMBER_ID' => 'YOUR_PHONE_NUMBER_ID',      // From Meta Developer Console
    'ACCESS_TOKEN' => 'YOUR_ACCESS_TOKEN',              // From Meta Developer Console
    'VERIFY_TOKEN' => 'ardtech2024securetoken',        // Create your own secure token
    'ADMIN_PHONE' => '27653547585',                    // Your WhatsApp (with country code)
    'BUSINESS_NAME' => 'ARD Tech'                      // Your business name
];
// ==========================================

// Disable error display for security
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Handle GET request (webhook verification)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    
    if ($mode === 'subscribe' && $token === $CONFIG['VERIFY_TOKEN']) {
        echo $challenge;
        http_response_code(200);
        logMessage('Webhook verified successfully');
    } else {
        echo 'Verification failed';
        http_response_code(403);
        logMessage('Webhook verification failed');
    }
    exit;
}

// Handle POST request (incoming messages)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    logMessage('Incoming webhook', $input);
    
    // Skip status updates (delivery receipts)
    if (isset($input['entry'][0]['changes'][0]['value']['statuses'])) {
        echo 'OK';
        http_response_code(200);
        exit;
    }
    
    // Process incoming message
    if (isset($input['entry'][0]['changes'][0]['value']['messages'][0])) {
        $msgData = $input['entry'][0]['changes'][0]['value']['messages'][0];
        $from = $msgData['from'];
        $type = $msgData['type'];
        
        $message = '';
        $imageId = null;
        
        if ($type === 'text') {
            $message = $msgData['text']['body'];
        } elseif ($type === 'image') {
            $imageId = $msgData['image']['id'];
        }
        
        logMessage("Message from {$from}: {$message}");
        
        // Forward to your WhatsApp
        forwardToAdmin($from, $message, $imageId);
    }
    
    echo 'OK';
    http_response_code(200);
} else {
    http_response_code(405);
}

/**
 * Forward message and/or screenshot to your WhatsApp
 */
function forwardToAdmin($from, $message, $imageId = null) {
    global $CONFIG;
    
    $url = "https://graph.facebook.com/v18.0/{$CONFIG['PHONE_NUMBER_ID']}/messages";
    
    $headers = [
        'Authorization: Bearer ' . $CONFIG['ACCESS_TOKEN'],
        'Content-Type: application/json'
    ];
    
    // Build message text
    $messageText = "🆕 *New ARD Tech Web Chat*\n\n";
    
    if (!empty($message)) {
        $messageText .= "💬 *Message:*\n{$message}\n\n";
    }
    
    $messageText .= "📱 *From:* {$from}\n";
    $messageText .= "⏰ *Time:* " . date('Y-m-d H:i:s') . "\n";
    $messageText .= "\n_Reply via {$CONFIG['BUSINESS_NAME']} website_";
    
    // Send text message first
    $textData = [
        'messaging_product' => 'whatsapp',
        'to' => $CONFIG['ADMIN_PHONE'],
        'type' => 'text',
        'text' => ['body' => $messageText]
    ];
    
    $result = sendWhatsAppRequest($url, $headers, $textData);
    
    // If there's an image, download and send it
    if ($imageId) {
        $imageUrl = getMediaUrl($imageId);
        if ($imageUrl) {
            $imgData = [
                'messaging_product' => 'whatsapp',
                'to' => $CONFIG['ADMIN_PHONE'],
                'type' => 'image',
                'image' => [
                    'link' => $imageUrl,
                    'caption' => "📸 Screenshot from {$from}"
                ]
            ];
            sendWhatsAppRequest($url, $headers, $imgData);
        }
    }
    
    return $result;
}

/**
 * Get the media URL from WhatsApp Cloud API
 */
function getMediaUrl($mediaId) {
    global $CONFIG;
    
    $url = "https://graph.facebook.com/v18.0/{$mediaId}";
    $headers = ['Authorization: Bearer ' . $CONFIG['ACCESS_TOKEN']];
    
    $response = sendWhatsAppRequest($url, $headers, null, 'GET');
    $result = json_decode($response, true);
    
    return $result['url'] ?? null;
}

/**
 * Send request to WhatsApp Cloud API
 */
function sendWhatsAppRequest($url, $headers, $data, $method = 'POST') {
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    logMessage("WhatsApp API Response (HTTP {$httpCode})", ['response' => $response]);
    
    return $response;
}

/**
 * Log messages to file for debugging
 */
function logMessage($message, $data = []) {
    $logFile = __DIR__ . '/whatsapp-webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    
    $logEntry = "[{$timestamp}] {$message}";
    if (!empty($data)) {
        $logEntry .= ' | ' . json_encode($data);
    }
    $logEntry .= "\n";
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
