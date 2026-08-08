<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; $id=(int)($_GET['id']??0); $identifier=$identifierService->getIdentifierById($id);
if (!$identifier || !$permissionService->canViewPatientIdentifiers((int)$identifier['patient_id'],$currentUser)) { http_response_code(403); exit('Access denied.'); }
$history=$identifierService->getIdentifierHistory($id); $pageTitle='Identifier History'; $moduleStylesheet='/modules/medical_records/assets/medical_records.css'; require __DIR__.'/../../../layouts/header.php'; require __DIR__.'/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__.'/../../../layouts/navbar.php'; ?><main class="content"><div class="card"><h1>Identifier History</h1><?php foreach($history as $entry): ?><div class="history-entry"><strong><?=e($entry['action'])?></strong> · Version <?= (int)$entry['version_no'] ?> · <?=e($entry['actor_name'])?><p><?=e($entry['reason'])?></p></div><?php endforeach; ?></div></main><?php require __DIR__.'/../../../layouts/footer.php'; ?></div>
