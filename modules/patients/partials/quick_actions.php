<?php

declare(strict_types=1);
/** @var array $patient */
?>


<div class="card">

    <h2>

        Quick Actions

    </h2>

    <div class="patient-actions-grid">

        <a
            href="../visits/create.php?patient=<?= (int)$patient['id'] ?>"
            class="action-card action-primary">

            <div class="action-icon">

                ➕

            </div>

            <div class="action-content">

                <strong>

                    New Encounter

                </strong>

                <span>

                    Register a new patient visit

                </span>

            </div>

        </a>

        <a
            href="edit.php?id=<?= (int)$patient['id'] ?>"
            class="action-card">

            <div class="action-icon">

                ✏️

            </div>

            <div class="action-content">

                <strong>

                    Edit Patient

                </strong>

                <span>

                    Update patient information

                </span>

            </div>

        </a>

        <a
            href="history.php?id=<?= (int)$patient['id'] ?>"
            class="action-card">

            <div class="action-icon">

                📜

            </div>

            <div class="action-content">

                <strong>

                    View History

                </strong>

                <span>

                    Registration and activity history

                </span>

            </div>

        </a>

        <a
            href="search.php"
            class="action-card">

            <div class="action-icon">

                🔍

            </div>

            <div class="action-content">

                <strong>

                    Search Patients

                </strong>

                <span>

                    Find another patient

                </span>

            </div>

        </a>

        <a
            href="../billing/index.php?patient=<?= (int)$patient['id'] ?>"
            class="action-card">

            <div class="action-icon">

                💳

            </div>

            <div class="action-content">

                <strong>

                    Billing

                </strong>

                <span>

                    View patient bills

                </span>

            </div>

        </a>

        <a
            href="../laboratory/index.php?patient=<?= (int)$patient['id'] ?>"
            class="action-card">

            <div class="action-icon">

                🧪

            </div>

            <div class="action-content">

                <strong>

                    Laboratory

                </strong>

                <span>

                    Laboratory requests and results

                </span>

            </div>

        </a>

    </div>

</div>