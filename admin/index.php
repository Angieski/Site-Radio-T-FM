<?php
session_start();
include 'conexao.php';

// Verificar autenticação
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

unset($_SESSION['success']);
unset($_SESSION['error']);

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Operações de Adição
        if (isset($_POST['add_strea'])) {
            try {
                // Validar exibição e URL
                $exibicao = $_POST['exibicao'];
                $urlplay = trim($_POST['urlplay']);
                
                // Verificar se URL é obrigatório
                if (in_array($exibicao, ['ambos', 'player']) && empty($urlplay)) {
                    throw new Exception("URL do Player é obrigatória para esta exibição");
                }
        
                // Obter última ordem
                $ultima_ordem = $pdo->query("SELECT MAX(ordem) AS max_ordem FROM strea")->fetch()['max_ordem'];
                
                // Query de inserção
                $stmt = $pdo->prepare("INSERT INTO strea 
                    (nome, freq, cidade, endereco, tel, ordem, exibicao, urlplay) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    
                $stmt->execute([
                    $_POST['nome'],
                    $_POST['freq'],
                    $_POST['cidade'],
                    $_POST['endereco'],
                    $_POST['tel'],
                    $ultima_ordem + 1,
                    $exibicao,
                    $urlplay
                ]);
                
                $_SESSION['success'] = "Endereço adicionado!";
                header('Location: index.php');
                exit;
        
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: index.php');
                exit;
            }
        }elseif (isset($_POST['strea'])) {
            try {
                $exibicao = $_POST['exibicao'];
                $urlplay = $_POST['urlplay'];
                
                // Validação condicional
                if (in_array($exibicao, ['ambos', 'player']) && empty(trim($urlplay))) {
                    $_SESSION['error'] = "URL do Player é obrigatória para exibição em Player ou Ambos";
                    header('Location: index.php');
                    exit;
                }
        
                $stmt = $pdo->prepare("UPDATE strea SET 
                    nome = ?,
                    freq = ?,
                    cidade = ?,
                    endereco = ?,
                    tel = ?,
                    exibicao = ?,
                    urlplay = ? 
                    WHERE id = ?");
        
                $stmt->execute([
                    $_POST['nome'],
                    $_POST['freq'],
                    $_POST['cidade'],
                    $_POST['endereco'],
                    $_POST['tel'],
                    $exibicao,
                    $urlplay,     
                    $_POST['id']  
                ]);
        
                $_SESSION['success'] = "Endereço atualizado!";
                header('Location: index.php');
                exit;
        
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erro: " . $e->getMessage();
                header('Location: index.php');
                exit;
            }
        }elseif (isset($_POST['add_equipet'])) {
            $stmt = $pdo->prepare("INSERT INTO equipet (nome, cargo) VALUES (?, ?)");
            $stmt->execute([$_POST['nome'], $_POST['cargo']]);
            $success = "Membro adicionado!";
        } 
        elseif (isset($_POST['add_programas'])) {
            if (!isset($_POST['dia'])) {
                $error = "Campo 'dia' não recebido.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO programas (nome, inf, inicio, dia) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['nome'], $_POST['inf'], $_POST['inicio'], $_POST['dia']]);
                $success = "Programa adicionado!";
            }
        }

        // Operações de Atualização
        elseif (isset($_POST['programas'])) {
            if (!isset($_POST['dia'])) {
                $error = "Campo 'dia' não recebido.";
            } else {
                $stmt = $pdo->prepare("UPDATE programas SET nome = ?, inf = ?, inicio = ?, dia = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['nome'],
                    $_POST['inf'],
                    $_POST['inicio'],
                    $_POST['dia'],
                    $_POST['id']
                ]);
                $success = "Programa atualizado!";
            }
        }elseif (isset($_POST['equipet'])) {
            $stmt = $pdo->prepare("UPDATE equipet SET nome = ?, cargo = ? WHERE id = ?");
            $stmt->execute([
                $_POST['nome'],
                $_POST['cargo'],
                $_POST['id']
            ]);
            $success = "Membro atualizado!";
        }

        // Operação de Exclusão
        elseif (isset($_POST['delete_programa'])) {
            $stmt = $pdo->prepare("DELETE FROM programas WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $_SESSION['success'] = "Endereço excluído!";
            header('Location: index.php');
            exit;
        }elseif (isset($_POST['delete_strea'])) {
            $stmt = $pdo->prepare("DELETE FROM strea WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $_SESSION['success'] = "Endereço excluído!";
            header('Location: index.php'); 
            exit;
        }elseif (isset($_POST['delete_equipet'])) {
            $stmt = $pdo->prepare("DELETE FROM equipet WHERE id = ?");
            $stmt->execute([$_POST['id']]); 
            $_SESSION['success'] = "Membro excluído!";
            header('Location: index.php');
            exit;
        }
    if (isset($success)) {
        $_SESSION['success'] = $success;
        header('Location: index.php');
        exit;
    }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro: " . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}

// Obter dados após as operações
$strea = $pdo->query("SELECT id, nome, freq, cidade, endereco, tel, ordem, exibicao, urlplay FROM strea WHERE id NOT IN (1, 17) ORDER BY ordem DESC")->fetchAll();
$equipet = $pdo->query("SELECT id, nome, cargo FROM equipet")->fetchAll();
$programas = $pdo->query("SELECT id, nome, inf, inicio, dia FROM programas ORDER BY dia, inicio")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Painel Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <style>
        .btn-success { 
            padding: 6px 12px; 
            min-width: 40px;
        }
        
        .btn-danger {
            min-width: 40px;
            padding: 6px 12px;
        }

        .form-control-lg-mobile {
            padding: 0.8rem;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .nav-tabs .nav-item {
                flex: 1;
                text-align: center;
                font-size: 14px;
            }
            
            .nav-pills {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 8px;
            }
            
            .nav-pills .nav-item {
                flex-shrink: 0;
                margin-right: 8px;
            }
            
            .form-control {
                font-size: 16px !important;
            }
            
            .btn-mobile {
                padding: 10px 16px;
                font-size: 16px;
            }
            
            .h4-mobile {
                font-size: 1.25rem;
            }
        }
    </style>
    <div class="container mt-4">
        <div class="d-flex flex-column flex-md-row justify-content-between mb-4 gap-2">
            <h2 class="h4 h4-mobile mb-0">Painel Administrativo</h2>
            <a href="logout.php" class="btn btn-danger btn-mobile align-self-md-center">Sair</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <ul class="nav nav-tabs nav-justified flex-nowrap" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-2" 
                        data-bs-toggle="tab" 
                        data-bs-target="#strea"
                        role="tab"
                        aria-controls="strea">Endereços</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2" 
                        data-bs-toggle="tab" 
                        data-bs-target="#equipet"
                        role="tab"
                        aria-controls="equipet">Equipe</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2" 
                        data-bs-toggle="tab" 
                        data-bs-target="#programas"
                        role="tab"
                        aria-controls="programas">Programas</button>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <!-- Seção Endereços -->
            <div class="tab-pane fade show active" id="strea" role="tabpanel" aria-labelledby="strea-tab">
                <form method="POST" class="mb-4 p-3 border rounded bg-light">
                    <h5 class="mb-3">Adicionar Novo Endereço</h5>
                    <div class="row g-2">
                        <div class="col-12 col-md-3">
                            <select name="exibicao" class="form-control form-control-lg-mobile" required>
                                <option value="ambos">Ambos</option>
                                <option value="mapa">Somente Mapa</option>
                                <option value="player">Somente Player</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="text" name="nome" class="form-control form-control-lg-mobile" placeholder="Nome da Rádio (cidade)" required>
                        </div>
                        <div class="col-12 col-md-2">
                            <input type="text" name="freq" class="form-control form-control-lg-mobile" placeholder="Frequência" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <input type="text" name="cidade" class="form-control form-control-lg-mobile" placeholder="Cidade (para endereço)" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <input type="text" name="endereco" class="form-control form-control-lg-mobile" placeholder="Endereço" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="text" name="tel" class="form-control form-control-lg-mobile" placeholder="Telefone" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <input type="url" name="urlplay" class="form-control form-control-lg-mobile" placeholder="URL do Player">
                        </div>
                        <div class="col-12 col-md-1">
                            <button type="submit" name="add_strea" class="btn btn-primary w-100 py-2">+</button>
                        </div>
                    </div>
                </form>

                <?php foreach ($strea as $radio): ?>
                <form method="POST" class="mb-3 p-2 border rounded">
                    <input type="hidden" name="id" value="<?= $radio['id'] ?>">
                    <div class="row g-2 align-items-center">
                        <div class="col-6 col-md-4">
                            <input type="text" name="nome" value="<?= htmlspecialchars($radio['nome']) ?>" 
                                   class="form-control form-control-lg-mobile" placeholder="Nome">
                        </div>
                            <div class="col-md-2">
                                <input type="text" name="freq" value="<?= htmlspecialchars($radio['freq']) ?>" 
                                    class="form-control" placeholder="Frequência">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="cidade" value="<?= htmlspecialchars($radio['cidade']) ?>" class="form-control" placeholder="Cidade">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="endereco" value="<?= htmlspecialchars($radio['endereco']) ?>" class="form-control" placeholder="Endereço">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="tel" value="<?= htmlspecialchars($radio['tel']) ?>" class="form-control" placeholder="Telefone">
                            </div>
                            <div class="col-md-4">
                                <input type="url" name="urlplay" value="<?= htmlspecialchars($radio['urlplay']) ?>" 
                                    class="form-control" placeholder="URL do Player">
                            </div>
                            <div class="col-md-3">
                                <select name="exibicao" class="form-control">
                                    <option value="ambos" <?= $radio['exibicao'] == 'ambos' ? 'selected' : '' ?>>Ambos</option>
                                    <option value="mapa" <?= $radio['exibicao'] == 'mapa' ? 'selected' : '' ?>>Somente mapa</option>
                                    <option value="player" <?= $radio['exibicao'] == 'player' ? 'selected' : '' ?>>Somente player</option>
                                </select>
                            </div>
                            <div class="col-2 col-md-1">
                            <div class="d-flex gap-1">
                                <button type="submit" name="strea" class="btn btn-success">✓</button>
                                <button type="submit" name="delete_strea" 
                                        class="btn btn-danger"
                                        onclick="return confirm('Excluir esta rádio?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endforeach; ?>
            </div>
            
            <div class="tab-pane fade" id="equipet" role="tabpanel" aria-labelledby="equipet-tab">
                <form method="POST" class="mb-4 p-3 border rounded bg-light">
                    <h5 class="mb-3">Adicionar Membro</h5>
                    <div class="row g-2">
                        <div class="col-12 col-md-5">
                            <input type="text" name="nome" class="form-control form-control-lg-mobile" placeholder="Nome" required>
                        </div>
                        <div class="col-12 col-md-5">
                            <input type="text" name="cargo" class="form-control form-control-lg-mobile" placeholder="Cargo" required>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" name="add_equipet" class="btn btn-primary w-100 py-2">+</button>
                        </div>
                    </div>
                </form>
                    <?php foreach ($equipet as $membro): ?>
                        <form method="POST" class="mb-4 p-3 border rounded">
                            <input type="hidden" name="id" value="<?= $membro['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <input type="text" name="nome" value="<?= htmlspecialchars($membro['nome']) ?>" class="form-control" placeholder="Nome">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="cargo" value="<?= htmlspecialchars($membro['cargo']) ?>" class="form-control" placeholder="Cargo">
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex gap-1">
                                        <button type="submit" name="equipet" class="btn btn-success flex-grow-1">✓</button>
                                        <button type="submit" name="delete_equipet" class="btn btn-danger" 
                                            onclick="return confirm('Tem certeza que deseja excluir este membro?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endforeach; ?>
            </div>

            <div class="tab-pane fade" id="programas" role="tabpanel" aria-labelledby="programas-tab">
                <div class="nav-pills-scroll mb-3">
                    <ul class="nav nav-pills flex-nowrap overflow-auto">
                        <?php foreach(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $key => $dia): ?>
                        <li class="nav-item flex-shrink-0" role="presentation">
                            <button class="nav-link <?= $key == 0 ? 'active' : '' ?> px-4 py-2" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#dia-<?= $key ?>"
                                    type="button"
                                    role="tab"
                                    aria-controls="dia-<?= $key ?>">
                                <?= $dia ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="tab-content">
                    <?php foreach(range(0,6) as $dia): ?>
                        <div class="tab-pane fade <?= $dia == 0 ? 'show active' : '' ?>" id="dia-<?= $dia ?>" role="tabpanel" aria-labelledby="dia-<?= $dia ?>">
                        <div class="mb-4">
                            <h5>Programação de <?= ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][$dia] ?></h5>
                            
                            <form method="POST" class="mb-3 p-3 border rounded bg-light">
                                <div class="row g-3">
                                    <input type="hidden" name="dia" value="<?= $dia ?>">
                                    <div class="col-md-4">
                                        <input type="text" name="nome" class="form-control" placeholder="Nome do Programa" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="time" name="inicio" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <textarea name="inf" class="form-control" placeholder="Descrição" required></textarea>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" name="add_programas" class="btn btn-primary w-100">+</button>
                                    </div>
                                </div>
                            </form>

                            <?php 
                            $programas_dia = array_filter($programas, function($p) use ($dia) {
                                return $p['dia'] == $dia;
                            });
                            usort($programas_dia, function($a, $b) {
                                return strtotime($a['inicio']) - strtotime($b['inicio']);
                            });
                            ?>
                            
                            <?php foreach($programas_dia as $programa): ?>
                                <form method="POST" class="mb-3 p-3 border rounded">
                                    <input type="hidden" name="id" value="<?= $programa['id'] ?>">
                                    <input type="hidden" name="dia" value="<?= $programa['dia'] ?>">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-3">
                                            <input type="text" name="nome" value="<?= htmlspecialchars($programa['nome']) ?>" 
                                                class="form-control" placeholder="Nome">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="time" name="inicio" value="<?= htmlspecialchars($programa['inicio']) ?>" 
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <textarea name="inf" class="form-control" rows="1" placeholder="Descrição"><?= htmlspecialchars($programa['inf']) ?></textarea>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="submit" name="programas" class="btn btn-success w-100" title="Salvar">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="submit" name="delete_programa" class="btn btn-danger w-100" 
                                                    onclick="return confirm('Tem certeza que deseja excluir este programa?')" title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exibicaoSelect = document.querySelector('[name="exibicao"]');
            const urlplayInput = document.getElementById('urlplay');

            function toggleUrlRequired() {
                urlplayInput.required = exibicaoSelect.value !== 'mapa';
            }

            // Evento para formulário de adição
            exibicaoSelect.addEventListener('change', toggleUrlRequired);
            toggleUrlRequired(); // Executar no carregamento
        });

        // Para formulários de EDIÇÃO (cada linha)
        document.querySelectorAll('[name="exibicao"]').forEach(select => {
            select.addEventListener('change', function() {
                const formGroup = this.closest('.row');
                const urlInput = formGroup.querySelector('[name="urlplay"]');
                urlInput.required = this.value !== 'mapa';
            });
        });
    </script>
</body>
</html>