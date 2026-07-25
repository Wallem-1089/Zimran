<?php

declare(strict_types=1);

$patient = $patient ?? [];
?>

<div class="form-section">

    <h2>Personal Information</h2>

    <div class="form-grid">

        <div class="form-group">

            <label for="first_name">
                First Name <span class="required">*</span>
            </label>

            <input
                type="text"
                id="first_name"
                name="first_name"
                maxlength="100"
                required
                value="<?= field('first_name', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="last_name">
                Last Name <span class="required">*</span>
            </label>

            <input
                type="text"
                id="last_name"
                name="last_name"
                maxlength="100"
                required
                value="<?= field('last_name', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="gender">
                Gender <span class="required">*</span>
            </label>

            <select
                id="gender"
                name="gender"
                required>

                <option value="">Select Gender</option>

                <option
                    value="Male"
                    <?= selected('gender', 'Male', $patient) ?>>
                    Male
                </option>

                <option
                    value="Female"
                    <?= selected('gender', 'Female', $patient) ?>>
                    Female
                </option>

            </select>

        </div>

        <div class="form-group">

            <label for="date_of_birth">
                Date of Birth
            </label>

            <input
                type="date"
                id="date_of_birth"
                name="date_of_birth"
                value="<?= field('date_of_birth', $patient) ?>">

        </div>

    </div>

</div>

<div class="form-section">

    <h2>Contact Information</h2>

    <div class="form-grid">

        <div class="form-group">

            <label for="phone">
                Phone Number
            </label>

            <input
                type="tel"
                id="phone"
                name="phone"
                maxlength="20"
                value="<?= field('phone', $patient) ?>">

        </div>

        <div class="form-group">

            <label for="email">
                Email Address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
require_once __DIR__ . '/../../config/helpers.php';