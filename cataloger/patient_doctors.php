<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'cataloger') {
    header("Location: ../index.php");
    exit;
}

$patient_id = $_GET['id'] ?? null;
if (!$patient_id) {
    header("Location: patients.php");
    exit;
}

// Fetch Patient
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'patient'");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    header("Location: patients.php?error=Paciente não encontrado");
    exit;
}

// Handle Remove
if (isset($_GET['remove'])) {
    $pd_id = $_GET['remove'];
    try {
        $stmt = $pdo->prepare("DELETE FROM patient_doctors WHERE id = ? AND patient_id = ?");
        $stmt->execute([$pd_id, $patient_id]);
        $success = "Médico desvinculado.";
    } catch (PDOException $e) {
        error_log("Erro remove_doctor: " . $e->getMessage());
        $error = "Erro ao remover médico.";
    }
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_doctor'])) {
    $doctor_id = $_POST['doctor_id'];
    
    if ($doctor_id) {
        // Check if patient already has a doctor with this specialty
        // 1. Get specialty of selected doctor
        $stmt = $pdo->prepare("SELECT specialty_id FROM doctors WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $new_specialty = $stmt->fetchColumn();

        // 2. Check if patient has any doctor with this specialty
        $sql = "SELECT COUNT(*) FROM patient_doctors pd 
                JOIN doctors d ON pd.doctor_id = d.id 
                WHERE pd.patient_id = ? AND d.specialty_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$patient_id, $new_specialty]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = "Este paciente já possui um médico desta especialidade.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO patient_doctors (patient_id, doctor_id) VALUES (?, ?)");
                $stmt->execute([$patient_id, $doctor_id]);
                $success = "Médico vinculado com sucesso!";
            } catch (PDOException $e) {
                error_log("Erro add_doctor_patient: " . $e->getMessage());
                $error = "Erro ao vincular médico.";
            }
        }
    }
}

// Fetch Assigned Doctors
$sql = "SELECT pd.id as pd_id, d.name, s.name as specialty 
        FROM patient_doctors pd 
        JOIN doctors d ON pd.doctor_id = d.id 
        JOIN specialties s ON d.specialty_id = s.id 
        WHERE pd.patient_id = ?
        ORDER BY s.name";
$stmt = $pdo->prepare($sql);
$stmt->execute([$patient_id]);
$assigned_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Doctors for Dropdown
$all_doctors = $pdo->query("SELECT d.id, d.name, s.name as specialty 
                            FROM doctors d 
                            JOIN specialties s ON d.specialty_id = s.id 
                            ORDER BY s.name, d.name")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h2>Gerenciar Médicos do Paciente</h2>
        <h4 class="text-muted"><?php echo htmlspecialchars($patient['name']); ?></h4>
        <a href="patients.php" class="btn btn-secondary mb-3">Voltar</a>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Adicionar Médico</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-10">
                        <select name="doctor_id" class="form-select" required>
                            <option value="">Selecione o Médico...</option>
                            <?php foreach($all_doctors as $d): ?>
                                <option value="<?php echo $d['id']; ?>">
                                    <?php echo htmlspecialchars($d['specialty'] . ' - ' . $d['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Apenas um médico por especialidade é permitido.</small>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_doctor" class="btn btn-primary w-100">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Médicos Vinculados</div>
            <div class="card-body">
                <?php if (count($assigned_doctors) > 0): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Especialidade</th>
                                <th>Médico</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($assigned_doctors as $ad): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ad['specialty']); ?></td>
                                    <td><?php echo htmlspecialchars($ad['name']); ?></td>
                                    <td>
                                        <a href="?id=<?php echo $patient_id; ?>&remove=<?php echo $ad['pd_id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Tem certeza que deseja desvincular este médico?')">
                                            Remover
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-muted my-3">Nenhum médico vinculado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
