<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit;
}

$email = $_POST['email'] ?? '';
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$confirm = $_POST['confirm'] ?? false;

// 1. Identify Patient
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'patient'");
$stmt->execute([$email]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    header("Location: index.php?error=Paciente não encontrado. Dirija-se à recepção.");
    exit;
}

// 2. Check Assigned Doctor
if (!$patient['doctor_id']) {
    header("Location: index.php?error=Você não possui médico vinculado. Dirija-se à recepção.");
    exit;
}

$doctor_stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
$doctor_stmt->execute([$patient['doctor_id']]);
$doctor = $doctor_stmt->fetch(PDO::FETCH_ASSOC);

// 3. Handle Confirmation
if ($confirm && $date && $time) {
    // Check availability again
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
            $error = "Erro no sistema: " . $e->getMessage();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <h3>Olá, <?php echo explode(' ', $patient['name'])[0]; ?></h3>
            <p class="mb-0">Agendamento com Dr(a). <?php echo $doctor['name']; ?></p>
        </div>
        <div class="card-body p-4">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
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

</body>
</html>
