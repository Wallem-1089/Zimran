<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$result = $problemListService->getProblemHistoryForUser((int)($_GET['id'] ?? 0), $currentUser);
if (!($result['success'] ?? false)) { http_response_code(!empty($result['audit_failed']) ? 503 : 403); exit('Problem history is unavailable.'); }
$problem = $result['data']['problem']; $history = $result['data']['history'];
$pageTitle = 'Problem History'; $moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php'; require __DIR__ . '/../../../layouts/sidebar.php';
?><div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content"><h1>Problem History</h1><h2><?= e($problem['problem_name']) ?></h2>
<?php foreach ($history as $version): ?><article class="card"><h3>Version <?= (int)$version['version_no'] ?> - <?= e($version['action']) ?></h3><p><?= e($version['changed_at']) ?> by <?= e($version['actor_name'] ?? 'Unknown') ?></p><p><strong>Reason:</strong> <?= e($version['reason']) ?></p><?php if (!empty($version['confidential_hidden'])): ?><p>Confidential version details are hidden.</p><?php else: ?><?php $before = json_decode((string)($version['previous_snapshot'] ?? ''), true) ?: []; $after = json_decode((string)($version['new_snapshot'] ?? ''), true) ?: []; ?><table><thead><tr><th>Field</th><th>Previous</th><th>New</th></tr></thead><tbody><?php foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $field): if (in_array($field, ['updated_at','created_at'], true) || ($before[$field] ?? null) === ($after[$field] ?? null)) continue; ?><tr><td><?= e(str_replace('_', ' ', ucfirst($field))) ?></td><td><?= e(is_scalar($before[$field] ?? null) ? (string)($before[$field] ?? '') : '') ?></td><td><?= e(is_scalar($after[$field] ?? null) ? (string)($after[$field] ?? '') : '') ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></article><?php endforeach; ?>
<a href="view.php?id=<?= (int)$problem['id'] ?>">Back</a></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
