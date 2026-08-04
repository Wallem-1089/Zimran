<?php

declare(strict_types=1);

if (!isset($visit)) {
    return;
}

?>

<div class="card receive-card">

    <div class="receive-icon">

        📥

    </div>

    <h2>

        Patient Awaiting Reception

    </h2>

    <p>

        This patient has been transferred to

        <strong>

            <?= e($visit['department_name']) ?>

        </strong>

        but has not yet been officially received.

    </p>

    <p>

        Clinical activities cannot begin until the patient is received.

    </p>

    <form

        method="POST"

        action="receive_save.php">

        <?= csrfField() ?>

        <input

            type="hidden"

            name="visit_id"

            value="<?= (int)$visit['id'] ?>">

        <button

            class="btn-primary"

            type="submit">

            Receive Patient

        </button>

    </form>

</div>
