<?php

declare(strict_types=1);

/**
 * Variables supplied by workspace.php
 *
 * @var array $patient
 * @var array $visit
 */

if (!isset($patient, $visit)) {
    return;
}


?>

<div
    id="tab-overview"
    class="workspace-tab active">

    <!-- ========================================================= -->
    <!-- Encounter Overview -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Encounter Overview

        </h2>

        <div class="summary-grid">

            <div class="summary-item">

                <span class="summary-label">

                    Patient

                </span>

                <span class="summary-value">

                    <?= e($patient['first_name']) ?>

                    <?= e($patient['last_name']) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Hospital Number

                </span>

                <span class="summary-value">

                    <?= e($patient['hospital_number']) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Encounter ID

                </span>

                <span class="summary-value">

                    #<?= (int)$visit['id'] ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Visit Date

                </span>

                <span class="summary-value">

                    <?= e($visit['created_at']) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Status

                </span>

                <span class="summary-value">

                    <?= e($visit['status']) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Department

                </span>

                <span class="summary-value">

                    <?= e($visit['department_name'] ?? 'Reception') ?>

                </span>

            </div>

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- Clinical Summary -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Clinical Summary

        </h2>

        <div class="empty-state">

            No consultation has been recorded for this encounter.

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- Nursing -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Nursing

        </h2>

        <div class="empty-state">

            No nursing assessment available.

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- Laboratory -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Laboratory

        </h2>

        <div class="empty-state">

            No laboratory requests.

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- Radiology -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Radiology

        </h2>

        <div class="empty-state">

            No radiology investigations.

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- Pharmacy -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Pharmacy

        </h2>

        <div class="empty-state">

            No medications have been prescribed.

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- Billing -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Billing Summary

        </h2>

        <div class="summary-grid">

            <div class="summary-item">

                <span class="summary-label">

                    Outstanding Balance

                </span>

                <span class="summary-value">

                    ₦0.00

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Payments

                </span>

                <span class="summary-value">

                    ₦0.00

                </span>

            </div>

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- Documents -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Documents

        </h2>

        <div class="empty-state">

            No documents uploaded.

        </div>

    </div>

</div>