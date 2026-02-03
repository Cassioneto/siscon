<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'cataloger') {
    header("Location: ../index.php");
    exit;
}

// 1. Select Patient if not provided
$patient_id = $_GET['patient_id'] ?? '';
$patient = null;
$assigned_doctors = [];
$doctor = null;
$doctor_id = $_GET['doctor_id'] ?? ($_POST['doctor_id'] ?? null);

if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'patient'");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        // Fetch Assigned Doctors
        $stmt = $pdo->prepare("SELECT d.*, s.name as specialty 
                               FROM patient_doctors pd 
                               JOIN doctors d ON pd.doctor_id = d.id 
                               JOIN specialties s ON d.specialty_id = s.id
                               WHERE pd.patient_id = ?");
        $stmt->execute([$patient_id]);
        $assigned_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If only one doctor, auto-select
        if (count($assigned_doctors) == 1 && !$doctor_id) {
            $doctor_id = $assigned_doctors[0]['id'];
        }
        
        // If doctor selected, fetch details
        if ($doctor_id) {
            foreach ($assigned_doctors as $d) {
                if ($d['id'] == $doctor_id) {
                    $doctor = $d;
                    break;
                }
            }
        }
    }
}

// Handle Booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $patient && $doctor) {
    $date = $_POST['date'];
    $time = $_POST['time'];

    // Validate Doctor Rules
    // A. Check Day of Week
    $dow = date('w', strtotime($date));
    $stmt_dow = $pdo->prepare("SELECT COUNT(*) FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?");
    $stmt_dow->execute([$doctor['id'], $dow]);
    
    if ($stmt_dow->fetchColumn() == 0) {
        $error = "O médico não atende neste dia da semana.";
    } else {
        // B. Check Daily Limit
        $stmt_limit = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'");
        $stmt_limit->execute([$doctor['id'], $date]);
        $current_count = $stmt_limit->fetchColumn();

        if ($current_count >= $doctor['daily_limit']) {
            $error = "Limite de atendimentos diários atingido ({$doctor['daily_limit']}) para esta data.";
        } else {
            // Check slot
            $stmt = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status = 'scheduled'");
            $stmt->execute([$doctor['id'], $date, $time]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Horário ocupado.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$patient['id'], $doctor['id'], $date, $time]);
                    $_SESSION['success'] = "Consulta agendada!";
                    header("Location: appointments.php");
                    exit;
                } catch (PDOException $e) {
                    error_log("Erro book_appointment_cataloger: " . $e->getMessage());
                    $error = "Erro interno ao agendar.";
                }
            }
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
            <div class="card-header bg-success text-white">Agendar Consulta (Catalogadora)</div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="GET" class="mb-3 position-relative" id="patientForm">
                    <label class="form-label">Selecione o Paciente</label>
                    
                    <input type="hidden" name="patient_id" id="patientIdInput" value="<?php echo htmlspecialchars($patient_id); ?>">
                    <input type="text" id="patientSearchInput" class="form-control" 
                           placeholder="Digite o telefone do paciente." 
                           autocomplete="off"
                           value="<?php echo $patient ? htmlspecialchars($patient['name'] . ' (' . $patient['phone'] . ')') : ''; ?>">
                    
                    <!-- Results Dropdown -->
                    <div id="patientSearchResults" class="list-group position-absolute w-100 shadow" style="display:none; z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                </form>

                <script>
                const searchInput = document.getElementById('patientSearchInput');
                const resultsDiv = document.getElementById('patientSearchResults');
                const idInput = document.getElementById('patientIdInput');
                const form = document.getElementById('patientForm');

                searchInput.addEventListener('input', function() {
                    const query = this.value;
                    idInput.value = ''; // Clear ID to force selection
                    
                    if (query.length < 2) {
                        resultsDiv.style.display = 'none';
                        return;
                    }

                    fetch('search_patients_json.php?q=' + encodeURIComponent(query))
                        .then(response => response.json())
                        .then(data => {
                            resultsDiv.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(item => {
                                    const a = document.createElement('a');
                                    a.href = '#';
                                    a.className = 'list-group-item list-group-item-action';
                                    a.innerHTML = `<strong>${item.name}</strong> <small class="text-muted">(${item.phone})</small>`;
                                    a.onclick = function(e) {
                                        e.preventDefault();
                                        searchInput.value = `${item.name} (${item.phone})`;
                                        idInput.value = item.id;
                                        resultsDiv.style.display = 'none';
                                        form.submit(); // Trigger reload to fetch doctors
                                    };
                                    resultsDiv.appendChild(a);
                                });
                                resultsDiv.style.display = 'block';
                            } else {
                                resultsDiv.style.display = 'none';
                            }
                        });
                });

                // Hide results when clicking outside
                document.addEventListener('click', function(e) {
                    if (e.target !== searchInput && e.target !== resultsDiv) {
                        resultsDiv.style.display = 'none';
                    }
                });
                </script>

                <?php if($patient): ?>
                    <hr>
                    <div class="alert alert-info">
                        <strong>Paciente:</strong> <?php echo htmlspecialchars($patient['name']); ?><br>
                        <strong>Médicos Vinculados:</strong> <?php echo count($assigned_doctors); ?>
                    </div>

                    <?php if(count($assigned_doctors) == 0): ?>
                        <div class="alert alert-warning">
                            Este paciente não tem médicos designados. <a href="patient_doctors.php?id=<?php echo $patient['id']; ?>">Vincular Médico</a>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">

                            <div class="mb-3">
                                <label class="form-label">Selecione o Médico</label>
                                <?php if(count($assigned_doctors) == 1): ?>
                                    <input type="hidden" name="doctor_id" value="<?php echo $assigned_doctors[0]['id']; ?>">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($assigned_doctors[0]['name'] . ' - ' . $assigned_doctors[0]['specialty']); ?>" readonly>
                                    <?php $doctor = $assigned_doctors[0]; // Ensure doctor is set for display logic below ?>
                                <?php else: ?>
                                    <select name="doctor_id" class="form-select" onchange="window.location.href='?patient_id=<?php echo $patient_id; ?>&doctor_id='+this.value" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach($assigned_doctors as $d): ?>
                                            <option value="<?php echo $d['id']; ?>" <?php echo ($doctor_id == $d['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($d['name'] . ' - ' . $d['specialty']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <?php if($doctor): ?>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        Dias de Atendimento: 
                                        <?php
                                        $stmt_days = $pdo->prepare("SELECT day_of_week FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week");
                                        $stmt_days->execute([$doctor['id']]);
                                        $days = $stmt_days->fetchAll(PDO::FETCH_COLUMN);
                                        $days_map = [0=>'Dom', 1=>'Seg', 2=>'Ter', 3=>'Qua', 4=>'Qui', 5=>'Sex', 6=>'Sáb'];
                                        $days_names = array_map(function($d) use ($days_map) { return $days_map[$d]; }, $days);
                                        echo implode(', ', $days_names);
                                        ?>
                                        | Limite: <?php echo $doctor['daily_limit']; ?>/dia
                                    </small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Data</label>
                                        <input type="date" name="date" id="dateInput" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                                        <div id="dateError" class="text-danger small mt-1" style="display:none;"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Hora</label>
                                        <input type="time" name="time" class="form-control" min="08:00" max="18:00" step="1800" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Confirmar Agendamento</button>

                                <?php
                                // Fetch blocked dates (Full limit reached)
                                $stmt_full = $pdo->prepare("SELECT appointment_date FROM appointments WHERE doctor_id = ? AND appointment_date >= CURDATE() AND status != 'cancelled' GROUP BY appointment_date HAVING COUNT(*) >= ?");
                                $stmt_full->execute([$doctor['id'], $doctor['daily_limit']]);
                                $full_dates = $stmt_full->fetchAll(PDO::FETCH_COLUMN);
                                ?>
                                <script>
                                    const allowedDays = <?php echo json_encode($days); ?>;
                                    const fullDates = <?php echo json_encode($full_dates); ?>;
                                    const dateInput = document.getElementById('dateInput');
                                    const errorDiv = document.getElementById('dateError');

                                    dateInput.addEventListener('input', function() {
                                        if (!this.value) return;

                                        // Parse date safely (YYYY-MM-DD to local date)
                                        const parts = this.value.split('-');
                                        const date = new Date(parts[0], parts[1] - 1, parts[2]);
                                        const dayOfWeek = date.getDay();

                                        let error = '';

                                        if (!allowedDays.includes(dayOfWeek)) {
                                            error = 'O médico não atende neste dia da semana.';
                                        } else if (fullDates.includes(this.value)) {
                                            error = 'Agenda lotada para esta data.';
                                        }

                                        if (error) {
                                            errorDiv.innerText = error;
                                            errorDiv.style.display = 'block';
                                            this.value = ''; // Reset
                                        } else {
                                            errorDiv.style.display = 'none';
                                        }
                                    });
                                </script>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
