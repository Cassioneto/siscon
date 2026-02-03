<?php
require_once '../config.php';
require_once '../includes/mimo_api.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone_input = $_POST['phone'] ?? '';
    
    // Sanitize: Keep only numbers and +
    $phone = preg_replace('/[^0-9+]/', '', $phone_input);

    error_log("Kiosk Login Attempt: Input='$phone_input', Cleaned='$phone'");

    // Validate phone (basic check)
    if (empty($phone)) {
        header("Location: index.php?error=Telefone obrigatório");
        exit;
    }

    // Extract last 9 digits for robust search
    $last9 = substr(preg_replace('/[^0-9]/', '', $phone), -9);
    
    error_log("Kiosk Search: Last9='$last9'");

    if (strlen($last9) < 9) {
         header("Location: index.php?error=Número de telefone inválido (muito curto)");
         exit;
    }

    // Search using LIKE for suffix match
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone LIKE ? AND role = 'patient'");
    $stmt->execute(["%$last9"]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log result for debugging
    if ($patient) {
        error_log("Kiosk: Found patient ID " . $patient['id']);
    } else {
        error_log("Kiosk: Patient NOT found for search %$last9");
        
        // Debug: Check if user exists but with different role
        $stmtCheck = $pdo->prepare("SELECT * FROM users WHERE phone LIKE ?");
        $stmtCheck->execute(["%$last9"]);
        $anyUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if ($anyUser) {
            error_log("Kiosk Debug: User exists but role is '" . $anyUser['role'] . "'");
        }
    }

    if (!$patient) {
        header("Location: index.php?error=Paciente não encontrado. Dirija-se à recepção.");
        exit;
    }

    // Generate 4-digit OTP
    $otp = rand(1000, 9999);
    
    // Store in session
    $_SESSION['otp_phone'] = $phone;
    $_SESSION['otp_code'] = $otp;
    $_SESSION['otp_expiry'] = time() + 300; // 5 minutes

    // Send SMS
    $sms = new TelcoSMS();
    $message = "Seu codigo de acesso Hospital: $otp";
    
    // Send using TelcoSMS
     $sms->sendSMS($phone, $message);
    
    // For debugging/demo purposes, log the OTP
    error_log("OTP for $phone: $otp");

    // Redirect to Verify Page
    header("Location: verify.php");
    exit;
} else {
    header("Location: index.php");
    exit;
}
