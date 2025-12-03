<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "raditfm_site";

$cidades_radio = [];

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt_player = $pdo->query("SELECT id, nome, nome_fant, freq, cidade, uf, tel, endereco, prog, urlplay, metada_url 
                           FROM strea 
                           WHERE id != 17 AND exibicao IN ('player', 'ambos')
                           ORDER BY ordem DESC");
    $radios_player = $stmt_player->fetchAll(PDO::FETCH_ASSOC);

    $stmt_map = $pdo->query("SELECT id, nome, nome_fant, freq, cidade, uf, tel, endereco, prog 
                        FROM strea 
                        WHERE id NOT IN (1, 17) AND exibicao IN ('mapa', 'ambos')
                        ORDER BY ordem DESC");
    $radios_map = $stmt_map->fetchAll(PDO::FETCH_ASSOC);

    $stmt_programas = $pdo->query("SELECT id, nome, inicio, inf, dia FROM programas WHERE status = '1' ORDER BY dia, inicio");
    $programas = $stmt_programas->fetchAll(PDO::FETCH_ASSOC);

    $stmt_equipe = $pdo->query("SELECT * FROM equipet");
    $equipe = $stmt_equipe->fetchAll(PDO::FETCH_ASSOC);

    $stmt_cidades = $pdo->query("SELECT DISTINCT cidade FROM strea WHERE cidade IS NOT NULL");
    $cidades_radio = $stmt_cidades->fetchAll(PDO::FETCH_COLUMN);

    $programas_por_dia = array_fill(0, 7, []);
    foreach ($programas as $programa) {
        $programas_por_dia[$programa['dia']][] = $programa;
    }

} catch (PDOException $e) {
    $cidades_radio = [];
    die("Erro de conexão: " . $e->getMessage());
}
?>