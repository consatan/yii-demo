<?php

$pdo = new PDO('mysql:host=192.168.100.99;dbname=yii2basic;charset=utf8mb4', 'root', 'root');
$stmt = $pdo->prepare('insert into supplier(name,code,t_status) value (:name, :code, :status)');

$name = $code = $status = null;
$stmt->bindParam(':name', $name);
$stmt->bindParam(':code', $code);
$stmt->bindParam(':status', $status);

$data = require __DIR__ . '/supplier.php';

$pdo->beginTransaction();
foreach ($data as $item) {
    $name = $item['name'];
    $code = $item['code'];
    $status = $item['t_status'];

    $stmt->execute();
}
$pdo->commit();
