<?php
require_once '../config.php';
// No login required for Kiosk start
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autoatendimento - Hospital Sys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #e9ecef; }
        .kiosk-container { max-width: 600px; margin: 50px auto; text-align: center; }
        .btn-xl { padding: 20px 40px; font-size: 24px; border-radius: 10px; width: 100%; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container kiosk-container">
    <h1 class="display-4 mb-5">Bem-vindo ao Hospital Sys</h1>
    <div class="card shadow-lg">
        <div class="card-body p-5">
            <h3 class="mb-4">Para iniciar, digite seu E-mail</h3>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>

            <form action="book.php" method="POST">
                <div class="mb-4">
                    <input type="email" name="email" class="form-control form-control-lg text-center" placeholder="seu@email.com" required>
                </div>
                <button type="submit" class="btn btn-primary btn-xl">Continuar</button>
            </form>
            <p class="text-muted mt-3">Não tem cadastro? Por favor, dirija-se à recepção.</p>
        </div>
    </div>
    <div class="mt-5">
        <a href="../index.php" class="text-muted text-decoration-none">Área Restrita (Funcionários)</a>
    </div>
</div>

</body>
</html>
