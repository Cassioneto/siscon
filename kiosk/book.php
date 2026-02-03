<?php
require_once '../config.php';

// Security Check: Must be verified via SMS
if (!isset($_SESSION['kiosk_verified']) || !isset($_SESSION['kiosk_phone'])) {
    header("Location: index.php");
    exit;
}

$phone = $_SESSION['kiosk_phone'];
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$confirm = $_POST['confirm'] ?? false;
$doctor_id = $_POST['doctor_id'] ?? '';

// 1. Identify Patient
$stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND role = 'patient'");
$stmt->execute([$phone]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    header("Location: index.php?error=Paciente não encontrado. Dirija-se à recepção.");
    exit;
}

// 2. Fetch Assigned Doctors
$stmt = $pdo->prepare("SELECT d.*, s.name as specialty 
                       FROM patient_doctors pd 
                       JOIN doctors d ON pd.doctor_id = d.id 
                       JOIN specialties s ON d.specialty_id = s.id
                       WHERE pd.patient_id = ?");
$stmt->execute([$patient['id']]);
$assigned_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Patient's History
$stmt_hist = $pdo->prepare("SELECT a.*, d.name as doctor_name, s.name as specialty 
                           FROM appointments a 
                           JOIN doctors d ON a.doctor_id = d.id 
                           JOIN specialties s ON d.specialty_id = s.id 
                           WHERE a.user_id = ? 
                           ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$stmt_hist->execute([$patient['id']]);
$my_appointments = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

if (empty($assigned_doctors)) {
    header("Location: index.php?error=Você não possui médico vinculado. Dirija-se à recepção.");
    exit;
}

// Auto-select if only one
if (count($assigned_doctors) == 1 && empty($doctor_id)) {
    $doctor_id = $assigned_doctors[0]['id'];
}

// If no doctor selected yet (and multiple exist), show selection screen
if (empty($doctor_id)) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Selecione o Médico - Hospital Sys</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
        <style>
            body { background-color: #e9ecef; }
            .kiosk-container { max-width: 800px; margin: 30px auto; }
            .doctor-btn { height: 100px; font-size: 1.2rem; margin-bottom: 15px; }
        </style>
    </head>
    <body>
    <div class="container kiosk-container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h3>Olá, <?php echo htmlspecialchars(explode(' ', $patient['name'])[0]); ?></h3>
                <p class="mb-0">Com qual médico deseja agendar?</p>
            </div>
            <div class="card-body p-4">
                
                <?php if(!empty($my_appointments)): ?>
                <div class="mb-4">
                    <h5 class="text-secondary">Meus Agendamentos</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Médico</th>
                                    <th>Especialidade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($my_appointments as $appt): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($appt['appointment_date'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($appt['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appt['specialty']); ?></td>
                                    <td>
                                        <?php 
                                        $status_map = ['scheduled'=>'Agendado', 'cancelled'=>'Cancelado', 'completed'=>'Concluído'];
                                        $badge_map = ['scheduled'=>'bg-primary', 'cancelled'=>'bg-danger', 'completed'=>'bg-success'];
                                        $st = $appt['status'];
                                        ?>
                                        <span class="badge <?php echo $badge_map[$st]??'bg-secondary'; ?>">
                                            <?php echo $status_map[$st]??$st; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <hr>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                    <div class="d-grid gap-3">
                        <?php foreach($assigned_doctors as $doc): ?>
                            <button type="submit" name="doctor_id" value="<?php echo $doc['id']; ?>" class="btn btn-outline-primary doctor-btn">
                                <strong><?php echo htmlspecialchars($doc['specialty']); ?></strong><br>
                                Dr(a). <?php echo htmlspecialchars($doc['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <a href="index.php" class="btn btn-secondary w-100 mt-3">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// 3. Doctor Selected - Proceed to Booking
$doctor = null;
foreach ($assigned_doctors as $d) {
    if ($d['id'] == $doctor_id) {
        $doctor = $d;
        break;
    }
}

if (!$doctor) {
    header("Location: index.php?error=Médico inválido.");
    exit;
}

// 4. Handle Confirmation
if ($confirm && $date && $time) {
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
            $error = "Limite de atendimentos diários atingido para esta data. Escolha outra.";
        } else {
            // C. Check Time Slot (Availability)
            $stmt = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status = 'scheduled'");
            $stmt->execute([$doctor['id'], $date, $time]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Horário indisponível. Tente outro.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$patient['id'], $doctor['id'], $date, $time]);
                    header("Location: index.php?success=Agendamento realizado com sucesso!");
                    exit;
                } catch (PDOException $e) {
                    error_log("Erro book_appointment: " . $e->getMessage());
                    $error = "Erro interno ao realizar agendamento.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento - Hospital Sys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <style>
        body { background-color: #e9ecef; }
        .kiosk-container { max-width: 800px; margin: 30px auto; }
        .btn-time { margin: 5px; width: 100px; }
    </style>
</head>
<body>

<div class="container kiosk-container">
    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
            <h3>Olá, <?php echo htmlspecialchars(explode(' ', $patient['name'])[0]); ?></h3>
            <p class="mb-0">Agendamento com Dr(a). <?php echo htmlspecialchars($doctor['name']); ?></p>
        </div>
        <div class="card-body p-4">
            
            <?php if(!empty($my_appointments)): ?>
                <div class="mb-4">
                    <button class="btn btn-outline-info w-100" type="button" data-bs-toggle="collapse" data-bs-target="#historyCollapse" aria-expanded="false" aria-controls="historyCollapse">
                        Ver Meus Agendamentos (<?php echo count($my_appointments); ?>)
                    </button>
                    <div class="collapse mt-2" id="historyCollapse">
                        <div class="card card-body bg-light">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Data</th>
                                            <th>Hora</th>
                                            <th>Médico</th>
                                            <th>Especialidade</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($my_appointments as $appt): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($appt['appointment_date'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($appt['appointment_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appt['specialty']); ?></td>
                                            <td>
                                                <?php 
                                                $status_map = ['scheduled'=>'Agendado', 'cancelled'=>'Cancelado', 'completed'=>'Concluído'];
                                                $badge_map = ['scheduled'=>'bg-primary', 'cancelled'=>'bg-danger', 'completed'=>'bg-success'];
                                                $st = $appt['status'];
                                                ?>
                                                <span class="badge <?php echo $badge_map[$st]??'bg-secondary'; ?>">
                                                    <?php echo $status_map[$st]??$st; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                <strong>Dias de Atendimento:</strong> 
                <?php
                $stmt_days = $pdo->prepare("SELECT day_of_week FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week");
                $stmt_days->execute([$doctor['id']]);
                $days = $stmt_days->fetchAll(PDO::FETCH_COLUMN);
                $days_map = [0=>'Dom', 1=>'Seg', 2=>'Ter', 3=>'Qua', 4=>'Qui', 5=>'Sex', 6=>'Sáb'];
                $days_names = array_map(function($d) use ($days_map) { return $days_map[$d]; }, $days);
                echo implode(', ', $days_names);
                ?>
                <br>
                <strong>Limite Diário:</strong> <?php echo $doctor['daily_limit']; ?> pacientes.
            </div>

            <form method="POST">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                <input type="hidden" name="doctor_id" value="<?php echo htmlspecialchars($doctor['id']); ?>">
                <input type="hidden" name="confirm" value="1">
                
                <div class="row justify-content-center">
                    <div class="col-md-6 mb-4">
                        <label class="form-label h5">Escolha a Data</label>
                        <input type="date" name="date" class="form-control form-control-lg" 
                               value="<?php echo $date ? $date : date('Y-m-d'); ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label h5">Escolha a Hora</label>
                        <select name="time" class="form-select form-select-lg" required>
                            <option value="">Selecione...</option>
                            <?php
                            $start = strtotime('08:00');
                            $end = strtotime('18:00');
                            while ($start <= $end) {
                                $t = date('H:i', $start);
                                $sel = ($time == $t) ? 'selected' : '';
                                echo "<option value='$t' $sel>$t</option>";
                                $start = strtotime('+30 minutes', $start);
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg p-3">Confirmar Agendamento</button>
                    <a href="index.php" class="btn btn-secondary btn-lg">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>
