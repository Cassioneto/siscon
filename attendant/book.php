<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'attendant') {
    header("Location: ../index.php");
    exit;
}

// 1. Select Patient if not provided
$patient_id = $_GET['patient_id'] ?? '';
$patient = null;

if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'patient'");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 2. Fetch Assigned Doctor logic
// The user said "Each patient has a designated doctor".
// So if patient is selected, we ONLY show slots for that doctor?
// Or can we override? "só pode ser alterado por atendentes" refers to the *designation*, not necessarily the appointment?
// But usually "designated doctor" means "my doctor".
// I will assume for booking, we default to the designated doctor.
$doctor_id = $patient['doctor_id'] ?? '';
$doctor_name = '';

if ($doctor_id) {
    $stmt = $pdo->prepare("SELECT name FROM doctors WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $doctor_name = $stmt->fetchColumn();
}

// Handle Booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $p_id = $_POST['patient_id'];
    $d_id = $_POST['doctor_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    // Check slot
    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status = 'scheduled'");
    $stmt->execute([$d_id, $date, $time]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Horário ocupado.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$p_id, $d_id, $date, $time]);
            $_SESSION['success'] = "Consulta agendada!";
            header("Location: appointments.php");
            exit;
        } catch (PDOException $e) {
            $error = "Erro: " . $e->getMessage();
        }
    }
}

// Fetch all patients for selection
$patients = $pdo->query("SELECT * FROM users WHERE role = 'patient' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-success text-white">Agendar Consulta (Atendente)</div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="GET" class="mb-3">
                    <label class="form-label">Selecione o Paciente</label>
                    <select name="patient_id" class="form-select" onchange="this.form.submit()" required>
                        <option value="">Selecione...</option>
                        <?php foreach($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $patient_id == $p['id'] ? 'selected' : ''; ?>>
                                <?php echo $p['name']; ?> (<?php echo $p['email']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if($patient): ?>
                    <hr>
                    <form method="POST">
                        <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                        
                        <div class="alert alert-info">
                            <strong>Paciente:</strong> <?php echo $patient['name']; ?><br>
                            <strong>Médico Designado:</strong> <?php echo $doctor_name ? $doctor_name : 'Nenhum (Vincule na tela de pacientes)'; ?>
                        </div>

                        <?php if($doctor_id): ?>
                            <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Data</label>
                                    <input type="date" name="date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Hora</label>
                                    <input type="time" name="time" class="form-control" min="08:00" max="18:00" step="1800" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Confirmar Agendamento</button>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Este paciente não tem médico designado. Por favor, vá em <a href="patients.php">Gerenciar Pacientes</a> para vincular um médico.
                            </div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>