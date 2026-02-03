<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Agendamento Hospitalar</title>
    <!-- OWASP A03: Software Supply Chain Failures - Use SRI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="/assets/css/custom.css" rel="stylesheet">
    <style>
        /* Fallback for local dev if absolute path fails */
        @import url('../assets/css/custom.css');
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container">
    <a class="navbar-brand" href="/index.php">
        <img src="/logo.jpg" alt="Logo" class="logo-img">
        Hospital Psiquiátrico de Luanda
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon" style="background-color: #ccc; border-radius: 4px;"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if(isset($_SESSION['user_id'])): ?>
            <li class="nav-item">
                <span class="nav-link">Olá, <strong class="text-primary"><?php echo $_SESSION['user_name']; ?></strong></span>
            </li>
            <?php if($_SESSION['user_role'] == 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="/admin/dashboard.php">Painel Admin</a></li>
            <?php elseif($_SESSION['user_role'] == 'cataloger'): ?>
                <li class="nav-item"><a class="nav-link" href="/cataloger/dashboard.php">Painel Catalogadora</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link btn btn-danger text-white btn-sm ms-2" href="/logout.php">Sair</a></li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="/index.php">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="/register.php">Cadastro</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
