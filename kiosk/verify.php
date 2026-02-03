<?php
require_once '../config.php';

if (!isset($_SESSION['otp_phone'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = $_POST['code'] ?? '';
    
    if ($code == $_SESSION['otp_code'] && time() < $_SESSION['otp_expiry']) {
        // Success
        $_SESSION['kiosk_verified'] = true;
        $_SESSION['kiosk_phone'] = $_SESSION['otp_phone'];
        
        // Cleanup OTP
        unset($_SESSION['otp_code']);
        unset($_SESSION['otp_expiry']);
        
        header("Location: book.php");
        exit;
    } else {
        $error = "Código inválido ou expirado.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação - Hospital Psiquiátrico de Luanda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .kiosk-container { max-width: 500px; margin: 50px auto; text-align: center; }
        .otp-input { letter-spacing: 10px; font-size: 2rem; text-align: center; }
    </style>
</head>
<body>

<div class="container kiosk-container">
    <div class="card shadow-lg border-0">
        <div class="card-body p-5">
            <h3 class="mb-4">Verificação</h3>
            <p>Enviamos um código de 4 dígitos para <strong><?php echo htmlspecialchars($_SESSION['otp_phone']); ?></strong></p>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <input type="text" name="code" class="form-control otp-input" maxlength="4" placeholder="0000" required inputmode="numeric">
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg">Verificar</button>
            </form>
            
            <form action="send_otp.php" method="POST" class="mt-3">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($_SESSION['otp_phone']); ?>">
                <button type="submit" class="btn btn-link">Reenviar Código</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
