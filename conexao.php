<?php

$servername = "localhost";
$username = "raditfm_site";
$password = "Kakaka11*Ma";
$dbname = "raditfm_site";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt_player = $pdo->query("
    SELECT id, nome, nome_fant, freq, cidade, uf, tel, endereco, prog, urlplay FROM strea WHERE id != 17 ORDER BY ordem DESC");
    $radios_player = $stmt_player->fetchAll(PDO::FETCH_ASSOC);

    $stmt_map = $pdo->query("SELECT id, nome, nome_fant, freq, cidade, uf, tel, endereco, prog FROM strea WHERE id NOT IN (1, 17) ORDER BY ordem DESC");
    $radios_map = $stmt_map->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>