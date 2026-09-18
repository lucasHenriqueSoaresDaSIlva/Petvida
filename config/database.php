<?php

function dbConnect()
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dir = dirname(__DIR__) . '/storage';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $dsn = 'sqlite:' . DB_PATH;
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    return $pdo;
}

function initializeDatabase()
{
    $db = dbConnect();

    $db->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            phone TEXT,
            role TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS animais (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tutor_id INTEGER NOT NULL,
            nome TEXT NOT NULL,
            especie TEXT NOT NULL,
            raca TEXT,
            sexo TEXT,
            data_nascimento TEXT,
            foto TEXT,
            FOREIGN KEY(tutor_id) REFERENCES users(id)
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS veterinarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            crmv TEXT NOT NULL,
            especialidade TEXT,
            dias_atendimento TEXT,
            horarios_atendimento TEXT,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS servicos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            duracao INTEGER NOT NULL,
            valor REAL NOT NULL
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS agendamentos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            animal_id INTEGER NOT NULL,
            veterinario_id INTEGER NOT NULL,
            servico_id INTEGER NOT NULL,
            data TEXT NOT NULL,
            hora TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'confirmado',
            FOREIGN KEY(animal_id) REFERENCES animais(id),
            FOREIGN KEY(veterinario_id) REFERENCES veterinarios(id),
            FOREIGN KEY(servico_id) REFERENCES servicos(id)
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS prontuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            agendamento_id INTEGER NOT NULL,
            peso REAL,
            sintomas TEXT,
            diagnostico TEXT,
            observacoes TEXT,
            FOREIGN KEY(agendamento_id) REFERENCES agendamentos(id)
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS vacinas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            quantidade INTEGER NOT NULL,
            estoque_minimo INTEGER NOT NULL
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS vacinacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            animal_id INTEGER NOT NULL,
            vacina_id INTEGER NOT NULL,
            veterinario_id INTEGER NOT NULL,
            data_aplicacao TEXT NOT NULL,
            lote TEXT,
            proxima_dose TEXT,
            FOREIGN KEY(animal_id) REFERENCES animais(id),
            FOREIGN KEY(vacina_id) REFERENCES vacinas(id),
            FOREIGN KEY(veterinario_id) REFERENCES veterinarios(id)
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS prescricoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            prontuario_id INTEGER NOT NULL,
            medicamento TEXT NOT NULL,
            dosagem TEXT,
            periodo_tratamento TEXT,
            data_prescricao TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(prontuario_id) REFERENCES prontuarios(id)
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS avisos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            mensagem TEXT NOT NULL,
            data_publicacao TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $userCount = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount === 0) {
        $insertUsers = [
            ['João Pereira', 'joao@petvida.com', password_hash('123456', PASSWORD_DEFAULT), '(11) 98888-1111', 'tutor'],
            ['Maria Silva', 'maria@petvida.com', password_hash('123456', PASSWORD_DEFAULT), '(11) 97777-2222', 'veterinario'],
            ['Carlos Almeida', 'admin@petvida.com', password_hash('123456', PASSWORD_DEFAULT), '(11) 96666-3333', 'admin'],
            ['Ana Souza', 'ana@petvida.com', password_hash('123456', PASSWORD_DEFAULT), '(11) 95555-4444', 'tutor'],
            ['Pedro Costa', 'pedro@petvida.com', password_hash('123456', PASSWORD_DEFAULT), '(11) 94444-5555', 'veterinario'],
        ];

        $stmt = $db->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)');
        foreach ($insertUsers as $user) {
            $stmt->execute($user);
        }

        $tutor1 = $db->query("SELECT id FROM users WHERE email = 'joao@petvida.com'")->fetch()['id'];
        $tutor2 = $db->query("SELECT id FROM users WHERE email = 'ana@petvida.com'")->fetch()['id'];
        $vet1 = $db->query("SELECT id FROM users WHERE email = 'maria@petvida.com'")->fetch()['id'];
        $vet2 = $db->query("SELECT id FROM users WHERE email = 'pedro@petvida.com'")->fetch()['id'];

        $db->exec("INSERT INTO veterinarios (user_id, crmv, especialidade, dias_atendimento, horarios_atendimento) VALUES
            ($vet1, 'SP-12345', 'Clínica Geral', 'Segunda a Sexta', '08:00-18:00'),
            ($vet2, 'SP-67890', 'Dermatologia', 'Terça a Sábado', '09:00-17:00')");

        $db->exec("INSERT INTO animais (tutor_id, nome, especie, raca, sexo, data_nascimento, foto) VALUES
            ($tutor1, 'Nina', 'Cachorro', 'Shih Tzu', 'Fêmea', '2020-02-15', 'https://images.unsplash.com/photo-1517849845537-4d257902454a?auto=format&fit=crop&w=900&q=80'),
            ($tutor1, 'Thor', 'Gato', 'Persa', 'Macho', '2019-10-08', 'https://images.unsplash.com/photo-1511044568932-338cba0ad803?auto=format&fit=crop&w=900&q=80'),
            ($tutor2, 'Luna', 'Cachorro', 'Labrador', 'Fêmea', '2021-05-11', 'https://images.unsplash.com/photo-1537151625747-768eb6cf92b2?auto=format&fit=crop&w=900&q=80')");

        $db->exec("INSERT INTO servicos (nome, duracao, valor) VALUES
            ('Consulta', 45, 180.00),
            ('Retorno', 30, 120.00),
            ('Vacinação', 20, 95.00),
            ('Banho e Tosa', 60, 140.00),
            ('Cirurgia', 120, 1200.00)");

        $db->exec("INSERT INTO avisos (titulo, mensagem, data_publicacao) VALUES
            ('Campanha de vacinação', 'A campanha de vacinação continua até 30/09. Agende sua dose com antecedência.', '2026-09-15'),
            ('Mudança de horário', 'O plantão do veterinário será reduzido na próxima semana devido a manutenção.', '2026-09-12'),
            ('Feriados', 'A clínica estará fechada nos feriados de 12/10 e 21/10.', '2026-09-10')");

        $db->exec("INSERT INTO vacinas (nome, quantidade, estoque_minimo) VALUES
            ('Vacina Antirrábica', 18, 10),
            ('Vacina V8', 12, 8),
            ('Vacina Gripe Canina', 7, 10),
            ('Vacina Quádrupla', 5, 8)");

        $animal1 = $db->query("SELECT id FROM animais WHERE nome = 'Nina'")->fetch()['id'];
        $animal2 = $db->query("SELECT id FROM animais WHERE nome = 'Thor'")->fetch()['id'];
        $vetMaria = $db->query("SELECT id FROM veterinarios WHERE crmv = 'SP-12345'")->fetch()['id'];
        $vetPedro = $db->query("SELECT id FROM veterinarios WHERE crmv = 'SP-67890'")->fetch()['id'];
        $vacinaAntirrabica = $db->query("SELECT id FROM vacinas WHERE nome = 'Vacina Antirrábica'")->fetch()['id'];
        $vacinaV8 = $db->query("SELECT id FROM vacinas WHERE nome = 'Vacina V8'")->fetch()['id'];
        $consulta = $db->query("SELECT id FROM servicos WHERE nome = 'Consulta'")->fetch()['id'];
        $vacinacao = $db->query("SELECT id FROM servicos WHERE nome = 'Vacinação'")->fetch()['id'];

        $db->exec("INSERT INTO agendamentos (animal_id, veterinario_id, servico_id, data, hora, status) VALUES
            ($animal1, $vetMaria, $consulta, '2026-09-19', '09:30', 'confirmado'),
            ($animal2, $vetMaria, $vacinacao, '2026-09-20', '11:00', 'confirmado'),
            ($animal1, $vetPedro, $consulta, '2026-09-22', '15:00', 'pendente'),
            ($animal2, $vetMaria, $consulta, '2026-09-25', '13:30', 'confirmado')");

        $agendamento1 = $db->query("SELECT id FROM agendamentos WHERE animal_id = $animal1 AND hora = '09:30'")->fetch()['id'];
        $agendamento2 = $db->query("SELECT id FROM agendamentos WHERE animal_id = $animal2 AND hora = '11:00'")->fetch()['id'];

        $db->exec("INSERT INTO prontuarios (agendamento_id, peso, sintomas, diagnostico, observacoes) VALUES
            ($agendamento1, 6.2, 'Coceira leve na região das orelhas.', 'Otite externa leve.', 'Manter limpeza diária e retornar em 14 dias.'),
            ($agendamento2, 4.8, 'Vômitos eventuais.', 'Sem alterações relevantes.', 'Monitorar alimentação e hidratação.')");

        $prontuario1 = $db->query("SELECT id FROM prontuarios WHERE agendamento_id = $agendamento1")->fetch()['id'];
        $prontuario2 = $db->query("SELECT id FROM prontuarios WHERE agendamento_id = $agendamento2")->fetch()['id'];

        $db->exec("INSERT INTO vacinacoes (animal_id, vacina_id, veterinario_id, data_aplicacao, lote, proxima_dose) VALUES
            ($animal1, $vacinaAntirrabica, $vetMaria, '2026-08-15', 'RA-1022', '2027-08-15'),
            ($animal2, $vacinaV8, $vetMaria, '2026-09-01', 'V8-331', '2026-12-01'),
            ($animal1, $vacinaV8, $vetPedro, '2026-09-14', 'V8-450', '2026-11-14')");

        $db->exec("INSERT INTO prescricoes (prontuario_id, medicamento, dosagem, periodo_tratamento, data_prescricao) VALUES
            ($prontuario1, 'Oticillin', '2 gotas no ouvido 2x ao dia', '7 dias', '2026-09-19'),
            ($prontuario2, 'Suplemento vitamínico', '1 comprimido ao dia', '30 dias', '2026-09-20')");
    }
}
