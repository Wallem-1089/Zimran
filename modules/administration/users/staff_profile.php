<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

$userId = (int)($_GET['id'] ?? 0);
$user = $userService->getUserById($userId);

if (!$user) {
    http_response_code(404);
    exit('User not found.');
}

administrationGuardSuperAdministratorUser($user, $currentUser, $permissionService);

$loadedProfile = $staffProfileService->getProfile($userId);
$profile = $loadedProfile['profile'] !== []
    ? $loadedProfile['profile']
    : $staffProfileService->defaultProfileFromUser($user);
$educationRows = $loadedProfile['education'];
$professionalRows = $loadedProfile['professional'];

if (!empty($_SESSION['old_staff_profile'])) {
    $old = $_SESSION['old_staff_profile'];
    $profile = $old['profile'] ?? $profile;
    $educationRows = $old['education'] ?? $educationRows;
    $professionalRows = $old['professional'] ?? $professionalRows;
    unset($_SESSION['old_staff_profile']);
}

for ($i = count($educationRows); $i < 3; $i++) {
    $educationRows[] = [];
}
for ($i = count($professionalRows); $i < 3; $i++) {
    $professionalRows[] = [];
}

$pageTitle = 'Staff Profile';
$errors = $_SESSION['administration_errors'] ?? [];
unset($_SESSION['administration_errors']);

