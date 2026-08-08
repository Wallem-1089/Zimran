<?php

declare(strict_types=1);

$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
$editing = isset($identifier);
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content"><div class="page-header"><h1><?= e($pageTitle) ?></h1></div>
<div class="card"><p>Patient: <strong><?= e($patient['hospital_number']) ?> — <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></strong></p>
<form method="post" action="<?= $editing ? 'update.php' : 'save.php' ?>">
<?= csrfField() ?>
<input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
<?php if ($editing): ?><input type="hidden" name="identifier_id" value="<?= (int)$identifier['id'] ?>"><input type="hidden" name="version" value="<?= (int)$identifier['version'] ?>"><?php endif; ?>
<div class="form-group"><label>Identifier Type</label><select name="identifier_type" required>
<?php foreach ($identifierTypes as $type): ?><option value="<?= e($type) ?>" <?= ($identifier['identifier_type'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?>
</select></div>
<div class="form-group"><label>Identifier Value</label><input name="identifier_value" required value="<?= e($identifier['identifier_value'] ?? '') ?>"></div>
<div class="form-group"><label>Issuing Authority</label><input name="issuing_authority" value="<?= e($identifier['issuing_authority'] ?? '') ?>"></div>
<div class="form-group"><label>Issue Date</label><input type="date" name="issue_date" value="<?= e($identifier['issue_date'] ?? '') ?>"></div>
<div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date" value="<?= e($identifier['expiry_date'] ?? '') ?>"></div>
<?php if (!$editing): ?><label><input type="checkbox" name="is_primary" value="1"> Primary identifier of this type</label><?php endif; ?>
<div class="form-group"><label>Reason</label><textarea name="reason" required></textarea></div>
<button class="btn-primary" type="submit"><?= $editing ? 'Save Changes' : 'Add Identifier' ?></button>
<a class="btn-secondary" href="../chart.php?patient=<?= (int)$patient['id'] ?>&tab=identifiers">Cancel</a>
</form></div></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
