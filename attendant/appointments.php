<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'attendant') {
    header("Location: ../index.php");
    exit;
}

// Reuse Admin Appointments Logic mostly
// Handle Status Change
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['action'] == 'cancel' ? 'cancelled' : 'completed';
    
    try {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $success = "Status atualizado!";
    } catch (PDOException $e) {
        $error = "Erro ao atualizar: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Agenda Geral</h2>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Voltar</a>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Médico</th>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT a.*, u.name as patient_name, d.name as doctor_name 
                                FROM appointments a 
                                JOIN users u ON a.user_id = u.id 
                                JOIN doctors d ON a.doctor_id = d.id 
                                ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                        $stmt = $pdo->query($sql);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $status_class = match($row['status']) {
                                'scheduled' => 'bg-warning text-dark',
                                'cancelled' => 'bg-danger text-white',
                                'completed' => 'bg-success text-white',
                                default => 'bg-secondary'
                            };
                            
                            echo "<tr>";
                            echo "<td>{$row['patient_name']}</td>";
                            echo "<td>{$row['doctor_name']}</td>";
                            echo "<td>" . date('d/m/Y', strtotime($row['appointment_date'])) . "</td>";
                            echo "<td>" . date('H:i', strtotime($row['appointment_time'])) . "</td>";
                            echo "<td><span class='badge {$status_class}'>{$row['status']}</span></td>";
                            echo "<td>";
                            if ($row['status'] == 'scheduled') {
                                echo "<a href='?action=cancel&id={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Cancelar agendamento?\")'>Cancelar</a>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>