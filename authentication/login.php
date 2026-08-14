<?php
require_once '../config/session.php';
require_once '../config/constants.php';
require_once '../config/helpers.php';

// Redirect logged-in users to the dashboard
if (isset($_SESSION['user'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

// Retrieve session messages
$error = "";
$success = "";

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (isset($_SESSION['login_errors'])) {

    $error = implode(' ', (array)$_SESSION['login_errors']);

    unset($_SESSION['login_errors']);

}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo APP_NAME; ?> | Login</title>

    <link rel="stylesheet" href="../assets/css/login.css">

    <script src="../assets/js/login.js" defer></script>

</head>

<body>

<div class="login-container">

    <!-- ================= LEFT PANEL ================= -->

    <div class="left-panel">

        <div class="branding">

            <img
                src="../assets/images/logo.png"
                alt="Hospital Logo"
                class="logo"
                onerror="this.style.display='none';">

            <h1><?php echo APP_NAME; ?></h1>

            <p class="tagline">
                Secure Hospital Information System
            </p>

        </div>

        <div class="features">

            <div class="feature">
                <span>✔</span>
                Patient Registration
            </div>

            <div class="feature">
                <span>✔</span>
                Electronic Medical Records
            </div>

            <div class="feature">
                <span>✔</span>
                Laboratory & Radiology
            </div>

            <div class="feature">
                <span>✔</span>
                Pharmacy Management
            </div>

            <div class="feature">
                <span>✔</span>
                Billing & Accounts
            </div>

            <div class="feature">
                <span>✔</span>
                Encounter Workspace
            </div>

        </div>

    </div>

    <!-- ================= RIGHT PANEL ================= -->

    <div class="right-panel">

        <div class="login-card">

            <h2>Sign In</h2>

            <p class="subtitle">
                Login to continue
            </p>

            <?php if (!empty($error)): ?>

                <div class="alert error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <?php if (!empty($success)): ?>

                <div class="alert success">
                    <?php echo htmlspecialchars($success); ?>
                </div>

            <?php endif; ?>

            <form
                id="loginForm"
                action="authenticate.php"
                method="POST"
                autocomplete="off">

                <?= csrfField() ?>

                <!-- Employee ID / Username -->

                <div class="form-group">

                    <label for="login">

                        Employee ID / Username

                    </label>

                    <input
                        type="text"
                        id="login"
                        name="login"
                        placeholder="Employee ID or Username"
                        required
                        autofocus>

                </div>

                <!-- Password -->

                <div class="form-group">

                    <label for="password">

                        Password

                    </label>

                    <div class="password-container">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required>

                        <button
                            type="button"
                            id="togglePassword"
                            class="toggle-btn">

                            Show

                        </button>

                    </div>

                </div>

                <!-- Remember Me -->

                <div class="remember">

                    <label>

                        <input
                            type="checkbox"
                            name="remember"
                            value="1">

                        Remember Me

                    </label>

                </div>

                <!-- Login Button -->

                <button
                    type="submit"
                    id="loginButton"
                    class="login-btn">

                    Sign In

                </button>

            </form>

            <div class="links">
                <span>Forgot password? Contact the System Administrator to reset your account.</span>
            </div>

        </div>

    </div>

</div>

<footer>

    <p>

        &copy; <?php echo date('Y'); ?>

        <?php echo APP_NAME; ?>

        Version <?php echo APP_VERSION; ?>

    </p>

</footer>

</body>

</html>
