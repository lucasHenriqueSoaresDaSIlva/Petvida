<?php
requireRole(['admin']);

$db = dbConnect();
$user = currentUser();
$page = $_GET['page'] ?? 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? 'tutor');

    if ($name && $email && $password) {
        $stmt = $db->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $role]);
        setFlash('success', 'Usuário cadastrado com sucesso.');
    } else {
        setFlash('error', 'Preencha os campos do usuário.');
    }
    redirect('users');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vet_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $crmv = trim($_POST['crmv'] ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');
    $dias = trim($_POST['dias_atendimento'] ?? '');
    $horarios = trim($_POST['horarios_atendimento'] ?? '');

    if ($name && $email && $password && $crmv) {
        $userStmt = $db->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)');
        $userStmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), '', 'veterinario']);
        $userId = $db->lastInsertId();
        $stmt = $db->prepare('INSERT INTO veterinarios (user_id, crmv, especialidade, dias_atendimento, horarios_atendimento) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $crmv, $especialidade, $dias, $horarios]);
        setFlash('success', 'Veterinário cadastrado com sucesso.');
    } else {
        setFlash('error', 'Preencha os campos do veterinário.');
    }
    redirect('vets');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_submit'])) {
    $nome = trim($_POST['nome'] ?? '');
    $duracao = (int) ($_POST['duracao'] ?? 0);
    $valor = (float) ($_POST['valor'] ?? 0);

    if ($nome) {
        $stmt = $db->prepare('INSERT INTO servicos (nome, duracao, valor) VALUES (?, ?, ?)');
        $stmt->execute([$nome, $duracao, $valor]);
        setFlash('success', 'Serviço cadastrado com sucesso.');
    } else {
        setFlash('error', 'Informe o nome do serviço.');
    }
    redirect('services');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notice_submit'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $data = trim($_POST['data_publicacao'] ?? date('Y-m-d'));

    if ($titulo && $mensagem) {
        $stmt = $db->prepare('INSERT INTO avisos (titulo, mensagem, data_publicacao) VALUES (?, ?, ?)');
        $stmt->execute([$titulo, $mensagem, $data]);
        setFlash('success', 'Aviso publicado com sucesso.');
    } else {
        setFlash('error', 'Preencha o título e a mensagem.');
    }
    redirect('notices');
}

$users = $db->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();
$vets = $db->query('SELECT v.*, u.name FROM veterinarios v JOIN users u ON u.id = v.user_id ORDER BY u.name')->fetchAll();
$services = $db->query('SELECT * FROM servicos ORDER BY nome')->fetchAll();
$notices = $db->query('SELECT * FROM avisos ORDER BY data_publicacao DESC')->fetchAll();
$vacinas = $db->query('SELECT * FROM vacinas ORDER BY nome')->fetchAll();

