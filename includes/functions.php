<?php

function currentUser()
{
    return $_SESSION['user'] ?? null;
}

function roleLabel(string $role): string
{
    $labels = [
        'tutor' => 'Tutor',
        'veterinario' => 'Veterinário',
        'admin' => 'Administrador',
    ];

    return $labels[$role] ?? ucfirst($role);
}

function redirect(string $page): void
{
    header('Location: ' . APP_URL . '/index.php?page=' . urlencode($page));
    exit;
}

function requireRole(array $allowedRoles): void
{
    $user = currentUser();

    if (!$user || !in_array($user['role'], $allowedRoles, true)) {
        redirect('login');
    }
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function getRoleMenu(string $role): array
{
    $menu = [
        'tutor' => [
            ['label' => 'Dashboard', 'page' => 'dashboard'],
            ['label' => 'Meus Animais', 'page' => 'animals'],
            ['label' => 'Agendamentos', 'page' => 'appointments'],
            ['label' => 'Carteira de Vacinação', 'page' => 'vaccination'],
            ['label' => 'Avisos', 'page' => 'notices'],
            ['label' => 'Perfil', 'page' => 'profile'],
        ],
        'veterinario' => [
            ['label' => 'Dashboard', 'page' => 'dashboard'],
            ['label' => 'Minha Agenda', 'page' => 'agenda'],
            ['label' => 'Prontuários', 'page' => 'records'],
            ['label' => 'Vacinas', 'page' => 'vaccines'],
            ['label' => 'Prescrições', 'page' => 'prescriptions'],
            ['label' => 'Perfil', 'page' => 'profile'],
        ],
        'admin' => [
            ['label' => 'Dashboard', 'page' => 'dashboard'],
            ['label' => 'Usuários', 'page' => 'users'],
            ['label' => 'Veterinários', 'page' => 'vets'],
            ['label' => 'Serviços', 'page' => 'services'],
            ['label' => 'Avisos', 'page' => 'notices'],
            ['label' => 'Relatórios', 'page' => 'reports'],
            ['label' => 'Estoque', 'page' => 'stock'],
            ['label' => 'Perfil', 'page' => 'profile'],
        ],
    ];

    return $menu[$role] ?? [];
}

function renderLayout(string $title, string $html, ?string $role = null, bool $showSidebar = true): void
{
    $user = currentUser();
    $menu = $role ? getRoleMenu($role) : [];
    $flash = getFlash();

    echo '<!DOCTYPE html>';
    echo '<html lang="pt-BR">';
    echo '<head>';
    echo '  <meta charset="UTF-8">';
    echo '  <meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '  <title>' . htmlspecialchars($title) . '</title>';
    echo '  <link rel="stylesheet" href="' . APP_URL . '/assets/css/styles.css">';
    echo '  <link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '</head>';
    echo '<body>';

    if ($flash) {
        echo '<div class="flash ' . htmlspecialchars($flash['type']) . '">' . htmlspecialchars($flash['message']) . '</div>';
    }

    echo '<div class="app-shell">';

    if ($showSidebar && $role) {
        echo '<aside class="sidebar">';
        echo '  <div class="brand-box">';
        echo '    <div class="brand-mark">P</div>';
        echo '    <div><strong>PETVIDA</strong><small>Amigo Fiel</small></div>';
        echo '  </div>';
        echo '  <nav class="sidebar-nav">';
        foreach ($menu as $item) {
            $activeClass = ($_GET['page'] ?? 'dashboard') === $item['page'] ? 'active' : '';
            echo '    <a class="nav-item ' . $activeClass . '" href="' . APP_URL . '/index.php?page=' . urlencode($item['page']) . '">' . htmlspecialchars($item['label']) . '</a>';
        }
        echo '  </nav>';
        echo '  <div class="sidebar-footer">';
        echo '    <a href="' . APP_URL . '/index.php?page=profile" class="user-mini">';
        echo '      <div class="avatar-circle">' . strtoupper(substr($user['name'] ?? 'U', 0, 1)) . '</div>';
        echo '      <div>';
        echo '        <strong>' . htmlspecialchars($user['name'] ?? 'Usuário') . '</strong>';
        echo '        <small>' . htmlspecialchars(roleLabel($user['role'] ?? '')) . '</small>';
        echo '      </div>';
        echo '    </a>';
        echo '    <a href="' . APP_URL . '/index.php?page=logout" class="sidebar-logout">Sair</a>';
        echo '  </div>';
        echo '</aside>';
    }

    echo '<div class="main-panel">';
    if ($showSidebar && $role) {
        echo '<header class="topbar"><button class="mobile-menu" type="button">☰</button><div class="topbar-title">' . htmlspecialchars($title) . '</div><div class="topbar-actions"><span class="badge-soft">' . htmlspecialchars(roleLabel($user['role'] ?? '')) . '</span><span class="icon-dot"></span></div></header>';
    } else {
        echo '<header class="topbar public-topbar"><div class="brand-inline"><div class="brand-mark">P</div><div><strong>PETVIDA</strong><small>Clínica Veterinária Amigo Fiel</small></div></div></header>';
    }

    echo '<main class="page-content">';
    echo $html;
    echo '</main>';
    echo '</div>';
    echo '</div>';
    echo '<script src="' . APP_URL . '/assets/js/app.js"></script>';
    echo '</body>';
    echo '</html>';
}

function getDbUserByEmail(string $email): ?array
{
    $db = dbConnect();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => strtolower(trim($email))]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function getAnimalById(int $id): ?array
{
    $db = dbConnect();
    $stmt = $db->prepare('SELECT * FROM animais WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function getAppointmentStats(string $role): array
{
    $db = dbConnect();
    $currentUser = currentUser();
    $stats = [
        'consultasHoje' => 0,
        'proximos' => 0,
        'atendimentosSemana' => 0,
        'animaisAtendidos' => 0,
        'vacinasAplicadas' => 0,
    ];

    if ($role === 'veterinario') {
        $vet = $db->query('SELECT id FROM veterinarios WHERE user_id = ' . (int) ($currentUser['id'] ?? 0))->fetch();
        if ($vet) {
            $vetId = (int) $vet['id'];
            $stats['consultasHoje'] = (int) $db->query("SELECT COUNT(*) FROM agendamentos WHERE veterinario_id = $vetId AND data = date('now')")->fetchColumn();
            $stats['proximos'] = (int) $db->query("SELECT COUNT(*) FROM agendamentos WHERE veterinario_id = $vetId AND data >= date('now')")->fetchColumn();
            $stats['atendimentosSemana'] = (int) $db->query("SELECT COUNT(*) FROM agendamentos WHERE veterinario_id = $vetId AND date(data) BETWEEN date('now','-6 days') AND date('now')")->fetchColumn();
            $stats['animaisAtendidos'] = (int) $db->query("SELECT COUNT(DISTINCT animal_id) FROM agendamentos WHERE veterinario_id = $vetId")->fetchColumn();
            $stats['vacinasAplicadas'] = (int) $db->query("SELECT COUNT(*) FROM vacinacoes WHERE veterinario_id = $vetId")->fetchColumn();
        }
    }

    if ($role === 'tutor') {
        $stats['animaisTotal'] = (int) $db->query('SELECT COUNT(*) FROM animais WHERE tutor_id = ' . (int) ($currentUser['id'] ?? 0))->fetchColumn();
    }

    return $stats;
}

function formatDate(string $value): string
{
    $date = new DateTime($value);
    return $date->format('d/m/Y');
}

function statusBadge(string $status): string
{
    $map = [
        'confirmado' => 'success',
        'pendente' => 'warning',
        'cancelado' => 'danger',
        'realizado' => 'info',
    ];

    return $map[strtolower($status)] ?? 'default';
}

function signedIn(): bool
{
    return isset($_SESSION['user']);
}

function logout(): void
{
    session_unset();
    session_destroy();
    redirect('login');
}
