<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$identifier = $identifierService->getIdentifierById((int)($_GET['id'] ?? 0));
if (!$identifier) { http_response_code(404); exit('Identifier not found.'); }
$patient = $patientService->getPatientById((int)$identifier['patient_id']);
if (!$permissionService->canViewPatientIdentifiers((int)$identifier['patient_id'], $currentUser)) { http_response_code(403); exit('Access denied.'); }
$pageTitle = 'Patient Identifier';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php'; require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content"><div class="card">
<h1><?= e($identifier['identifier_type']) ?></h1><p><strong>Value:</strong> <?= e($identifierService->maskIdentifier($identifier['identifier_type'], $identifier['identifier_value'])) ?></p>
<p><strong>Authority:</strong> <?= e($identifier['issuing_authority'] ?? '-') ?></p><p><strong>Status:</strong> <?= $identifier['is_active'] ? e($identifier['verification_status']) : 'Inactive' ?></p>
<a href="history.php?id=<?= (int)$identifier['id'] ?>">History</a>
<?php if ($permissionService->canManagePatientIdentifiers((int)$identifier['patient_id'], $identifier['identifier_type'], $currentUser)): ?> · <a href="edit.php?id=<?= (int)$identifier['id'] ?>">Edit</a>
<form method="post" action="set_primary.php" style="display:inline"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$identifier['id'] ?>"><input type="hidden" name="reason" value="Selected as primary by authorized user."><button type="submit">Set Primary</button></form>
<form method="post" action="deactivate.php" style="display:inline"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$identifier['id'] ?>"><input type="hidden" name="reason" value="Deactivated by authorized user."><button type="submit">Deactivate</button></form><?php endif; ?>
<?php if ($permissionService->canVerifyPatientIdentifiers($currentUser)): ?><form method="post" action="verify.php" style="display:inline"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$identifier['id'] ?>"><input type="hidden" name="reason" value="Evidence verified by authorized user."><button type="submit">Verify</button></form><?php endif; ?>
</div></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
