<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Variables supplied by workspace.php
|--------------------------------------------------------------------------
*/

if (!isset($visit, $patient)) {

    return;

}

?>

<section
    id="tab-billing"
    class="workspace-tab">

    <div class="card">

        <div class="card-header">

            <div>

                <h2>

                    Billing & Accounts

                </h2>

                <p>

                    Patient billing, invoices, payments and account status.

                </p>

            </div>

            <div>

                <a
                    href="../../accounts/create_invoice.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    Create Invoice

                </a>

            </div>

        </div>

        <div class="summary-grid">

            <div class="summary-item">

                <span class="summary-label">

                    Encounter

                </span>

                <span class="summary-value">

                    #<?= (int)$visit['id'] ?>

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

                    Outstanding Balance

                </span>

                <span class="summary-value">

                    ₦0.00

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Payment Status

                </span>

                <span class="summary-value">

                    No Charges

                </span>

            </div>

        </div>

    </div>

    <div class="card">

        <h3>

            Current Invoice

        </h3>

        <div class="empty-state">

            No invoice has been generated for this encounter.

        </div>

    </div>

    <div class="card">

        <h3>

            Charges Summary

        </h3>

        <table class="summary-table">

            <thead>

                <tr>

                    <th>

                        Service

                    </th>

                    <th>

                        Amount

                    </th>

                    <th>

                        Status

                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td colspan="3" class="text-center">

                        No billable services recorded.

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Payment History

        </h3>

        <table class="summary-table">

            <thead>

                <tr>

                    <th>

                        Receipt No.

                    </th>

                    <th>

                        Date

                    </th>

                    <th>

                        Amount

                    </th>

                    <th>

                        Cashier

                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td colspan="4" class="text-center">

                        No payments have been recorded.

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Insurance Information

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Payment Type

                    </th>

                    <td>

                        Self Pay

                    </td>

                </tr>

                <tr>

                    <th>

                        Insurance Provider

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Policy Number

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Authorization Status

                    </th>

                    <td>

                        Not Applicable

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Financial Summary

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Total Charges

                    </th>

                    <td>

                        ₦0.00

                    </td>

                </tr>

                <tr>

                    <th>

                        Total Payments

                    </th>

                    <td>

                        ₦0.00

                    </td>

                </tr>

                <tr>

                    <th>

                        Outstanding Balance

                    </th>

                    <td>

                        ₦0.00

                    </td>

                </tr>

                <tr>

                    <th>

                        Last Payment

                    </th>

                    <td>

                        —

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Billing Notes

        </h3>

        <div class="empty-state">

            No billing notes available for this encounter.

        </div>

    </div>

</section>