<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$formKey = trim((string)($_GET['form'] ?? ''));
$definition = $configurableFormService->getDefinition($formKey, $currentUser);
if (!$definition) {
    http_response_code(404);
    exit('Configurable form not found.');
}

$fields = $configurableFormService->listFields($formKey, false);
$pageTitle = 'Configure ' . (string)$definition['form_name'];

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>

    <section class="card">
        <div class="section-heading">
            <div>
                <h2><?= e((string)$definition['form_name']) ?> Fields</h2>
                <p class="text-muted">Tick Active for fields you want users to see under “Additional Configured Fields”.</p>
            </div>
            <a class="btn-secondary" href="index.php">Form Settings</a>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['validation_errors'])): ?>
            <div class="alert-danger">
                <strong>Please correct the following:</strong>
                <ul>
                    <?php foreach ((array)$_SESSION['validation_errors'] as $error): ?>
                        <li><?= e((string)$error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['validation_errors']); ?>
        <?php endif; ?>

        <form method="post" action="update.php">
            <?= csrfField() ?>
            <input type="hidden" name="form_key" value="<?= e($formKey) ?>">

            <table>
                <thead>
                    <tr>
                        <th>Active</th>
                        <th>Required</th>
                        <th>Label</th>
                        <th>Key</th>
                        <th>Type</th>
                        <th>Sort</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fields as $field): ?>
                        <tr>
                            <td><input type="checkbox" name="fields[<?= (int)$field['id'] ?>][is_active]" value="1" <?= !empty($field['is_active']) ? 'checked' : '' ?>></td>
                            <td><input type="checkbox" name="fields[<?= (int)$field['id'] ?>][is_required]" value="1" <?= !empty($field['is_required']) ? 'checked' : '' ?>></td>
                            <td><input type="text" name="fields[<?= (int)$field['id'] ?>][field_label]" value="<?= e((string)$field['field_label']) ?>" required></td>
                            <td><code><?= e((string)$field['field_key']) ?></code></td>
                            <td>
                                <select name="fields[<?= (int)$field['id'] ?>][field_type]">
                                    <?php foreach (['text','textarea','number','date','select','checkbox','yes_no'] as $type): ?>
                                        <option value="<?= e($type) ?>" <?= (string)$field['field_type'] === $type ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', '/', $type))) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" name="fields[<?= (int)$field['id'] ?>][sort_order]" value="<?= (int)$field['sort_order'] ?>"></td>
                            <td>
                                <?php
                                $options = '';
                                if (!empty($field['options_json'])) {
                                    $decoded = json_decode((string)$field['options_json'], true);
                                    $options = is_array($decoded) ? implode("\n", $decoded) : '';
                                }
                                ?>
                                <textarea name="fields[<?= (int)$field['id'] ?>][options]" rows="2" placeholder="One option per line"><?= e($options) ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Add New Extra Field</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="new_field_label">Field Label</label>
                    <input type="text" id="new_field_label" name="new_field_label" placeholder="Example: Mental Status">
                </div>
                <div class="form-group">
                    <label for="new_field_key">Field Key</label>
                    <input type="text" id="new_field_key" name="new_field_key" placeholder="Optional; generated from label if blank">
                </div>
                <div class="form-group">
                    <label for="new_field_type">Field Type</label>
                    <select id="new_field_type" name="new_field_type">
                        <option value="text">Text</option>
                        <option value="textarea" selected>Textarea</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="select">Select</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="yes_no">Yes/No</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new_field_sort_order">Sort Order</label>
                    <input type="number" id="new_field_sort_order" name="new_field_sort_order" value="100">
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="new_field_required" value="1"> Required</label>
                    <label><input type="checkbox" name="new_field_active" value="1"> Active immediately</label>
                </div>
                <div class="form-group">
                    <label for="new_field_options">Options</label>
                    <textarea id="new_field_options" name="new_field_options" rows="3" placeholder="For select fields only. One option per line."></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn-primary" type="submit">Save Form Settings</button>
                <a class="btn-secondary" href="index.php">Cancel</a>
            </div>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
