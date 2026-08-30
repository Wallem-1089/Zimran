<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Required Variables
|--------------------------------------------------------------------------
|
| $visit
| $activeTab
|
*/

if (!isset($visit)) {

    return;

}

$visitId = (int)$visit['id'];

$activeTab = $activeTab ?? 'overview';

function isActiveTab(
    string $tab,
    string $activeTab
): string {

    return $tab === $activeTab
        ? ' workspace-card-active'
        : '';

}

?>

<div class="card">

    <h2>

        Clinical Workspace

    </h2>

    <div class="workspace-grid">

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=overview"
            class="workspace-card<?= isActiveTab('overview', $activeTab) ?>">

            <div class="workspace-icon">

                🏠

            </div>

            <div class="workspace-content">

                <strong>

                    Overview

                </strong>

                <span>

                    Encounter summary

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=consultation"
            class="workspace-card<?= isActiveTab('consultation', $activeTab) ?>">

            <div class="workspace-icon">

                👨‍⚕️

            </div>

            <div class="workspace-content">

                <strong>

                    Consultation

                </strong>

                <span>

                    Doctor assessment

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=vitals"
            class="workspace-card<?= isActiveTab('vitals', $activeTab) ?>">

            <div class="workspace-icon">

                🩺

            </div>

            <div class="workspace-content">

                <strong>

                    Vital Signs

                </strong>

                <span>

                    Vitals and measurements

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=nursing"
            class="workspace-card<?= isActiveTab('nursing', $activeTab) ?>">

            <div class="workspace-icon">

                🩺

            </div>

            <div class="workspace-content">

                <strong>

                    Nursing

                </strong>

                <span>

                    Nursing care

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=laboratory"
            class="workspace-card<?= isActiveTab('laboratory', $activeTab) ?>">

            <div class="workspace-icon">

                🧪

            </div>

            <div class="workspace-content">

                <strong>

                    Laboratory

                </strong>

                <span>

                    Laboratory requests

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=radiology"
            class="workspace-card<?= isActiveTab('radiology', $activeTab) ?>">

            <div class="workspace-icon">

                🩻

            </div>

            <div class="workspace-content">

                <strong>

                    Radiology

                </strong>

                <span>

                    Imaging services

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=ecg"
            class="workspace-card<?= isActiveTab('ecg', $activeTab) ?>">

            <div class="workspace-icon">

                ECG

            </div>

            <div class="workspace-content">

                <strong>

                    ECG

                </strong>

                <span>

                    ECG requests

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=pharmacy"
            class="workspace-card<?= isActiveTab('pharmacy', $activeTab) ?>">

            <div class="workspace-icon">

                💊

            </div>

            <div class="workspace-content">

                <strong>

                    Pharmacy

                </strong>

                <span>

                    Prescriptions

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=billing"
            class="workspace-card<?= isActiveTab('billing', $activeTab) ?>">

            <div class="workspace-icon">

                💳

            </div>

            <div class="workspace-content">

                <strong>

                    Billing

                </strong>

                <span>

                    Charges & payments

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=stock_usage"
            class="workspace-card<?= isActiveTab('stock_usage', $activeTab) ?>">

            <div class="workspace-icon">

                📦

            </div>

            <div class="workspace-content">

                <strong>

                    Stock Used

                </strong>

                <span>

                    Patient consumables

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=admission"
            class="workspace-card<?= isActiveTab('admission', $activeTab) ?>">

            <div class="workspace-icon">

                🛏️

            </div>

            <div class="workspace-content">

                <strong>

                    Admission

                </strong>

                <span>

                    Ward & bed

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=physiotherapy"
            class="workspace-card<?= isActiveTab('physiotherapy', $activeTab) ?>">

            <div class="workspace-icon">

                🏃

            </div>

            <div class="workspace-content">

                <strong>

                    Physiotherapy

                </strong>

                <span>

                    Rehabilitation

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=theatre"
            class="workspace-card<?= isActiveTab('theatre', $activeTab) ?>">

            <div class="workspace-icon">

                🏥

            </div>

            <div class="workspace-content">

                <strong>

                    Theatre

                </strong>

                <span>

                    Surgical management

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=documents"
            class="workspace-card<?= isActiveTab('documents', $activeTab) ?>">

            <div class="workspace-icon">

                📄

            </div>

            <div class="workspace-content">

                <strong>

                    Documents

                </strong>

                <span>

                    Reports & attachments

                </span>

            </div>

        </a>

        <a
            href="workspace.php?id=<?= (int)$visitId ?>&tab=notes"
            class="workspace-card<?= isActiveTab('notes', $activeTab) ?>">

            <div class="workspace-icon">

                📝

            </div>

            <div class="workspace-content">

                <strong>

                    Notes

                </strong>

                <span>

                    Clinical notes

                </span>

            </div>

        </a>

    </div>

</div>
