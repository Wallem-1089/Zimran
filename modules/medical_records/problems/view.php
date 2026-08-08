<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$problem = longitudinalProblemForUser($problemListService, (int)($_GET['id'] ?? 0), $currentUser);
$patient = $patientService->getPatientById((int)$problem['patient_id']);
$visitId = longitudinalVisitContext($pdo, $permissionService, $currentUser, (int)$problem['patient_id'], $_GET['visit'] ?? null);
$canManage = $permissionService->canManageProblemList((int)$problem['patient_id'], $currentUser);
$canVerify = $permissionService->canVerifyProblemList((int)$problem['patient_id'], $currentUser);
$canResolve = $permissionService->canResolveProblemList((int)$problem['patient_id'], $currentUser);
$pageTitle = 'Problem Details'; $moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php'; require __DIR__ . '/../../../layouts/sidebar.php';
?><div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content">
<h1><?= e($problem['problem_name']) ?></h1>
<div class="card summary-grid">
<?php foreach (['category' => 'Category','clinical_status' => 'Status','verification_status' => 'Verification','severity' => 'Severity','onset_date' => 'Onset','confidentiality_level' => 'Confidentiality','notes' => 'Notes'] as $field => $label): ?><div class="summary-item"><span class="summary-label"><?= e($label) ?></span><span class="summary-value"><?= e($problem[$field] ?? 'Not recorded') ?></span></div><?php endforeach; ?>
</div>
<div class="card"><h2>Actions</h2>
<?php if ($canManage && !in_array($problem['clinical_status'], ['Resolved','Entered-in-error'], true)): ?><a class="btn-secondary" href="edit.php?id=<?= (int)$problem['id'] ?><?= e(longitudinalQuery($visitId)) ?>">Edit</a><?php endif; ?>
<?php if ($permissionService->canViewProblemHistory((int)$problem['patient_id'], $currentUser)): ?><a class="btn-secondary" href="history.php?id=<?= (int)$problem['id'] ?>">History</a><?php endif; ?>
<?php $actions = [];
if ($canVerify && $problem['clinical_status'] === 'Active' && $problem['verification_status'] !== 'Confirmed') { $actions += ['verify' => 'Verify','refute' => 'Refute']; }
if ($canResolve && $problem['clinical_status'] === 'Active') { $actions += ['deactivate' => 'Deactivate','resolve' => 'Resolve']; }
if ($canResolve && in_array($problem['clinical_status'], ['Inactive','Resolved'], true)) { $actions['reactivate'] = 'Reactivate'; }
if ($canResolve && $problem['clinical_status'] !== 'Entered-in-error') { $actions['entered_in_error'] = 'Entered in Error'; }
foreach ($actions as $action => $label): ?><form class="inline-form" method="post" action="<?= e($action) ?>.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$problem['id'] ?>"><input type="hidden" name="version" value="<?= (int)$problem['version'] ?>"><input type="hidden" name="visit_id" value="<?= (int)($visitId ?? 0) ?>"><input required name="reason" maxlength="1000" placeholder="Reason"><button type="submit" class="btn-secondary"><?= e($label) ?></button></form><?php endforeach; ?>
</div><a href="../chart.php?patient=<?= (int)$patient['id'] ?>&tab=problems<?= e(longitudinalQuery($visitId)) ?>">Back to Problem List</a>
</main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
