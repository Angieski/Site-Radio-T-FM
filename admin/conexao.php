<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "raditfm_site";

try {
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>