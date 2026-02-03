<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../includes/header.php';

// Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM specialties");
$total_specialties = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
$total_doctors = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM appointments");
$total_appointments = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'cataloger'");
$total_catalogers = $stmt->fetchColumn();
?>

<h2>Painel Administrativo</h2>
<hr>

<div class="row">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Especialidades</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $total_specialties; ?></h5>
                <a href="specialties.php" class="text-white">Gerenciar</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Médicos</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $total_doctors; ?></h5>
                <a href="doctors.php" class="text-white">Gerenciar</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-header">Agendamentos</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $total_appointments; ?></h5>
                <a href="appointments.php" class="text-white">Ver Todos</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info mb-3">
            <div class="card-header">Catalogadoras</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $total_catalogers; ?></h5>
                <a href="users.php" class="text-white">Gerenciar Equipe</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>