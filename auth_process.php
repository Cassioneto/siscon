<?php
require_once 'config.php';

$action = $_POST['action'] ?? '';

if ($action == 'register') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Todos os campos são obrigatórios.";
        header("Location: register.php");
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "As senhas não coincidem.";
        header("Location: register.php");
        exit;
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "E-mail já cadastrado.";
        header("Location: register.php");
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'patient')");
        $stmt->execute([$name, $email, $hashed_password]);
        $_SESSION['success'] = "Cadastro realizado com sucesso! Faça login.";
        header("Location: index.php");
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao cadastrar: " . $e->getMessage();
        header("Location: register.php");
    }

} elseif ($action == 'login') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } elseif ($user['role'] == 'attendant') {
            header("Location: attendant/dashboard.php");
        } else {
            // Patients don't log in here anymore? Or maybe they still can?
            // User asked for "Self service" zone, usually that's separate.
            // But if they login, where do they go?
            // The prompt says "em vez de ser zona de pacientes faça como zona de atendentes".
            // This implies the standard login is for staff.
            // But patients might still have an account?
            // I'll redirect patients to kiosk/index.php if they somehow login here, 
            // OR maybe disable patient login on the main form?
            // For now, I will redirect to kiosk or a simple profile page.
            // But the prompt implies the "Patient Zone" IS NOW "Attendant Zone".
            header("Location: kiosk/index.php"); 
        }
    } else {
        $_SESSION['error'] = "E-mail ou senha incorretos.";
        header("Location: index.php");
    }
} else {
    header("Location: index.php");
}
?>