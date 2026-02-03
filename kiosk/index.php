<?php
require_once '../config.php';
// No login required for Kiosk start
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autoatendimento - Hospital Psiquiátrico de Luanda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body class="kiosk-mode">

<div class="container kiosk-container" style="max-width: 700px; margin-top: 5vh;">
    <div class="text-center mb-4">
        <img src="../logo.jpg" alt="Logo" class="logo-kiosk" style="max-height: 100px;">
        <h1 class="display-6 mt-3 text-success fw-bold">HOSPITAL PSIQUIÁTRICO DE LUANDA</h1>
        <h2 class="h5 text-muted">Autoatendimento</h2>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-4 text-center">Identificação do Paciente</h3>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger fade-in"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success fade-in"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>

            <form action="send_otp.php" method="POST">
                <div class="mb-4">
                    <label class="form-label text-muted small text-uppercase fw-bold">Número de Telefone</label>
                    <input type="tel" name="phone" class="form-control form-control-lg text-center fs-3" placeholder="9xx xxx xxx" required inputmode="numeric" autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fs-4 shadow-sm">
                    Continuar <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>
            <p class="text-muted mt-4 text-center small">Não tem cadastro? Por favor, dirija-se à recepção.</p>
        </div>
    </div>
    <div class="mt-4 text-center">
        <a href="../index.php" class="text-muted text-decoration-none small">Área Restrita (Funcionários)</a>
    </div>
</div>

</body>
</html>
