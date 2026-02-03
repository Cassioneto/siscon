<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'cataloger') {
    header("Location: ../index.php");
    exit;
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Painel da Catalogadora</h2>
        <hr>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Pacientes</div>
                    <div class="card-body">
                        <h5 class="card-title">Gerenciar</h5>
                        <p class="card-text">Cadastrar e vincular médicos.</p>
                        <a href="patients.php" class="btn btn-light btn-sm">Acessar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Agendamento</div>
                    <div class="card-body">
                        <h5 class="card-title">Nova Consulta</h5>
                        <p class="card-text">Marcar para um paciente.</p>
                        <a href="book.php" class="btn btn-light btn-sm">Agendar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-header">Consultas</div>
                    <div class="card-body">
                        <h5 class="card-title">Ver Agenda</h5>
                        <p class="card-text">Visualizar agendamentos.</p>
                        <a href="appointments.php" class="btn btn-light btn-sm">Ver Todos</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>