<?php
requireRole(['veterinario']);

$db = dbConnect();
$user = currentUser();
$page = $_GET['page'] ?? 'dashboard';

$vet = $db->query('SELECT id FROM veterinarios WHERE user_id = ' . (int) $user['id'])->fetch();
$vetId = $vet ? (int) $vet['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_submit'])) {
    $animal_id = (int) ($_POST['animal_id'] ?? 0);
    $peso = (float) ($_POST['peso'] ?? 0);
    $sintomas = trim($_POST['sintomas'] ?? '');
    $diagnostico = trim($_POST['diagnostico'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    $agendamento = $db->prepare('SELECT id FROM agendamentos WHERE animal_id = ? AND veterinario_id = ? ORDER BY data DESC LIMIT 1');
    $agendamento->execute([$animal_id, $vetId]);
    $agendamentoId = $agendamento->fetchColumn();

    if ($agendamentoId) {
        $stmt = $db->prepare('INSERT INTO prontuarios (agendamento_id, peso, sintomas, diagnostico, observacoes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$agendamentoId, $peso, $sintomas, $diagnostico, $observacoes]);
        setFlash('success', 'Prontuário registrado com sucesso.');
    } else {
        setFlash('error', 'Não foi possível registrar o prontuário.');
    }
    redirect('records');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vaccine_submit'])) {
    $animal_id = (int) ($_POST['animal_id'] ?? 0);
    $vacina_id = (int) ($_POST['vacina_id'] ?? 0);
    $lote = trim($_POST['lote'] ?? '');
    $data_aplicacao = trim($_POST['data_aplicacao'] ?? '');
    $proxima_dose = trim($_POST['proxima_dose'] ?? '');

    $stmt = $db->prepare('INSERT INTO vacinacoes (animal_id, vacina_id, veterinario_id, data_aplicacao, lote, proxima_dose) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$animal_id, $vacina_id, $vetId, $data_aplicacao, $lote, $proxima_dose]);

    $stock = $db->prepare('UPDATE vacinas SET quantidade = MAX(0, quantidade - 1) WHERE id = ?');
    $stock->execute([$vacina_id]);
    setFlash('success', 'Vacinação registrada com sucesso.');
    redirect('vaccines');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prescription_submit'])) {
    $prontuario_id = (int) ($_POST['prontuario_id'] ?? 0);
    $medicamento = trim($_POST['medicamento'] ?? '');
    $dosagem = trim($_POST['dosagem'] ?? '');
    $periodo = trim($_POST['periodo_tratamento'] ?? '');

    $stmt = $db->prepare('INSERT INTO prescricoes (prontuario_id, medicamento, dosagem, periodo_tratamento, data_prescricao) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$prontuario_id, $medicamento, $dosagem, $periodo, date('Y-m-d')]);
    setFlash('success', 'Prescrição registrada com sucesso.');
    redirect('prescriptions');
}

$agenda = $db->query('SELECT a.*, s.nome AS servico, an.nome AS animal_name, u.name AS tutor_name FROM agendamentos a JOIN servicos s ON s.id = a.servico_id JOIN animais an ON an.id = a.animal_id JOIN users u ON u.id = an.tutor_id WHERE a.veterinario_id = ' . $vetId . ' ORDER BY a.data, a.hora')->fetchAll();
$animals = $db->query('SELECT * FROM animais')->fetchAll();
$vacinas = $db->query('SELECT * FROM vacinas')->fetchAll();
$prontuarios = $db->query('SELECT p.*, a.data, an.nome AS animal_name FROM prontuarios p JOIN agendamentos a ON a.id = p.agendamento_id JOIN animais an ON an.id = a.animal_id WHERE a.veterinario_id = ' . $vetId . ' ORDER BY a.data DESC')->fetchAll();

switch ($page) {
    case 'agenda':
        $html = '<section class="page-header"><div><span class="eyebrow">Agenda</span><h1>Minha Agenda</h1></div></section>';
        $html .= '<div class="card"><table class="data-table"><thead><tr><th>Horário</th><th>Animal</th><th>Tutor</th><th>Serviço</th><th>Status</th></tr></thead><tbody>';
        foreach ($agenda as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['hora']) . '</td><td>' . htmlspecialchars($item['animal_name']) . '</td><td>' . htmlspecialchars($item['tutor_name']) . '</td><td>' . htmlspecialchars($item['servico']) . '</td><td><span class="badge ' . statusBadge($item['status']) . '">' . htmlspecialchars($item['status']) . '</span></td></tr>';
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Minha Agenda', $html, 'veterinario');
        break;

    case 'records':
        $html = '<section class="page-header"><div><span class="eyebrow">Atendimento</span><h1>Prontuários</h1></div></section>';
        $html .= '<div class="card"><h3>Registrar prontuário</h3><form method="post" class="grid-form"><input type="hidden" name="record_submit" value="1"><div class="form-grid"><label><span>Animal</span><select name="animal_id">';
        foreach ($animals as $animal) {
            $html .= '<option value="' . (int) $animal['id'] . '">' . htmlspecialchars($animal['nome']) . '</option>';
        }
        $html .= '</select></label><label><span>Peso</span><input type="number" step="0.1" name="peso"></label><label><span>Sintomas</span><input type="text" name="sintomas"></label><label><span>Diagnóstico</span><input type="text" name="diagnostico"></label><label><span>Observações</span><textarea name="observacoes"></textarea></label></div><button type="submit" class="primary-button">Salvar</button></form></div>';
        $html .= '<div class="card"><h3>Atendimentos anteriores</h3><table class="data-table"><thead><tr><th>Animal</th><th>Data</th><th>Sintomas</th><th>Diagnóstico</th><th>Observações</th></tr></thead><tbody>';
        foreach ($prontuarios as $pr) {
            $html .= '<tr><td>' . htmlspecialchars($pr['animal_name']) . '</td><td>' . formatDate($pr['data']) . '</td><td>' . htmlspecialchars($pr['sintomas']) . '</td><td>' . htmlspecialchars($pr['diagnostico']) . '</td><td>' . htmlspecialchars($pr['observacoes']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Prontuários', $html, 'veterinario');
        break;

    case 'vaccines':
        $html = '<section class="page-header"><div><span class="eyebrow">Vacinas</span><h1>Vacinação</h1></div></section>';
        $html .= '<div class="card"><h3>Registrar vacinação</h3><form method="post" class="grid-form"><input type="hidden" name="vaccine_submit" value="1"><div class="form-grid"><label><span>Animal</span><select name="animal_id">';
        foreach ($animals as $animal) {
            $html .= '<option value="' . (int) $animal['id'] . '">' . htmlspecialchars($animal['nome']) . '</option>';
        }
        $html .= '</select></label><label><span>Vacina</span><select name="vacina_id">';
        foreach ($vacinas as $vacina) {
            $html .= '<option value="' . (int) $vacina['id'] . '">' . htmlspecialchars($vacina['nome']) . ' (' . (int) $vacina['quantidade'] . ' em estoque)</option>';
        }
        $html .= '</select></label><label><span>Data de aplicação</span><input type="date" name="data_aplicacao" required></label><label><span>Lote</span><input type="text" name="lote"></label><label><span>Próxima dose</span><input type="date" name="proxima_dose"></label></div><button type="submit" class="primary-button">Salvar vacinação</button></form></div>';
        renderLayout('PETVIDA | Vacinas', $html, 'veterinario');
        break;

    case 'prescriptions':
        $html = '<section class="page-header"><div><span class="eyebrow">Prescrições</span><h1>Emitir prescrição</h1></div></section>';
        $html .= '<div class="card"><form method="post" class="grid-form"><input type="hidden" name="prescription_submit" value="1"><div class="form-grid"><label><span>Prontuário relacionado</span><select name="prontuario_id">';
        foreach ($prontuarios as $pr) {
            $html .= '<option value="' . (int) $pr['id'] . '">' . htmlspecialchars($pr['animal_name']) . ' · ' . formatDate($pr['data']) . '</option>';
        }
        $html .= '</select></label><label><span>Medicamento</span><input type="text" name="medicamento" required></label><label><span>Dosagem</span><input type="text" name="dosagem"></label><label><span>Período de tratamento</span><input type="text" name="periodo_tratamento" required></label></div><button type="submit" class="primary-button">Salvar prescrição</button></form></div>';
        renderLayout('PETVIDA | Prescrições', $html, 'veterinario');
        break;

    case 'profile':
        $html = '<section class="page-header"><div><span class="eyebrow">Perfil</span><h1>Perfil do veterinário</h1></div></section>';
        $vetInfo = $db->query('SELECT * FROM veterinarios WHERE user_id = ' . (int) $user['id'])->fetch();
        $html .= '<div class="profile-card"><div class="profile-main"><div class="avatar-circle large">' . strtoupper(substr($user['name'], 0, 1)) . '</div><div><h2>' . htmlspecialchars($user['name']) . '</h2><p>CRMV: ' . htmlspecialchars($vetInfo['crmv'] ?? '-') . '</p><p>Especialidade: ' . htmlspecialchars($vetInfo['especialidade'] ?? '-') . '</p></div></div></div>';
        renderLayout('PETVIDA | Perfil', $html, 'veterinario');
        break;

    case 'dashboard':
    default:
        $stats = getAppointmentStats('veterinario');
        $cards = [
            ['label' => 'Consultas de hoje', 'value' => $stats['consultasHoje'], 'color' => 'green'],
            ['label' => 'Próximos atendimentos', 'value' => $stats['proximos'], 'color' => 'blue'],
            ['label' => 'Total da semana', 'value' => $stats['atendimentosSemana'], 'color' => 'yellow'],
            ['label' => 'Vacinas aplicadas', 'value' => $stats['vacinasAplicadas'], 'color' => 'red'],
        ];
        $html = '<section class="page-header"><div><span class="eyebrow">Olá, ' . htmlspecialchars($user['name']) . '</span><h1>Dashboard</h1></div></section>';
        $html .= '<div class="stats-grid">';
        foreach ($cards as $card) {
            $html .= '<div class="stat-card ' . $card['color'] . '"><span>' . htmlspecialchars($card['label']) . '</span><strong>' . htmlspecialchars($card['value']) . '</strong></div>';
        }
        $html .= '</div>';
        $html .= '<div class="card"><h3>Agenda do dia</h3><table class="data-table"><thead><tr><th>Horário</th><th>Animal</th><th>Tutor</th><th>Serviço</th><th>Status</th></tr></thead><tbody>';
        foreach ($agenda as $item) {
            if ($item['data'] === date('Y-m-d')) {
                $html .= '<tr><td>' . htmlspecialchars($item['hora']) . '</td><td>' . htmlspecialchars($item['animal_name']) . '</td><td>' . htmlspecialchars($item['tutor_name']) . '</td><td>' . htmlspecialchars($item['servico']) . '</td><td><span class="badge ' . statusBadge($item['status']) . '">' . htmlspecialchars($item['status']) . '</span></td></tr>';
            }
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Dashboard', $html, 'veterinario');
        break;
}
