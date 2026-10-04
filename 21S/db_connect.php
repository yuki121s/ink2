<?php
// ===== ตั้งค่าฐานข้อมูล (บน Shared Hosting ให้แก้เป็นค่าที่โฮสต์ให้มา) =====
$host   = 'localhost';
$dbname = '67';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,   // ใช้ Prepared Statement จริงของ MySQL
    ]);
} catch (PDOException $e) {
    die('เชื่อมต่อฐานข้อมูลไม่สำเร็จ กรุณาตรวจสอบค่าใน db_connect.php');
}

require_once __DIR__ . '/functions.php';
