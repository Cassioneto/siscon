<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_specialty'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO specialties (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = "Especialidade adicionada!";
        } catch (PDOException $e) {
            $error = "Erro ao adicionar: " . $e->getMessage();
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM specialties WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Especialidade removida!";
    } catch (PDOException $e) {
        $error = "Erro ao remover: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Gerenciar Especialidades</h2>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Voltar</a>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Nova Especialidade</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-auto">
                        <input type="text" name="name" class="form-control" placeholder="Nome da Especialidade" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" name="add_specialty" class="btn btn-primary">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Lista de Especialidades</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM specialties ORDER BY name");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td><a href='?delete={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Tem certeza?\")'>Excluir</a></td>";
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