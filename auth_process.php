<?php
require_once 'config.php';

$action = $_POST['action'] ?? '';

// OWASP A01: Broken Access Control (CSRF Protection)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erro de segurança (CSRF). Por favor, recarregue a página e tente novamente.");
    }
}

if ($action == 'register') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($phone) || empty($password)) {
        $_SESSION['error'] = "Nome, Telefone e Senha são obrigatórios.";
        header("Location: register.php");
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "As senhas não coincidem.";
        header("Location: register.php");
        exit;
    }

    // Check if phone exists (Unique for patients ideally)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Telefone já cadastrado.";
        header("Location: register.php");
        exit;
    }
    
    // Check if email exists (if provided)
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "E-mail já cadastrado.";
            header("Location: register.php");
            exit;
        }
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'patient')");
        $stmt->execute([$name, !empty($email) ? $email : null, $phone, $hashed_password]);
        $_SESSION['success'] = "Cadastro realizado com sucesso! Faça login.";
        header("Location: index.php");
    } catch (PDOException $e) {
        error_log("Erro cadastro: " . $e->getMessage());
        $_SESSION['error'] = "Erro interno ao cadastrar. Tente novamente.";
        header("Location: register.php");
    }

} elseif ($action == 'login') {
    $login_input = trim($_POST['email']); // Can be email or phone
    $password = $_POST['password'];

    // Try to find by Email OR Phone
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$login_input, $login_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } elseif ($user['role'] == 'cataloger') {
            header("Location: cataloger/dashboard.php");
        } else {
            // Patients are redirected to kiosk/index.php if they login here.
            header("Location: kiosk/index.php"); 
        }
    } else {
        $_SESSION['error'] = "Credenciais incorretas (Email/Telefone ou Senha).";
        header("Location: index.php");
    }
} else {
    header("Location: index.php");
}
?>