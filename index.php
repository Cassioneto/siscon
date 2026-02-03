<?php
require_once 'config.php';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="text-center mb-4">
            <img src="logo.jpg" alt="Logo" style="max-height: 100px;">
            <h4 class="mt-2 text-success" style="font-weight: 700;">HOSPITAL PSIQUIÁTRICO DE LUANDA</h4>
        </div>
        <div class="card">
            <div class="card-header text-center">Acesso ao Sistema</div>
            <div class="card-body p-4">
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <form action="auth_process.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label>E-mail ou Telefone</label>
                        <input type="text" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Senha</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
                <div class="mt-3 text-center">
                    <a href="register.php">Criar conta</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>