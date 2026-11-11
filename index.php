<?php
// ==========================================
// AULA 01: O CÓDIGO SPAGHETTI (Tudo misturado)
// ==========================================

// 1. CONEXÃO COM O BANCO DE DADOS E CRIAÇÃO DA TABELA (Acoplamento de Infraestrutura)
$dbFile = __DIR__ . '/tasks.sqlite';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    descricao TEXT,
    data_vencimento DATE NOT NULL,
    responsavel TEXT NOT NULL,
    done INTEGER DEFAULT 0
)");

// 2. LÓGICA DE NEGÓCIO E CONTROLE DE REQUISIÇÕES MISTURADOS
$error = '';

// Criar nova tarefa
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['title'])
    && isset($_POST['descricao'])
    && isset($_POST['data_vencimento'])
    && isset($_POST['responsavel'])
) {
    $title = trim($_POST['title']);
    $descricao = trim($_POST['descricao']);
    $responsavel = trim($_POST['responsavel']);
    $data_vencimento = trim($_POST['data_vencimento']);

    if (empty($title)) {
        $error = "O título da tarefa não pode estar vazio!";
    } elseif (empty($responsavel)) {
        $error = "O responsável da tarefa não pode estar vazio!";
    } elseif (empty($data_vencimento)) {
        $error = "A data de vencimento da tarefa não pode estar vazia!";
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tasks (title, descricao, data_vencimento, responsavel)
             VALUES (:title, :descricao, :data_vencimento, :responsavel)"
        );
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':descricao', $descricao);
        $stmt->bindValue(':data_vencimento', $data_vencimento);
        $stmt->bindValue(':responsavel', $responsavel);
        $stmt->execute();

        header("Location: index.php");
        exit;
    }
}

// Concluir ou excluir tarefa
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    
    if ($_GET['action'] === 'complete') {
        $pdo->exec("UPDATE tasks SET done = 1 WHERE id = $id");
    } elseif ($_GET['action'] === 'delete') {
        $pdo->exec("DELETE FROM tasks WHERE id = $id");
    }
    
    header("Location: index.php");
    exit;
}

// 3. BUSCA DE DADOS MISTURADA COM A VISUALIZAÇÃO
$stmt = $pdo->query("SELECT * FROM tasks ORDER BY id DESC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Master - Spaghetti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            min-height: 100vh;
            padding-top: 32px;
        }
        .dashboard-shell {
            max-width: 1200px;
            margin: 0 auto;
        }
        .topbar {
            background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);
            color: white;
            border-radius: 18px 18px 0 0;
        }
        .summary-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            height: 100%;
        }
        .summary-card .card-body {
            padding: 1.25rem 1.25rem;
        }
        .task-panel {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .table thead th {
            background: #f8fafc;
            font-weight: 700;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
        }
        .table tbody tr.done td {
            text-decoration: line-through;
            color: #94a3b8;
            background: #f8fafc;
        }
        .badge-status {
            font-size: 0.72rem;
            padding: 0.45rem 0.7rem;
        }
        .actions a {
            text-decoration: none;
            font-size: 1.25rem;
        }
    </style>
</head>
<body>

<div class="container dashboard-shell">
    <div class="card task-panel overflow-hidden">
        <div class="topbar p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1 class="h3 mb-0 fw-bold text-white">Task Master</h1>
                    <small class="text-light opacity-75">Painel de tarefas</small>
                </div>
                <button class="btn btn-light btn-sm px-3 fw-semibold">+ Nova tarefa</button>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card summary-card bg-primary-subtle border-primary-subtle">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Total</p>
                                    <h3 class="mb-0 fw-bold"><?php echo count($tasks); ?></h3>
                                </div>
                                <span class="badge bg-primary rounded-pill p-2 fs-6">📋</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card bg-success-subtle border-success-subtle">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Concluídas</p>
                                    <h3 class="mb-0 fw-bold"><?php echo count(array_filter($tasks, fn($task) => (int) $task['done'] === 1)); ?></h3>
                                </div>
                                <span class="badge bg-success rounded-pill p-2 fs-6">✅</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card bg-warning-subtle border-warning-subtle">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Pendentes</p>
                                    <h3 class="mb-0 fw-bold"><?php echo count(array_filter($tasks, fn($task) => (int) $task['done'] === 0)); ?></h3>
                                </div>
                                <span class="badge bg-warning rounded-pill p-2 fs-6">🕒</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="row g-3 mb-4 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Título</label>
                    <input type="text" class="form-control" name="title" placeholder="O que precisa ser feito?" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Responsável</label>
                    <input type="text" class="form-control" name="responsavel" placeholder="Quem fará esta tarefa?" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Vencimento</label>
                    <input type="date" class="form-control" name="data_vencimento">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Descrição</label>
                    <input type="text" class="form-control" name="descricao" placeholder="Descrição da tarefa" autocomplete="off">
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Título</th>
                            <th scope="col">Responsável</th>
                            <th scope="col">Vencimento</th>
                            <th scope="col">Descrição</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr class="<?php echo $task['done'] ? 'done' : ''; ?>">
                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><?php echo htmlspecialchars($task['responsavel']); ?></td>
                                <td><?php echo htmlspecialchars($task['data_vencimento']); ?></td>
                                <td><?php echo htmlspecialchars($task['descricao']); ?></td>
                                <td class="text-center">
                                    <?php if ((int) $task['done'] === 1): ?>
                                        <span class="badge bg-success badge-status">Concluída</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark badge-status">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions text-center">
                                    <?php if (!$task['done']): ?>
                                        <a href="?action=complete&id=<?php echo $task['id']; ?>" title="Concluir" class="me-2">✅</a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo $task['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir esta tarefa?');" title="Excluir">❌</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
