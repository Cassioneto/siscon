<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Agendamento Hospitalar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="/index.php">Hospital Sys</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if(isset($_SESSION['user_id'])): ?>
            <li class="nav-item">
                <span class="nav-link text-white">Olá, <?php echo $_SESSION['user_name']; ?></span>
            </li>
            <?php if($_SESSION['user_role'] == 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="/admin/dashboard.php">Painel Admin</a></li>
            <?php elseif($_SESSION['user_role'] == 'attendant'): ?>
                <li class="nav-item"><a class="nav-link" href="/attendant/dashboard.php">Painel Atendente</a></li>
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