$value = static fn (string $field): string => (string)($profile[$field] ?? '');
$dateValue = static fn (string $field): string => (string)($profile[$field] ?? '');

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <div class="page-header">
            <div>
                <h2>Staff Profile</h2>
                <p><?= e((string)$user['first_name'] . ' ' . (string)$user['last_name']) ?> · <?= e((string)$user['employee_id']) ?></p>
            </div>
            <p>
                <a class="btn-secondary" href="view.php?id=<?= $userId ?>">Back to User</a>
            </p>
        </div>

        <?php if ($errors): ?>
            <ul class="alert alert-danger">
                <?php foreach ($errors as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="staff_profile_save.php?id=<?= $userId ?>" enctype="multipart/form-data">
            <?= csrfField() ?>

            <h3>Personal Details</h3>
            <div class="card" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
                <div style="width:120px;height:120px;border:1px solid #d6e0ec;border-radius:12px;overflow:hidden;background:#f8fafc;display:flex;align-items:center;justify-content:center;">
                    <?php if (!empty($profile['profile_photo_path'])): ?>
                        <img src="<?= e(($baseUrl ?? '') . '/' . ltrim((string)$profile['profile_photo_path'], '/')) ?>" alt="Staff profile picture" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <span class="text-muted">No Photo</span>
                    <?php endif; ?>
                </div>
                <label style="flex:1;min-width:220px;">Profile Picture
                    <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small class="form-help">Upload a clear passport/profile photo. JPG, PNG, WebP, or GIF; max 2 MB.</small>
                </label>
            </div>
            <div class="form-grid">
                <label>Name (Surname)
                    <input name="surname" required value="<?= e($value('surname')) ?>">
                </label>
                <label>First Name
                    <input name="first_name" required value="<?= e($value('first_name')) ?>">
                </label>
                <label>Middle Name
                    <input name="middle_name" value="<?= e($value('middle_name')) ?>">
                </label>
                <label>Title
                    <select name="title">
                        <option value="">Select</option>
                        <?php foreach (['Mr.', 'Mrs.', 'Miss', 'Dr'] as $title): ?>
                            <option value="<?= e($title) ?>" <?= $value('title') === $title ? 'selected' : '' ?>><?= e($title) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Gender
                    <select name="gender">
                        <option value="">Select</option>
                        <?php foreach (['Male', 'Female'] as $gender): ?>
                            <option value="<?= e($gender) ?>" <?= $value('gender') === $gender ? 'selected' : '' ?>><?= e($gender) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Date of Birth
                    <input type="date" name="date_of_birth" value="<?= e($dateValue('date_of_birth')) ?>">
                </label>
                <label>Place of Birth
                    <input name="place_of_birth" value="<?= e($value('place_of_birth')) ?>">
                </label>
                <label>Marital Status
                    <select name="marital_status">
                        <option value="">Select</option>
                        <?php foreach (['Single', 'Married', 'Divorced', 'Widowed', 'Separated'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= $value('marital_status') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Home Town
                    <input name="home_town" value="<?= e($value('home_town')) ?>">
                </label>
                <label>Phone No.
                    <input name="phone_no" value="<?= e($value('phone_no')) ?>">
                </label>
                <label>Personal E-mail
                    <input type="email" name="personal_email" value="<?= e($value('personal_email')) ?>">
                </label>
                <label>Religion
                    <input name="religion" value="<?= e($value('religion')) ?>">
                </label>
                <label>National Identity Number (NIN)
                    <input name="national_identity_number" value="<?= e($value('national_identity_number')) ?>">
                </label>
            </div>

            <div class="form-grid">
                <label>Permanent Home Address
                    <textarea name="permanent_home_address" rows="3"><?= e($value('permanent_home_address')) ?></textarea>
                </label>
                <label>Residential Address <span class="text-muted">(if different)</span>
                    <textarea name="residential_address" rows="3"><?= e($value('residential_address')) ?></textarea>
                </label>
            </div>

            <h3>Employment Details</h3>
            <div class="form-grid">
                <label>Date of First Appointment into the Service
                    <input type="date" name="first_appointment_date" value="<?= e($dateValue('first_appointment_date')) ?>">
                </label>
                <label>Salary Grade Level of First Appointment
                    <input name="first_appointment_salary_grade" value="<?= e($value('first_appointment_salary_grade')) ?>">
                </label>
                <label>Employee No.
                    <input name="employee_no" required value="<?= e($value('employee_no')) ?>">
                </label>
                <label>Official Email
                    <input type="email" name="official_email" value="<?= e($value('official_email')) ?>">
                </label>
                <label>Salary Bank
                    <input name="salary_bank" value="<?= e($value('salary_bank')) ?>">
                </label>
                <label>Salary Bank Account No.
                    <input name="salary_bank_account_no" value="<?= e($value('salary_bank_account_no')) ?>">
                </label>
            </div>

            <h3>Next of Kin</h3>
            <div class="form-grid">
                <fieldset>
                    <legend>Next of Kin 1</legend>
                    <label>Name <input name="next_of_kin1_name" value="<?= e($value('next_of_kin1_name')) ?>"></label>
                    <label>Address <textarea name="next_of_kin1_address" rows="3"><?= e($value('next_of_kin1_address')) ?></textarea></label>
                    <label>Date of Birth <input type="date" name="next_of_kin1_date_of_birth" value="<?= e($dateValue('next_of_kin1_date_of_birth')) ?>"></label>
                    <label>Relationship <input name="next_of_kin1_relationship" value="<?= e($value('next_of_kin1_relationship')) ?>"></label>
                    <label>Phone Number <input name="next_of_kin1_phone" value="<?= e($value('next_of_kin1_phone')) ?>"></label>
                </fieldset>
                <fieldset>
                    <legend>Next of Kin 2</legend>
                    <label>Name <input name="next_of_kin2_name" value="<?= e($value('next_of_kin2_name')) ?>"></label>
                    <label>Address <textarea name="next_of_kin2_address" rows="3"><?= e($value('next_of_kin2_address')) ?></textarea></label>
                    <label>Date of Birth <input type="date" name="next_of_kin2_date_of_birth" value="<?= e($dateValue('next_of_kin2_date_of_birth')) ?>"></label>
                    <label>Relationship <input name="next_of_kin2_relationship" value="<?= e($value('next_of_kin2_relationship')) ?>"></label>
                    <label>Phone Number <input name="next_of_kin2_phone" value="<?= e($value('next_of_kin2_phone')) ?>"></label>
                </fieldset>
            </div>

            <h3>Educational Qualifications</h3>
            <p class="text-muted">Add as many rows as needed. Leave a row blank to ignore it.</p>
            <div class="table-responsive">
                <table class="data-table" id="education-table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Schools Attended with Dates</th>
                            <th>Course of Study</th>
                            <th>Certificates Obtained</th>
                            <th>Class of Degree</th>
                            <th>Year of Graduation</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($educationRows as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><textarea name="education[<?= $index ?>][school_attended_with_dates]" rows="2"><?= e((string)($row['school_attended_with_dates'] ?? '')) ?></textarea></td>
                                <td><input name="education[<?= $index ?>][course_of_study]" value="<?= e((string)($row['course_of_study'] ?? '')) ?>"></td>
                                <td><input name="education[<?= $index ?>][certificates_obtained]" value="<?= e((string)($row['certificates_obtained'] ?? '')) ?>"></td>
                                <td><input name="education[<?= $index ?>][class_of_degree]" value="<?= e((string)($row['class_of_degree'] ?? '')) ?>"></td>
                                <td><input name="education[<?= $index ?>][year_of_graduation]" value="<?= e((string)($row['year_of_graduation'] ?? '')) ?>"></td>
                                <td><input name="education[<?= $index ?>][remarks]" value="<?= e((string)($row['remarks'] ?? '')) ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn-secondary" data-add-row="education">Add Educational Qualification</button>

            <h3>Professional Qualifications</h3>
            <p class="text-muted">Add licenses, certifications, memberships, and other professional qualifications.</p>
            <div class="table-responsive">
                <table class="data-table" id="professional-table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Certification Type</th>
                            <th>Certificates</th>
                            <th>Certification License No.</th>
                            <th>Certification Expiration Date</th>
                            <th>Year of Attaining It</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($professionalRows as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><input name="professional[<?= $index ?>][certification_type]" value="<?= e((string)($row['certification_type'] ?? '')) ?>"></td>
                                <td><input name="professional[<?= $index ?>][certificates]" value="<?= e((string)($row['certificates'] ?? '')) ?>"></td>
                                <td><input name="professional[<?= $index ?>][certification_license_no]" value="<?= e((string)($row['certification_license_no'] ?? '')) ?>"></td>
                                <td><input type="date" name="professional[<?= $index ?>][certification_expiration_date]" value="<?= e((string)($row['certification_expiration_date'] ?? '')) ?>"></td>
                                <td><input name="professional[<?= $index ?>][year_attained]" value="<?= e((string)($row['year_attained'] ?? '')) ?>"></td>
                                <td><input name="professional[<?= $index ?>][remarks]" value="<?= e((string)($row['remarks'] ?? '')) ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn-secondary" data-add-row="professional">Add Professional Qualification</button>

            <p class="form-actions">
                <button type="submit" class="btn-primary">Save Staff Profile</button>
                <a class="btn-secondary" href="view.php?id=<?= $userId ?>">Cancel</a>
            </p>
        </form>
    </section>
</main>

<script>
document.querySelectorAll('[data-add-row]').forEach((button) => {
    button.addEventListener('click', () => {
        const type = button.getAttribute('data-add-row');
        const table = document.getElementById(type + '-table');
        const body = table.querySelector('tbody');
        const index = body.querySelectorAll('tr').length;
        const row = document.createElement('tr');
        if (type === 'education') {
            row.innerHTML = `
                <td>${index + 1}</td>
                <td><textarea name="education[${index}][school_attended_with_dates]" rows="2"></textarea></td>
                <td><input name="education[${index}][course_of_study]"></td>
                <td><input name="education[${index}][certificates_obtained]"></td>
                <td><input name="education[${index}][class_of_degree]"></td>
                <td><input name="education[${index}][year_of_graduation]"></td>
                <td><input name="education[${index}][remarks]"></td>
            `;
        } else {
            row.innerHTML = `
                <td>${index + 1}</td>
                <td><input name="professional[${index}][certification_type]"></td>
                <td><input name="professional[${index}][certificates]"></td>
                <td><input name="professional[${index}][certification_license_no]"></td>
                <td><input type="date" name="professional[${index}][certification_expiration_date]"></td>
                <td><input name="professional[${index}][year_attained]"></td>
                <td><input name="professional[${index}][remarks]"></td>
            `;
        }
        body.appendChild(row);
    });
});
</script>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
