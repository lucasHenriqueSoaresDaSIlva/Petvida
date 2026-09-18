<?php
require __DIR__ . '/config/bootstrap.php';

$page = $_GET['page'] ?? 'login';

if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    logout();
}

if (!signedIn()) {
    $allowedPublic = ['login', 'register', 'forgot'];
    if (!in_array($page, $allowedPublic, true)) {
        $page = 'login';
    }
    require __DIR__ . '/pages/public.php';
    exit;
}

$user = currentUser();

switch ($user['role']) {
    case 'tutor':
        require __DIR__ . '/pages/tutor.php';
        break;
    case 'veterinario':
        require __DIR__ . '/pages/veterinary.php';
        break;
    case 'admin':
        require __DIR__ . '/pages/admin.php';
        break;
    default:
        logout();
}
