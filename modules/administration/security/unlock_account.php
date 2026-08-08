<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireSecurityAdministrator();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: account_lockouts.php'); exit; }
requireCsrfToken();
$result = $securityUserService->unlockUser((int)($_POST['user_id'] ?? 0), (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] = $result['success'] ? 'Account unlocked.' : $result['errors'];
header('Location: account_lockouts.php'); exit;
