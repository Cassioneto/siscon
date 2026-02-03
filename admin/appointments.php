<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

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

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    $search = $_GET['search'] ?? '';
    $params = [];
    
    $sql = "SELECT a.*, u.name as patient_name, d.name as doctor_name, s.name as specialty_name 
            FROM appointments a 
            JOIN users u ON a.user_id = u.id 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN specialties s ON d.specialty_id = s.id 
            WHERE 1=1";

    if (!empty($search)) {
        $sql .= " AND (u.name LIKE ? OR d.name LIKE ? OR s.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
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
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['specialty_name']) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($row['appointment_date'])) . "</td>";
        echo "<td>" . date('H:i', strtotime($row['appointment_time'])) . "</td>";
        echo "<td><span class='badge {$status_class}'>{$row['status']}</span></td>";
        echo "<td>";
        if ($row['status'] == 'scheduled') {
            echo "<a href='?action=complete&id={$row['id']}' class='btn btn-success btn-sm me-1'>Concluir</a>";
            echo "<a href='?action=cancel&id={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Cancelar agendamento?\")'>Cancelar</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    exit;
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Todos os Agendamentos</h2>
            <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-white">
                <input type="text" id="searchInput" class="form-control" placeholder="Pesquisar por paciente, médico ou especialidade...">
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Especialidade</th>
                                <th>Data</th>
                                <th>Hora</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentsTableBody">
                            <?php
                            // Initial Load (same logic as AJAX but without search params initially)
                            $sql = "SELECT a.*, u.name as patient_name, d.name as doctor_name, s.name as specialty_name 
                                    FROM appointments a 
                                    JOIN users u ON a.user_id = u.id 
                                    JOIN doctors d ON a.doctor_id = d.id 
                                    JOIN specialties s ON d.specialty_id = s.id 
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
                                echo "<td>{$row['id']}</td>";
                                echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['specialty_name']) . "</td>";
                                echo "<td>" . date('d/m/Y', strtotime($row['appointment_date'])) . "</td>";
                                echo "<td>" . date('H:i', strtotime($row['appointment_time'])) . "</td>";
                                echo "<td><span class='badge {$status_class}'>{$row['status']}</span></td>";
                                echo "<td>";
                                if ($row['status'] == 'scheduled') {
                                    echo "<a href='?action=complete&id={$row['id']}' class='btn btn-success btn-sm me-1'>Concluir</a>";
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
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const query = this.value;
    fetch(`appointments.php?ajax_search=1&search=${encodeURIComponent(query)}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('appointmentsTableBody').innerHTML = html;
        })
        .catch(err => console.error('Erro na pesquisa:', err));
});
</script>

<?php include '../includes/footer.php'; ?>