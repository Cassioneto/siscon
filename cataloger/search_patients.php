<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'cataloger') {
    http_response_code(403);
    exit;
}

$search = $_GET['q'] ?? '';

$sql = "SELECT u.*, 
        GROUP_CONCAT(CONCAT(d.name, ' (', s.name, ')') SEPARATOR ', ') as doctors_list 
        FROM users u 
        LEFT JOIN patient_doctors pd ON u.id = pd.patient_id 
        LEFT JOIN doctors d ON pd.doctor_id = d.id 
        LEFT JOIN specialties s ON d.specialty_id = s.id
        WHERE u.role = 'patient' ";

if (!empty($search)) {
    $sql .= " AND (u.name LIKE :search OR u.phone LIKE :search OR u.email LIKE :search) ";
}

$sql .= " GROUP BY u.id ORDER BY u.name";

$stmt = $pdo->prepare($sql);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->execute();

$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($patients as $row) {
    $name = htmlspecialchars($row['name'] ?? '');
    $phone = htmlspecialchars($row['phone'] ?? '');
    $email = htmlspecialchars($row['email'] ?? '');
    $doctors_list = htmlspecialchars($row['doctors_list'] ?: 'Nenhum');
    
    echo "<tr>";
    echo "<td>{$name}</td>";
    echo "<td>{$phone}</td>";
    echo "<td>{$email}</td>";
    echo "<td><small>{$doctors_list}</small></td>";
    echo "<td>
            <button class='btn btn-sm btn-warning edit-btn' 
                    data-id='{$row['id']}' 
                    data-name='{$name}' 
                    data-phone='{$phone}' 
                    data-email='{$email}'>Editar</button>
            <a href='patient_doctors.php?id={$row['id']}' class='btn btn-sm btn-info text-white'>Gerenciar Médicos</a>
            <a href='book.php?patient_id={$row['id']}' class='btn btn-sm btn-success'>Agendar</a>
          </td>";
    echo "</tr>";
}
