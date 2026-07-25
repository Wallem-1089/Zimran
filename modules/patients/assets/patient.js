/*
|--------------------------------------------------------------------------
| Hospital Management System
| Patients Module
|--------------------------------------------------------------------------
*/

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    initializeAutoFocus();

    initializeFormValidation();

    initializeUnsavedChangesWarning();

    initializeSubmitButtons();

});

/*
|--------------------------------------------------------------------------
| Auto Focus
|--------------------------------------------------------------------------
*/

function initializeAutoFocus() {

    const firstInput = document.querySelector(

        'input:not([type="hidden"]), select, textarea'

    );

    if (firstInput) {

        firstInput.focus();

    }

}

/*
|--------------------------------------------------------------------------
| Basic Client Validation
|--------------------------------------------------------------------------
*/

function initializeFormValidation() {

    const forms = document.querySelectorAll('form');

    forms.forEach(form => {

        form.addEventListener('submit', function (event) {

            if (!form.checkValidity()) {

                event.preventDefault();

                form.reportValidity();

            }

        });

    });

}

/*
|--------------------------------------------------------------------------
| Warn Before Leaving Unsaved Form
|--------------------------------------------------------------------------
*/

function initializeUnsavedChangesWarning() {

    const form = document.querySelector('form');

    if (!form) {
        return;
    }

    let changed = false;

    form.querySelectorAll('input, select, textarea').forEach(element => {

        element.addEventListener('input', () => {

            changed = true;

        });

        element.addEventListener('change', () => {

            changed = true;

        });

    });

    form.addEventListener('submit', () => {

        changed = false;

    });

    window.addEventListener('beforeunload', function (event) {

        if (!changed) {
            return;
        }

        event.preventDefault();

        event.returnValue = '';

    });

}

/*
|--------------------------------------------------------------------------
| Prevent Double Submission
|--------------------------------------------------------------------------
*/

function initializeSubmitButtons() {

    const forms = document.querySelectorAll('form');

    forms.forEach(form => {

        form.querySelectorAll('button[type="submit"]').forEach(button => {

            button.addEventListener('click', function () {

                setTimeout(() => {

                    form.querySelectorAll('button[type="submit"]').forEach(btn => {

                        btn.disabled = true;

                        if (!btn.dataset.originalText) {

                            btn.dataset.originalText = btn.innerHTML;

                        }

                        btn.innerHTML = 'Please wait...';

                    });

                }, 0);

            });

        });

    });

}