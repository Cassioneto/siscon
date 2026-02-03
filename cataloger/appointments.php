<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'cataloger') {
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

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Buscar por Paciente (Nome ou Telefone)</label>
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Digite...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data</label>
                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <a href="appointments.php" class="btn btn-outline-secondary w-100">Limpar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Telefone</th>
                                <th>Médico</th>
                                <th>Data</th>
                                <th>Hora</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $search = $_GET['search'] ?? '';
                            $date = $_GET['date'] ?? '';

                            $sql = "SELECT a.*, u.name as patient_name, u.phone as patient_phone, d.name as doctor_name 
                                    FROM appointments a 
                                    JOIN users u ON a.user_id = u.id 
                                    JOIN doctors d ON a.doctor_id = d.id 
                                    WHERE 1=1";
                            
                            $params = [];

                            if (!empty($search)) {
                                $sql .= " AND (u.name LIKE ? OR u.phone LIKE ?)";
                                $params[] = "%$search%";
                                $params[] = "%$search%";
                            }

                            if (!empty($date)) {
                                $sql .= " AND a.appointment_date = ?";
                                $params[] = $date;
                            }

                            $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                            
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);

                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $status_class = match($row['status']) {
                                    'scheduled' => 'bg-warning text-dark',
                                    'cancelled' => 'bg-danger text-white',
                                    'completed' => 'bg-success text-white',
                                    default => 'bg-secondary'
                                };
                                
                                echo "<tr>";
                                echo "<td class='fw-bold'>" . htmlspecialchars($row['patient_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['patient_phone']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                                echo "<td>" . date('d/m/Y', strtotime($row['appointment_date'])) . "</td>";
                                echo "<td>" . date('H:i', strtotime($row['appointment_time'])) . "</td>";
                                echo "<td><span class='badge {$status_class}'>{$row['status']}</span></td>";
                                echo "<td>";
                                if ($row['status'] == 'scheduled') {
                                    echo "<div class='btn-group' role='group'>";
                                    echo "<a href='?action=complete&id={$row['id']}' class='btn btn-outline-success btn-sm' onclick='return confirm(\"Confirmar que o paciente compareceu e foi atendido?\")' title='Confirmar Atendimento'><i class='bi bi-check-lg'></i> Atendido</a>";
                                    echo "<a href='?action=cancel&id={$row['id']}' class='btn btn-outline-danger btn-sm' onclick='return confirm(\"Cancelar agendamento?\")' title='Cancelar Agendamento'><i class='bi bi-x-lg'></i> Cancelar</a>";
                                    echo "</div>";
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
</div>

<?php include '../includes/footer.php'; ?>