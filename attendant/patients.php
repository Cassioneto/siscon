<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'attendant') {
    header("Location: ../index.php");
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_patient'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']); // assuming we add phone column or use existing if any (users table structure check?)
        // users table: name, email, password, role, created_at, doctor_id
        // I should probably add 'phone' to users table too if I want to capture it, but for now I'll stick to schema or add it.
        // User didn't ask for phone, but 'kiosk' usually needs it. I will add phone to users table.
        $doctor_id = $_POST['doctor_id'] ?: null;
        
        // Generate dummy password for patient
        $password = password_hash('123456', PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, doctor_id) VALUES (?, ?, ?, 'patient', ?)");
            $stmt->execute([$name, $email, $password, $doctor_id]);
            $success = "Paciente cadastrado!";
        } catch (PDOException $e) {
            $error = "Erro: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_patient'])) {
        $id = $_POST['user_id'];
        $doctor_id = $_POST['doctor_id'] ?: null;
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET doctor_id = ? WHERE id = ?");
            $stmt->execute([$doctor_id, $id]);
            $success = "Médico vinculado atualizado!";
        } catch (PDOException $e) {
            $error = "Erro: " . $e->getMessage();
        }
    }
}

$doctors = $pdo->query("SELECT * FROM doctors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Gerenciar Pacientes</h2>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Voltar</a>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Add Patient Form -->
        <div class="card mb-4">
            <div class="card-header">Novo Paciente</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control" placeholder="Nome Completo" required>
                    </div>
                    <div class="col-md-4">
                        <input type="email" name="email" class="form-control" placeholder="E-mail" required>
                    </div>
                    <div class="col-md-3">
                        <select name="doctor_id" class="form-select" required>
                            <option value="">Selecione Médico Responsável...</option>
                            <?php foreach($doctors as $d): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
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
            <div class="card-header">Lista de Pacientes</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Médico Responsável</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT u.*, d.name as doctor_name 
                                FROM users u 
                                LEFT JOIN doctors d ON u.doctor_id = d.id 
                                WHERE u.role = 'patient' 
                                ORDER BY u.name";
                        $stmt = $pdo->query($sql);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>{$row['name']}</td>";
                            echo "<td>{$row['email']}</td>";
                            echo "<td>
                                    <form method='POST' class='d-flex'>
                                        <input type='hidden' name='user_id' value='{$row['id']}'>
                                        <input type='hidden' name='update_patient' value='1'>
                                        <select name='doctor_id' class='form-select form-select-sm me-2'>
                                            <option value=''>Sem médico</option>";
                                            foreach($doctors as $d) {
                                                $selected = ($row['doctor_id'] == $d['id']) ? 'selected' : '';
                                                echo "<option value='{$d['id']}' $selected>{$d['name']}</option>";
                                            }
                            echo "      </select>
                                        <button type='submit' class='btn btn-sm btn-outline-primary'>Salvar</button>
                                    </form>
                                  </td>";
                            echo "<td><a href='book.php?patient_id={$row['id']}' class='btn btn-sm btn-success'>Agendar</a></td>";
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