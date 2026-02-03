<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'cataloger') {
    header("Location: ../index.php");
    exit;
}

// Handle Add Patient
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // OWASP A01: CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erro de segurança (CSRF). Recarregue a página.");
    }

    if (isset($_POST['add_patient'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']); 
        $doctor_id = $_POST['doctor_id'] ?: null;
        
        // Generate dummy password for patient
        $password = password_hash('123456', PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'patient', ?)");
            $stmt->execute([$name, $email, $password, $phone]);
            $patient_id = $pdo->lastInsertId();

            if ($doctor_id) {
                $stmt = $pdo->prepare("INSERT INTO patient_doctors (patient_id, doctor_id) VALUES (?, ?)");
                $stmt->execute([$patient_id, $doctor_id]);
            }

            $pdo->commit();
            $success = "Paciente cadastrado!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erro add_patient: " . $e->getMessage());
            $error = "Erro interno ao cadastrar paciente.";
        }
    }

    // Handle Edit Patient
    if (isset($_POST['edit_patient'])) {
        $id = $_POST['edit_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = 'patient'");
            $stmt->execute([$name, !empty($email) ? $email : null, $phone, $id]);
            $success = "Paciente atualizado!";
        } catch (PDOException $e) {
            error_log("Erro edit_patient: " . $e->getMessage());
            $error = "Erro ao atualizar paciente.";
        }
    }
}

// Fetch Doctors for dropdown (Add Form)
$doctors = $pdo->query("SELECT d.id, d.name, s.name as specialty 
                        FROM doctors d 
                        JOIN specialties s ON d.specialty_id = s.id 
                        ORDER BY s.name, d.name")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Gerenciar Pacientes</h2>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Voltar</a>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Add Patient Form -->
        <div class="card mb-4">
            <div class="card-header">Novo Paciente</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" placeholder="Nome Completo" required>
                    </div>
                    <div class="col-md-3">
                        <input type="tel" name="phone" class="form-control" placeholder="Telefone" required>
                    </div>
                    <div class="col-md-3">
                        <input type="email" name="email" class="form-control" placeholder="E-mail (Opcional)">
                    </div>
                    <div class="col-md-2">
                        <select name="doctor_id" class="form-select">
                            <option value="">Médico Inicial...</option>
                            <?php foreach($doctors as $d): ?>
                                <option value="<?php echo $d['id']; ?>">
                                    <?php echo htmlspecialchars($d['specialty'] . ' - ' . $d['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="add_patient" class="btn btn-primary w-100">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- List Patients -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Lista de Pacientes</span>
                <input type="text" id="searchPatient" class="form-control form-control-sm w-25" placeholder="Buscar por nome ou telefone...">
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Médicos Designados</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="patientsTableBody">
                            <?php
                            // Fetch patients and concatenate doctor names
                            $sql = "SELECT u.*, 
                                    GROUP_CONCAT(CONCAT(d.name, ' (', s.name, ')') SEPARATOR ', ') as doctors_list 
                                    FROM users u 
                                    LEFT JOIN patient_doctors pd ON u.id = pd.patient_id 
                                    LEFT JOIN doctors d ON pd.doctor_id = d.id 
                                    LEFT JOIN specialties s ON d.specialty_id = s.id
                                    WHERE u.role = 'patient' 
                                    GROUP BY u.id
                                    ORDER BY u.name";
                            $stmt = $pdo->query($sql);
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $name = htmlspecialchars($row['name'] ?? '');
                                $phone = htmlspecialchars($row['phone'] ?? '');
                                $email = htmlspecialchars($row['email'] ?? '');
                                $doctors_list = htmlspecialchars($row['doctors_list'] ?: 'Nenhum');
                                
                                echo "<tr>";
                                echo "<td class='fw-bold'>{$name}</td>";
                                echo "<td>{$phone}</td>";
                                echo "<td>{$email}</td>";
                                echo "<td><small class='text-muted'>{$doctors_list}</small></td>";
                                echo "<td>
                                        <div class='btn-group' role='group'>
                                            <button class='btn btn-sm btn-outline-warning edit-btn' 
                                                    data-id='{$row['id']}' 
                                                    data-name='{$name}' 
                                                    data-phone='{$phone}' 
                                                    data-email='{$email}'>Editar</button>
                                            <a href='patient_doctors.php?id={$row['id']}' class='btn btn-sm btn-outline-info'>Médicos</a>
                                            <a href='book.php?patient_id={$row['id']}' class='btn btn-sm btn-outline-success'>Agendar</a>
                                        </div>
                                      </td>";
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

<!-- Edit Patient Modal -->
<div class="modal fade" id="editPatientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="mb-3">
                        <label>Nome</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Telefone</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="edit_patient" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('searchPatient').addEventListener('keyup', function() {
    let query = this.value;
    fetch('search_patients.php?q=' + query)
        .then(response => response.text())
        .then(data => {
            document.getElementById('patientsTableBody').innerHTML = data;
            attachEditHandlers(); // Re-attach events to new buttons
        });
});

function attachEditHandlers() {
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_phone').value = this.dataset.phone;
            document.getElementById('edit_email').value = this.dataset.email;
            
            new bootstrap.Modal(document.getElementById('editPatientModal')).show();
        });
    });
}

// Initial attach
attachEditHandlers();
</script>

<?php include '../includes/footer.php'; ?>
