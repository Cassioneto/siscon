<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

// Handle Add Staff
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role']; // admin or attendant

    if (!empty($name) && !empty($email) && !empty($password)) {
        // Check email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "E-mail já existe.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed, $role]);
                $success = "Usuário adicionado!";
            } catch (PDOException $e) {
                $error = "Erro: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($id != $_SESSION['user_id']) { // Prevent self-delete
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Usuário removido!";
        } catch (PDOException $e) {
            $error = "Erro: " . $e->getMessage();
        }
    } else {
        $error = "Você não pode se excluir.";
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Gerenciar Usuários (Staff)</h2>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Voltar</a>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Novo Usuário</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" placeholder="Nome" required>
                    </div>
                    <div class="col-md-3">
                        <input type="email" name="email" class="form-control" placeholder="E-mail" required>
                    </div>
                    <div class="col-md-3">
                        <input type="password" name="password" class="form-control" placeholder="Senha" required>
                    </div>
                    <div class="col-md-2">
                        <select name="role" class="form-select">
                            <option value="attendant">Atendente</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="add_staff" class="btn btn-primary w-100">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Lista de Usuários (Admin/Atendentes)</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Função</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM users WHERE role IN ('admin', 'attendant') ORDER BY name");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>{$row['name']}</td>";
                            echo "<td>{$row['email']}</td>";
                            echo "<td><span class='badge bg-info'>{$row['role']}</span></td>";
                            echo "<td>";
                            if ($row['id'] != $_SESSION['user_id']) {
                                echo "<a href='?delete={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Tem certeza?\")'>Excluir</a>";
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