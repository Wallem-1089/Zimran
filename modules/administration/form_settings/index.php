<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$pageTitle = 'Form Settings';
$definitions = $configurableFormService->listDefinitions($currentUser);

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>

    <section class="card">
        <div class="section-heading">
            <div>
                <h2>Form Settings</h2>
                <p class="text-muted">Enable optional extra fields without changing the core coded clinical forms.</p>
            </div>
            <a class="btn-secondary" href="../dashboard/index.php">Administration</a>
        </div>

        <?php if (!$configurableFormService->tablesAvailable()): ?>
            <div class="alert-info">Configurable form tables are not available yet. Apply Migration 070 to enable this page.</div>
        <?php elseif ($definitions === []): ?>
            <div class="empty-state">No configurable forms are available yet.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Form</th>
                        <th>Description</th>
                        <th>Fields</th>
                        <th>Active Fields</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($definitions as $definition): ?>
                        <tr>
                            <td><?= e((string)$definition['form_name']) ?></td>
                            <td><?= e((string)($definition['description'] ?? '')) ?></td>
                            <td><?= (int)($definition['field_count'] ?? 0) ?></td>
                            <td><?= (int)($definition['active_field_count'] ?? 0) ?></td>
                            <td><?= !empty($definition['is_active']) ? 'Active' : 'Inactive' ?></td>
                            <td><a class="btn-secondary btn-small" href="edit.php?form=<?= urlencode((string)$definition['form_key']) ?>">Configure</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