switch ($page) {
    case 'users':
        $html = '<section class="page-header"><div><span class="eyebrow">Administração</span><h1>Usuários</h1></div></section>';
        $html .= '<div class="card"><h3>Cadastrar usuário</h3><form method="post" class="grid-form"><input type="hidden" name="user_submit" value="1"><div class="form-grid"><label><span>Nome</span><input type="text" name="name" required></label><label><span>E-mail</span><input type="email" name="email" required></label><label><span>Telefone</span><input type="tel" name="phone"></label><label><span>Senha</span><input type="password" name="password" required></label><label><span>Tipo</span><select name="role"><option value="tutor">Tutor</option><option value="veterinario">Veterinário</option><option value="admin">Administrador</option></select></label></div><button type="submit" class="primary-button">Salvar usuário</button></form></div>';
        $html .= '<div class="card"><table class="data-table"><thead><tr><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Tipo</th></tr></thead><tbody>';
        foreach ($users as $row) {
            $html .= '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['email']) . '</td><td>' . htmlspecialchars($row['phone']) . '</td><td>' . htmlspecialchars(roleLabel($row['role'])) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Usuários', $html, 'admin');
        break;

    case 'vets':
        $html = '<section class="page-header"><div><span class="eyebrow">Equipe</span><h1>Veterinários</h1></div></section>';
        $html .= '<div class="card"><h3>Cadastrar veterinário</h3><form method="post" class="grid-form"><input type="hidden" name="vet_submit" value="1"><div class="form-grid"><label><span>Nome</span><input type="text" name="name" required></label><label><span>E-mail</span><input type="email" name="email" required></label><label><span>Senha</span><input type="password" name="password" required></label><label><span>CRMV</span><input type="text" name="crmv" required></label><label><span>Especialidade</span><input type="text" name="especialidade"></label><label><span>Dias</span><input type="text" name="dias_atendimento"></label><label><span>Horários</span><input type="text" name="horarios_atendimento"></label></div><button type="submit" class="primary-button">Salvar</button></form></div>';
        $html .= '<div class="card"><table class="data-table"><thead><tr><th>Nome</th><th>CRMV</th><th>Especialidade</th><th>Dias</th><th>Horários</th></tr></thead><tbody>';
        foreach ($vets as $row) {
            $html .= '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['crmv']) . '</td><td>' . htmlspecialchars($row['especialidade']) . '</td><td>' . htmlspecialchars($row['dias_atendimento']) . '</td><td>' . htmlspecialchars($row['horarios_atendimento']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Veterinários', $html, 'admin');
        break;

    case 'services':
        $html = '<section class="page-header"><div><span class="eyebrow">Serviços</span><h1>Gerenciar serviços</h1></div></section>';
        $html .= '<div class="card"><h3>Adicionar serviço</h3><form method="post" class="grid-form"><input type="hidden" name="service_submit" value="1"><div class="form-grid"><label><span>Nome</span><input type="text" name="nome" required></label><label><span>Duração (min)</span><input type="number" name="duracao" required></label><label><span>Valor</span><input type="number" step="0.01" name="valor" required></label></div><button type="submit" class="primary-button">Salvar</button></form></div>';
        $html .= '<div class="card"><table class="data-table"><thead><tr><th>Nome</th><th>Duração</th><th>Valor</th></tr></thead><tbody>';
        foreach ($services as $service) {
            $html .= '<tr><td>' . htmlspecialchars($service['nome']) . '</td><td>' . (int) $service['duracao'] . ' min</td><td>R$ ' . number_format((float) $service['valor'], 2, ',', '.') . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Serviços', $html, 'admin');
        break;

    case 'notices':
        $html = '<section class="page-header"><div><span class="eyebrow">Avisos</span><h1>Gerenciar avisos</h1></div></section>';
        $html .= '<div class="card"><h3>Publicar comunicado</h3><form method="post" class="grid-form"><input type="hidden" name="notice_submit" value="1"><div class="form-grid"><label><span>Título</span><input type="text" name="titulo" required></label><label><span>Data de publicação</span><input type="date" name="data_publicacao" value="' . date('Y-m-d') . '"></label></div><label><span>Mensagem</span><textarea name="mensagem" required rows="5"></textarea></label><button type="submit" class="primary-button">Publicar</button></form></div>';
        $html .= '<div class="card"><h3>Comunicados publicados</h3>';
        foreach ($notices as $notice) {
            $html .= '<article class="notice-card"><div class="notice-top"><h3>' . htmlspecialchars($notice['titulo']) . '</h3><span>' . formatDate($notice['data_publicacao']) . '</span></div><p>' . htmlspecialchars($notice['mensagem']) . '</p></article>';
        }
        $html .= '</div>';
        renderLayout('PETVIDA | Avisos', $html, 'admin');
        break;

    case 'reports':
        $html = '<section class="page-header"><div><span class="eyebrow">Relatórios</span><h1>Relatórios e Estatísticas</h1></div></section>';
        $html .= '<div class="stats-grid"><div class="stat-card green"><span>Atendimentos</span><strong>' . (int) $db->query('SELECT COUNT(*) FROM agendamentos')->fetchColumn() . '</strong></div><div class="stat-card blue"><span>Consultas</span><strong>' . (int) $db->query("SELECT COUNT(*) FROM agendamentos WHERE servico_id IN (SELECT id FROM servicos WHERE nome = 'Consulta')")->fetchColumn() . '</strong></div><div class="stat-card yellow"><span>Vacinas em atraso</span><strong>' . (int) $db->query("SELECT COUNT(*) FROM vacinacoes WHERE proxima_dose < date('now')")->fetchColumn() . '</strong></div></div>';
        $html .= '<div class="card"><table class="data-table"><thead><tr><th>Serviço</th><th>Quantidade</th></tr></thead><tbody>';
        $servicosMais = $db->query('SELECT s.nome, COUNT(a.id) AS total FROM agendamentos a JOIN servicos s ON s.id = a.servico_id GROUP BY s.id ORDER BY total DESC LIMIT 5')->fetchAll();
        foreach ($servicosMais as $row) {
            $html .= '<tr><td>' . htmlspecialchars($row['nome']) . '</td><td>' . (int) $row['total'] . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Relatórios', $html, 'admin');
        break;

    case 'stock':
        $html = '<section class="page-header"><div><span class="eyebrow">Estoque</span><h1>Estoque de Vacinas</h1></div></section>';
        $html .= '<div class="card"><table class="data-table"><thead><tr><th>Vacina</th><th>Quantidade</th><th>Estoque mínimo</th><th>Situação</th></tr></thead><tbody>';
        foreach ($vacinas as $vacina) {
            $situacao = 'Estoque normal';
            if ($vacina['quantidade'] <= $vacina['estoque_minimo']) {
                $situacao = 'Estoque baixo';
            }
            if ($vacina['quantidade'] <= 0) {
                $situacao = 'Estoque crítico';
            }
            $html .= '<tr><td>' . htmlspecialchars($vacina['nome']) . '</td><td>' . (int) $vacina['quantidade'] . '</td><td>' . (int) $vacina['estoque_minimo'] . '</td><td><span class="badge ' . strtolower(str_replace(' ', '-', $situacao)) . '">' . htmlspecialchars($situacao) . '</span></td></tr>';
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Estoque', $html, 'admin');
        break;

    case 'profile':
        $html = '<section class="page-header"><div><span class="eyebrow">Perfil</span><h1>Perfil do administrador</h1></div></section>';
        $html .= '<div class="profile-card"><div class="profile-main"><div class="avatar-circle large">' . strtoupper(substr($user['name'], 0, 1)) . '</div><div><h2>' . htmlspecialchars($user['name']) . '</h2><p>' . htmlspecialchars($user['email']) . '</p><p>' . htmlspecialchars($user['phone'] ?: 'Telefone não informado') . '</p></div></div></div>';
        renderLayout('PETVIDA | Perfil', $html, 'admin');
        break;

    case 'dashboard':
    default:
        $stats = [
            ['label' => 'Total de tutores', 'value' => (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'tutor'")->fetchColumn(), 'color' => 'green'],
            ['label' => 'Total de animais', 'value' => (int) $db->query('SELECT COUNT(*) FROM animais')->fetchColumn(), 'color' => 'blue'],
            ['label' => 'Total de veterinários', 'value' => (int) $db->query('SELECT COUNT(*) FROM veterinarios')->fetchColumn(), 'color' => 'yellow'],
            ['label' => 'Consultas do dia', 'value' => (int) $db->query("SELECT COUNT(*) FROM agendamentos WHERE data = date('now')")->fetchColumn(), 'color' => 'red'],
        ];
        $html = '<section class="page-header"><div><span class="eyebrow">Olá, ' . htmlspecialchars($user['name']) . '</span><h1>Dashboard administrativo</h1></div></section>';
        $html .= '<div class="stats-grid">';
        foreach ($stats as $card) {
            $html .= '<div class="stat-card ' . $card['color'] . '"><span>' . htmlspecialchars($card['label']) . '</span><strong>' . htmlspecialchars($card['value']) . '</strong></div>';
        }
        $html .= '</div>';
        $html .= '<div class="content-grid two-columns"><div class="card"><h3>Serviços mais procurados</h3><table class="data-table"><thead><tr><th>Serviço</th><th>Quantidade</th></tr></thead><tbody>';
        $serviceRows = $db->query('SELECT s.nome, COUNT(a.id) AS total FROM agendamentos a JOIN servicos s ON s.id = a.servico_id GROUP BY s.id ORDER BY total DESC LIMIT 5')->fetchAll();
        foreach ($serviceRows as $row) {
            $html .= '<tr><td>' . htmlspecialchars($row['nome']) . '</td><td>' . (int) $row['total'] . '</td></tr>';
        }
        $html .= '</tbody></table></div><div class="card"><h3>Vacinas com estoque baixo</h3><ul class="list-stack">';
        foreach ($vacinas as $vacina) {
            if ($vacina['quantidade'] <= $vacina['estoque_minimo']) {
                $html .= '<li><strong>' . htmlspecialchars($vacina['nome']) . '</strong><p>' . (int) $vacina['quantidade'] . ' itens em estoque</p></li>';
            }
        }
        $html .= '</ul></div></div>';
        renderLayout('PETVIDA | Dashboard', $html, 'admin');
        break;
}
