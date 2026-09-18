<?php
requireRole(['tutor']);

$db = dbConnect();
$user = currentUser();
$page = $_GET['page'] ?? 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['animal_submit'])) {
    $nome = trim($_POST['nome'] ?? '');
    $especie = trim($_POST['especie'] ?? '');
    $raca = trim($_POST['raca'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $foto = trim($_POST['foto'] ?? '');

    if ($nome && $especie) {
        $stmt = $db->prepare('INSERT INTO animais (tutor_id, nome, especie, raca, sexo, data_nascimento, foto) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user['id'], $nome, $especie, $raca, $sexo, $dataNascimento, $foto ?: 'https://images.unsplash.com/photo-1517849845537-4d257902454a?auto=format&fit=crop&w=800&q=80']);
        setFlash('success', 'Animal cadastrado com sucesso.');
    } else {
        setFlash('error', 'Preencha os campos obrigatórios do animal.');
    }
    redirect('animals');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_submit'])) {
    $animal_id = (int) ($_POST['animal_id'] ?? 0);
    $veterinario_id = (int) ($_POST['veterinario_id'] ?? 0);
    $servico_id = (int) ($_POST['servico_id'] ?? 0);
    $data = trim($_POST['data'] ?? '');
    $hora = trim($_POST['hora'] ?? '');

    $exists = $db->prepare('SELECT id FROM agendamentos WHERE veterinario_id = ? AND data = ? AND hora = ?');
    $exists->execute([$veterinario_id, $data, $hora]);
    if ($exists->fetch()) {
        setFlash('error', 'Não foi possível realizar o agendamento. O horário selecionado já está ocupado.');
        redirect('appointments');
    }

    $stmt = $db->prepare('INSERT INTO agendamentos (animal_id, veterinario_id, servico_id, data, hora, status) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$animal_id, $veterinario_id, $servico_id, $data, $hora, 'confirmado']);
    setFlash('success', 'Agendamento realizado com sucesso.');
    redirect('appointments');
}

$animals = $db->query('SELECT * FROM animais WHERE tutor_id = ' . (int) $user['id'])->fetchAll();
$nextAppointment = $db->query('SELECT a.*, s.nome AS servico, vet.crmv, u.name AS veterinario_name FROM agendamentos a JOIN servicos s ON s.id = a.servico_id JOIN veterinarios vet ON vet.id = a.veterinario_id JOIN users u ON u.id = vet.user_id WHERE a.animal_id IN (SELECT id FROM animais WHERE tutor_id = ' . (int) $user['id'] . ') ORDER BY a.data, a.hora LIMIT 1')->fetch();
$nextVaccine = $db->query('SELECT v.*, a.nome AS animal_name FROM vacinacoes vac JOIN vacinas v ON v.id = vac.vacina_id JOIN animais a ON a.id = vac.animal_id WHERE a.tutor_id = ' . (int) $user['id'] . ' ORDER BY vac.proxima_dose ASC LIMIT 1')->fetch();
$lateVaccines = $db->query('SELECT COUNT(*) as total FROM vacinacoes vac JOIN animais a ON a.id = vac.animal_id WHERE a.tutor_id = ' . (int) $user['id'] . ' AND vac.proxima_dose < date("now")')->fetch();
$notices = $db->query('SELECT * FROM avisos ORDER BY data_publicacao DESC LIMIT 4')->fetchAll();
$services = $db->query('SELECT * FROM servicos')->fetchAll();
$vets = $db->query('SELECT v.id, u.name, v.crmv, v.especialidade FROM veterinarios v JOIN users u ON u.id = v.user_id')->fetchAll();

switch ($page) {
    case 'animals':
        $html = '<section class="page-header"><div><span class="eyebrow">PETVIDA</span><h1>Meus Animais</h1></div><button class="primary-button" type="button" onclick="document.getElementById(\'animal-form\').classList.toggle(\'hidden\')">Adicionar animal</button></section>';
        $html .= '<div id="animal-form" class="card hidden" style="margin-bottom: 24px;">';
        $html .= '<h3>Cadastro do animal</h3><form method="post" class="grid-form"><input type="hidden" name="animal_submit" value="1"><div class="form-grid"><label><span>Nome</span><input type="text" name="nome" required></label><label><span>Espécie</span><input type="text" name="especie" required></label><label><span>Raça</span><input type="text" name="raca"></label><label><span>Sexo</span><select name="sexo"><option value="Macho">Macho</option><option value="Fêmea">Fêmea</option></select></label><label><span>Data de nascimento</span><input type="date" name="data_nascimento"></label><label><span>Foto</span><input type="url" name="foto" placeholder="URL da imagem"></label></div><button type="submit" class="primary-button">Salvar animal</button></form></div>';

        $html .= '<div class="card-grid three-columns">';
        foreach ($animals as $animal) {
            $html .= '<article class="pet-card">';
            $html .= '<div class="pet-photo" style="background-image:url(\'' . htmlspecialchars($animal['foto']) . '\');"></div>';
            $html .= '<div class="pet-info"><h3>' . htmlspecialchars($animal['nome']) . '</h3><p>' . htmlspecialchars($animal['especie']) . ' · ' . htmlspecialchars($animal['raca']) . '</p><p>Sexo: ' . htmlspecialchars($animal['sexo']) . '</p><p>Nasc.: ' . formatDate($animal['data_nascimento']) . '</p></div>';
            $html .= '<div class="pet-actions"><a class="secondary-button" href="index.php?page=animal&id=' . (int) $animal['id'] . '">Ver detalhes</a></div>';
            $html .= '</article>';
        }
        $html .= '</div>';
        renderLayout('PETVIDA | Meus Animais', $html, 'tutor');
        break;

    case 'animal':
        $id = (int) ($_GET['id'] ?? 0);
        $animal = getAnimalById($id);
        if (!$animal || (int) $animal['tutor_id'] !== (int) $user['id']) {
            setFlash('error', 'Animal não encontrado.');
            redirect('animals');
        }

        $hist = $db->query('SELECT a.data, u.name AS veterinario, s.nome AS servico, p.peso, p.diagnostico, p.observacoes FROM agendamentos a JOIN veterinarios v ON v.id = a.veterinario_id JOIN users u ON u.id = v.user_id JOIN servicos s ON s.id = a.servico_id LEFT JOIN prontuarios p ON p.agendamento_id = a.id WHERE a.animal_id = ' . (int) $id . ' ORDER BY a.data DESC')->fetchAll();
        $vac = $db->query('SELECT vac.nome, vaci.data_aplicacao, vaci.lote, u.name AS veterinario, vaci.proxima_dose FROM vacinacoes vaci JOIN vacinas vac ON vac.id = vaci.vacina_id JOIN veterinarios vet ON vet.id = vaci.veterinario_id JOIN users u ON u.id = vet.user_id WHERE vaci.animal_id = ' . (int) $id . ' ORDER BY vaci.data_aplicacao DESC')->fetchAll();
        $presc = $db->query('SELECT p.*, prp.data_prescricao FROM prescricoes p JOIN prontuarios pr ON pr.id = p.prontuario_id JOIN agendamentos a ON a.id = pr.agendamento_id WHERE a.animal_id = ' . (int) $id . ' ORDER BY p.data_prescricao DESC')->fetchAll();

        $html = '<section class="page-header"><div><span class="eyebrow">Animal</span><h1>' . htmlspecialchars($animal['nome']) . '</h1></div><a class="secondary-button" href="index.php?page=animals">Voltar</a></section>';
        $html .= '<div class="pet-detail-card">';
        $html .= '<div class="pet-detail-photo" style="background-image:url(\'' . htmlspecialchars($animal['foto']) . '\');"></div>';
        $html .= '<div class="pet-detail-summary"><h2>' . htmlspecialchars($animal['nome']) . '</h2><p><strong>Espécie:</strong> ' . htmlspecialchars($animal['especie']) . '</p><p><strong>Raça:</strong> ' . htmlspecialchars($animal['raca']) . '</p><p><strong>Sexo:</strong> ' . htmlspecialchars($animal['sexo']) . '</p><p><strong>Nascimento:</strong> ' . formatDate($animal['data_nascimento']) . '</p></div>';
        $html .= '</div>';
        $html .= '<div class="tabs"><button class="tab active" type="button">Histórico</button><button class="tab" type="button">Vacinação</button><button class="tab" type="button">Prescrições</button></div>';
        $html .= '<div class="tab-panel active"><table class="data-table"><thead><tr><th>Data</th><th>Veterinário</th><th>Serviço</th><th>Peso</th><th>Diagnóstico</th><th>Observações</th></tr></thead><tbody>';
        foreach ($hist as $item) {
            $html .= '<tr><td>' . formatDate($item['data']) . '</td><td>' . htmlspecialchars($item['veterinario']) . '</td><td>' . htmlspecialchars($item['servico']) . '</td><td>' . htmlspecialchars($item['peso'] ?: '-') . '</td><td>' . htmlspecialchars($item['diagnostico'] ?: '-') . '</td><td>' . htmlspecialchars($item['observacoes'] ?: '-') . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<div class="tab-panel"><table class="data-table"><thead><tr><th>Vacina</th><th>Data</th><th>Lote</th><th>Veterinário</th><th>Próxima dose</th></tr></thead><tbody>';
        foreach ($vac as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['nome']) . '</td><td>' . formatDate($item['data_aplicacao']) . '</td><td>' . htmlspecialchars($item['lote']) . '</td><td>' . htmlspecialchars($item['veterinario']) . '</td><td>' . formatDate($item['proxima_dose']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<div class="tab-panel"><table class="data-table"><thead><tr><th>Medicamento</th><th>Dosagem</th><th>Período</th><th>Data</th></tr></thead><tbody>';
        foreach ($presc as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['medicamento']) . '</td><td>' . htmlspecialchars($item['dosagem']) . '</td><td>' . htmlspecialchars($item['periodo_tratamento']) . '</td><td>' . formatDate($item['data_prescricao']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Detalhes do Animal', $html, 'tutor');
        break;

    case 'appointments':
        $html = '<section class="page-header"><div><span class="eyebrow">Agendamentos</span><h1>Agendamentos</h1></div></section>';
        $html .= '<div class="card"><h3>Novo agendamento</h3><form method="post" class="grid-form"><input type="hidden" name="appointment_submit" value="1"><div class="form-grid"><label><span>Animal</span><select name="animal_id">';
        foreach ($animals as $animal) {
            $html .= '<option value="' . (int) $animal['id'] . '">' . htmlspecialchars($animal['nome']) . '</option>';
        }
        $html .= '</select></label><label><span>Serviço</span><select name="servico_id">';
        foreach ($services as $service) {
            $html .= '<option value="' . (int) $service['id'] . '">' . htmlspecialchars($service['nome']) . '</option>';
        }
        $html .= '</select></label><label><span>Veterinário</span><select name="veterinario_id">';
        foreach ($vets as $vet) {
            $html .= '<option value="' . (int) $vet['id'] . '">' . htmlspecialchars($vet['name']) . ' · ' . htmlspecialchars($vet['especialidade']) . '</option>';
        }
        $html .= '</select></label><label><span>Data</span><input type="date" name="data" required></label><label><span>Horário</span><input type="time" name="hora" required></label></div><button type="submit" class="primary-button">Agendar</button></form></div>';

        $agenda = $db->query('SELECT a.*, s.nome AS servico, u.name AS veterinario_name, an.nome AS animal_name FROM agendamentos a JOIN servicos s ON s.id = a.servico_id JOIN veterinarios v ON v.id = a.veterinario_id JOIN users u ON u.id = v.user_id JOIN animais an ON an.id = a.animal_id WHERE an.tutor_id = ' . (int) $user['id'] . ' ORDER BY a.data, a.hora')->fetchAll();
        $html .= '<div class="card"><h3>Próximos atendimentos</h3><table class="data-table"><thead><tr><th>Serviço</th><th>Animal</th><th>Veterinário</th><th>Data</th><th>Horário</th><th>Status</th></tr></thead><tbody>';
        foreach ($agenda as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['servico']) . '</td><td>' . htmlspecialchars($item['animal_name']) . '</td><td>' . htmlspecialchars($item['veterinario_name']) . '</td><td>' . formatDate($item['data']) . '</td><td>' . htmlspecialchars($item['hora']) . '</td><td><span class="badge ' . statusBadge($item['status']) . '">' . htmlspecialchars($item['status']) . '</span></td></tr>';
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Agendamentos', $html, 'tutor');
        break;

    case 'vaccination':
        $rows = $db->query('SELECT vaci.*, vac.nome AS vacina_nome, a.nome AS animal_name, u.name AS veterinario_name FROM vacinacoes vaci JOIN vacinas vac ON vac.id = vaci.vacina_id JOIN animais a ON a.id = vaci.animal_id JOIN veterinarios vet ON vet.id = vaci.veterinario_id JOIN users u ON u.id = vet.user_id WHERE a.tutor_id = ' . (int) $user['id'] . ' ORDER BY vaci.data_aplicacao DESC')->fetchAll();
        $html = '<section class="page-header"><div><span class="eyebrow">Carteira</span><h1>Carteira de Vacinação</h1></div></section>';
        $html .= '<div class="card"><table class="data-table"><thead><tr><th>Animal</th><th>Vacina</th><th>Data</th><th>Lote</th><th>Veterinário</th><th>Próxima dose</th><th>Situação</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $status = (strtotime($row['proxima_dose']) < time()) ? 'Em atraso' : ((strtotime($row['proxima_dose']) - time()) < 30 * 24 * 60 * 60 ? 'Próxima dose' : 'Em dia');
            $html .= '<tr><td>' . htmlspecialchars($row['animal_name']) . '</td><td>' . htmlspecialchars($row['vacina_nome']) . '</td><td>' . formatDate($row['data_aplicacao']) . '</td><td>' . htmlspecialchars($row['lote']) . '</td><td>' . htmlspecialchars($row['veterinario_name']) . '</td><td>' . formatDate($row['proxima_dose']) . '</td><td><span class="badge ' . strtolower(str_replace(' ', '-', $status)) . '">' . htmlspecialchars($status) . '</span></td></tr>';
        }
        $html .= '</tbody></table></div>';
        renderLayout('PETVIDA | Carteira de Vacinação', $html, 'tutor');
        break;

    case 'notices':
        $html = '<section class="page-header"><div><span class="eyebrow">Comunicados</span><h1>Avisos</h1></div></section>';
        $html .= '<div class="content-stack">';
        foreach ($notices as $notice) {
            $html .= '<article class="notice-card"><div class="notice-top"><h3>' . htmlspecialchars($notice['titulo']) . '</h3><span>' . formatDate($notice['data_publicacao']) . '</span></div><p>' . htmlspecialchars($notice['mensagem']) . '</p></article>';
        }
        $html .= '</div>';
        renderLayout('PETVIDA | Avisos', $html, 'tutor');
        break;

    case 'profile':
        $html = '<section class="page-header"><div><span class="eyebrow">Perfil</span><h1>Perfil do tutor</h1></div></section>';
        $html .= '<div class="profile-card"><div class="profile-main"><div class="avatar-circle large">' . strtoupper(substr($user['name'], 0, 1)) . '</div><div><h2>' . htmlspecialchars($user['name']) . '</h2><p>' . htmlspecialchars($user['email']) . '</p><p>' . htmlspecialchars($user['phone'] ?: 'Telefone não informado') . '</p></div></div><div class="stats-inline"><div><strong>Animais</strong><span>' . count($animals) . '</span></div><div><strong>Consultas</strong><span>' . count($db->query('SELECT * FROM agendamentos a JOIN animais an ON an.id = a.animal_id WHERE an.tutor_id = ' . (int) $user['id'])->fetchAll()) . '</span></div></div></div>';
        renderLayout('PETVIDA | Perfil', $html, 'tutor');
        break;

    case 'dashboard':
    default:
        $cards = [
            ['label' => 'Animais cadastrados', 'value' => count($animals), 'color' => 'green'],
            ['label' => 'Próxima consulta', 'value' => $nextAppointment ? formatDate($nextAppointment['data']) . ' · ' . $nextAppointment['hora'] : 'Sem consulta', 'color' => 'blue'],
            ['label' => 'Próxima vacina', 'value' => $nextVaccine ? $nextVaccine['nome'] . ' · ' . formatDate($nextVaccine['proxima_dose']) : 'Sem vacina', 'color' => 'yellow'],
            ['label' => 'Vacinas em atraso', 'value' => (int) $lateVaccines['total'], 'color' => 'red'],
        ];

        $html = '<section class="page-header"><div><span class="eyebrow">Olá, ' . htmlspecialchars($user['name']) . '</span><h1>Dashboard</h1></div></section>';
        $html .= '<div class="stats-grid">';
        foreach ($cards as $card) {
            $html .= '<div class="stat-card ' . $card['color'] . '"><span>' . htmlspecialchars($card['label']) . '</span><strong>' . htmlspecialchars($card['value']) . '</strong></div>';
        }
        $html .= '</div>';
        $html .= '<div class="content-grid two-columns"><div class="card"><h3>Avisos recentes</h3><ul class="list-stack">';
        foreach ($notices as $notice) {
            $html .= '<li><strong>' . htmlspecialchars($notice['titulo']) . '</strong><small>' . formatDate($notice['data_publicacao']) . '</small><p>' . htmlspecialchars($notice['mensagem']) . '</p></li>';
        }
        $html .= '</ul></div><div class="card"><h3>Resumo do pet</h3><ul class="list-stack">';
        foreach ($animals as $animal) {
            $html .= '<li><strong>' . htmlspecialchars($animal['nome']) . '</strong><p>' . htmlspecialchars($animal['especie']) . ' · ' . htmlspecialchars($animal['raca']) . '</p></li>';
        }
        $html .= '</ul></div></div>';
        renderLayout('PETVIDA | Dashboard', $html, 'tutor');
        break;
}
