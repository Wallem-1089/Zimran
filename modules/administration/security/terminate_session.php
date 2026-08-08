<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: active_sessions.php'); exit; }
requireCsrfToken();
$result = $securitySessionService->terminateSession(
    (int)($_POST['session_id'] ?? 0),
    (int)$currentUser['id'],
    'Terminated through security administration.'
);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] = $result['success'] ? 'Session terminated.' : $result['errors'];
header('Location: active_sessions.php');
exit;
