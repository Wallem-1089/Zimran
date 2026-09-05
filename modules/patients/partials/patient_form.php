<?php

declare(strict_types=1);

$patient = $patient ?? [];
$supportedGenders = PatientService::supportedGenders();
?>

<div class="form-section">

    <h2>Personal Information</h2>

    <div class="form-grid">

        <div class="form-group">

            <label for="first_name">First Name <span class="required">*</span></label>

            <input type="text" id="first_name" name="first_name" maxlength="100"
                required value="<?= field('first_name', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="middle_name">Other Names</label>

            <input type="text" id="middle_name" name="middle_name" maxlength="100"
                value="<?= field('middle_name', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="last_name">Last Name <span class="required">*</span></label>

            <input type="text" id="last_name" name="last_name" maxlength="100"
                required value="<?= field('last_name', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="gender">Gender <span class="required">*</span></label>

            <select id="gender" name="gender" required>

                <option value="">Select Gender</option>

                <?php foreach ($supportedGenders as $genderOption): ?>

                    <option value="<?= e($genderOption) ?>"
                        <?= selected('gender', $genderOption, $patient) ?>>
                        <?= e($genderOption) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="form-group">

            <label for="date_of_birth">Date of Birth <span class="required">*</span></label>

            <input type="date" id="date_of_birth" name="date_of_birth"
                max="<?= e(date('Y-m-d')) ?>" required
                value="<?= field('date_of_birth', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="marital_status">Marital Status</label>

            <input type="text" id="marital_status" name="marital_status" maxlength="30"
                value="<?= field('marital_status', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="occupation">Occupation</label>

            <input type="text" id="occupation" name="occupation" maxlength="100"
                value="<?= field('occupation', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="place_of_work">Place of Work</label>

            <input type="text" id="place_of_work" name="place_of_work" maxlength="150"
                value="<?= field('place_of_work', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="nationality">Nationality</label>

            <input type="text" id="nationality" name="nationality" maxlength="100"
                value="<?= field('nationality', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="state_of_origin">State of Origin</label>

            <input type="text" id="state_of_origin" name="state_of_origin" maxlength="100"
                value="<?= field('state_of_origin', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="ethnic_group">Ethnic Group</label>

            <input type="text" id="ethnic_group" name="ethnic_group" maxlength="100"
                value="<?= field('ethnic_group', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="religion">Religion</label>

            <input type="text" id="religion" name="religion" maxlength="100"
                value="<?= field('religion', $patient) ?>">

        </div>

    </div>

</div>

<div class="form-section">

    <h2>Contact Information</h2>

    <div class="form-grid">

        <div class="form-group">

            <label for="phone">Phone Number</label>

            <input type="tel" id="phone" name="phone" maxlength="20"
                value="<?= field('phone', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="whatsapp_number">WhatsApp Number</label>

            <input type="tel" id="whatsapp_number" name="whatsapp_number" maxlength="20"
                value="<?= field('whatsapp_number', $patient) ?>">
            <small class="text-muted">Used for WhatsApp handoff when available; normal phone is used as fallback.</small>

        </div>

        <div class="form-group">

            <label for="email">Email Address</label>

            <input type="email" id="email" name="email" maxlength="150"
                value="<?= field('email', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="address">Address</label>

            <textarea id="address" name="address" rows="3"><?= field('address', $patient) ?></textarea>

        </div>

    </div>

</div>

<div class="form-section">

    <h2>Registration Details</h2>

    <div class="form-grid">

        <div class="form-group">

            <label for="blood_group">Blood Group</label>

            <input type="text" id="blood_group" name="blood_group" maxlength="5"
                value="<?= field('blood_group', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="genotype">Genotype</label>

            <input type="text" id="genotype" name="genotype" maxlength="5"
                value="<?= field('genotype', $patient) ?>">

        </div>

    </div>

</div>

<div class="form-section">

    <h2>Next of Kin</h2>

    <div class="form-grid">

        <div class="form-group">

            <label for="next_of_kin">Full Name</label>

            <input type="text" id="next_of_kin" name="next_of_kin" maxlength="150"
                value="<?= field('next_of_kin', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="next_of_kin_relationship">Relationship</label>

            <input type="text" id="next_of_kin_relationship"
                name="next_of_kin_relationship" maxlength="100"
                value="<?= field('next_of_kin_relationship', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="next_of_kin_phone">Phone Number</label>

            <input type="tel" id="next_of_kin_phone" name="next_of_kin_phone"
                maxlength="20" value="<?= field('next_of_kin_phone', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="next_of_kin_address">Address</label>

            <textarea id="next_of_kin_address" name="next_of_kin_address" rows="3"><?= field('next_of_kin_address', $patient) ?></textarea>

        </div>

    </div>

</div>
