/*
==========================================================
Hospital Management System
Login Page JavaScript
Version: 1.0
==========================================================
*/

document.addEventListener("DOMContentLoaded", () => {

    // ==========================================
    // ELEMENTS
    // ==========================================

    const loginForm = document.getElementById("loginForm");
    const loginInput = document.getElementById("login");
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");
    const loginButton = document.getElementById("loginButton");

    // ==========================================
    // AUTO FOCUS
    // ==========================================

    if (loginInput) {
        loginInput.focus();
    }

    // ==========================================
    // SHOW / HIDE PASSWORD
    // ==========================================

    if (togglePassword && passwordInput) {

        togglePassword.addEventListener("click", () => {

            if (passwordInput.type === "password") {

                passwordInput.type = "text";
                togglePassword.textContent = "Hide";

            } else {

                passwordInput.type = "password";
                togglePassword.textContent = "Show";

            }

        });

    }

    // ==========================================
    // REMOVE WHITESPACE
    // ==========================================

    if (loginInput) {

        loginInput.addEventListener("blur", () => {

            loginInput.value = loginInput.value.trim();

        });

    }

    // ==========================================
    // SIMPLE VALIDATION
    // ==========================================

    function validateForm() {

        let valid = true;

        loginInput.classList.remove("error");
        passwordInput.classList.remove("error");

        const login = loginInput.value.trim();
        const password = passwordInput.value;

        if (login === "") {

            loginInput.classList.add("error");
            loginInput.focus();

            valid = false;

        }

        if (password === "") {

            if (valid) {
                passwordInput.focus();
            }

            passwordInput.classList.add("error");

            valid = false;

        }

        return valid;

    }

    // ==========================================
    // LOGIN BUTTON LOADING
    // ==========================================

    function startLoading() {

        loginButton.disabled = true;

        loginButton.classList.add("loading");

        loginButton.textContent = "Signing In...";

    }

    // ==========================================
    // FORM SUBMISSION
    // ==========================================

    if (loginForm) {

        loginForm.addEventListener("submit", function (event) {

            if (!validateForm()) {

                event.preventDefault();

                return;

            }

            startLoading();

        });

    }

    // ==========================================
    // REMOVE ERROR BORDER WHILE TYPING
    // ==========================================

    [loginInput, passwordInput].forEach((input) => {

        if (!input) return;

        input.addEventListener("input", () => {

            input.classList.remove("error");

        });

    });

});