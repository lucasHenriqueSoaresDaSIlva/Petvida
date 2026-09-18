<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = getDbUserByEmail($email);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'],
        ];
        setFlash('success', 'Login realizado com sucesso.');
        $redirectPage = $user['role'] === 'tutor' ? 'dashboard' : ($user['role'] === 'veterinario' ? 'dashboard' : 'dashboard');
        redirect($redirectPage);
    }

    setFlash('error', 'Credenciais inválidas. Verifique seu e-mail e senha.');
    redirect('login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$password) {
        setFlash('error', 'Preencha todos os campos obrigatórios.');
        redirect('register');
    }

    if (getDbUserByEmail($email)) {
        setFlash('error', 'Já existe um usuário cadastrado com este e-mail.');
        redirect('register');
    }

    $db = dbConnect();
    $stmt = $db->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone, 'tutor']);

    setFlash('success', 'Cadastro realizado com sucesso. Faça login para continuar.');
    redirect('login');
}

$page = $_GET['page'] ?? 'login';

switch ($page) {
    case 'register':
        $html = <<<'HTML'
            <section class="auth-panel">
                <div class="auth-card">
                    <div class="auth-header">
                        <span class="eyebrow">PETVIDA</span>
                        <h1>Crie sua conta</h1>
                        <p>Cadastre-se para acompanhar seus pets e agendar atendimentos.</p>
                    </div>
                    <form method="post" class="auth-form">
                        <input type="hidden" name="register_submit" value="1">
                        <label>
                            <span>Nome completo</span>
                            <input type="text" name="name" required placeholder="Seu nome">
                        </label>
                        <label>
                            <span>E-mail</span>
                            <input type="email" name="email" required placeholder="nome@email.com">
                        </label>
                        <label>
                            <span>Telefone</span>
                            <input type="tel" name="phone" placeholder="(11) 99999-9999">
                        </label>
                        <label>
                            <span>Senha</span>
                            <input type="password" name="password" required placeholder="Mínimo 6 caracteres">
                        </label>
                        <button type="submit" class="primary-button">Cadastrar</button>
                    </form>
                    <div class="auth-meta">
                        <span>Já possui conta?</span>
                        <a href="index.php?page=login">Entrar agora</a>
                    </div>
                </div>
            </section>
        HTML;
        renderLayout('PETVIDA | Cadastro', $html);
        break;

    case 'forgot':
        $html = <<<'HTML'
            <section class="auth-panel">
                <div class="auth-card">
                    <div class="auth-header">
                        <span class="eyebrow">Recuperação</span>
                        <h1>Recuperar senha</h1>
                        <p>Informe seu e-mail para receber instruções de redefinição.</p>
                    </div>
                    <form method="post" class="auth-form">
                        <label>
                            <span>E-mail</span>
                            <input type="email" name="email" placeholder="nome@email.com" required>
                        </label>
                        <button type="submit" class="primary-button">Enviar link</button>
                    </form>
                    <div class="auth-meta">
                        <a href="index.php?page=login">Voltar para o login</a>
                    </div>
                </div>
            </section>
        HTML;
        renderLayout('PETVIDA | Recuperar senha', $html);
        break;

    case 'login':
    default:
        $html = <<<'HTML'
            <section class="auth-panel">
                <div class="auth-card">
                    <div class="auth-header">
                        <span class="eyebrow">Clínica Veterinária Amigo Fiel</span>
                        <h1>Bem-vindo ao PETVIDA</h1>
                        <p>Centralize cuidado, agendamentos e saúde do seu pet em um só lugar.</p>
                    </div>

                    <form method="post" class="auth-form">
                        <input type="hidden" name="login_submit" value="1">
                        <label>
                            <span>E-mail</span>
                            <input type="email" name="email" placeholder="usuario@email.com" required>
                        </label>
                        <label>
                            <span>Senha</span>
                            <input type="password" name="password" placeholder="Sua senha" required>
                        </label>
                        <div class="auth-helpers">
                            <a href="index.php?page=forgot">Esqueci minha senha</a>
                        </div>
                        <button type="submit" class="primary-button">Entrar</button>
                    </form>

                    <div class="demo-users">
                        <p>Credenciais de demonstração</p>
                        <div class="demo-row">
                            <span class="badge-sample tutor">Tutor</span>
                            <small>joao@petvida.com / 123456</small>
                        </div>
                        <div class="demo-row">
                            <span class="badge-sample vet">Veterinário</span>
                            <small>maria@petvida.com / 123456</small>
                        </div>
                        <div class="demo-row">
                            <span class="badge-sample admin">Admin</span>
                            <small>admin@petvida.com / 123456</small>
                        </div>
                    </div>

                    <div class="auth-meta">
                        <span>Não tem conta?</span>
                        <a href="index.php?page=register">Cadastre-se</a>
                    </div>
                </div>
            </section>
        HTML;
        renderLayout('PETVIDA | Login', $html, null, false);
        break;
}
