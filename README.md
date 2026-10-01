# Zimran E-HMIS / Hospital Management System

Version: **1.2**

## Technology Stack

- PHP 8+
- MySQL / MariaDB
- PDO
- Apache / XAMPP for local development
- HTML5
- CSS3
- Vanilla JavaScript

## Current Scope

The application is an encounter-centered hospital management system with:

- payment-gated patient registration, renewal billing, patient search, patient
  profile, printable Patient Face Sheet, patient chart, medical documents,
  clinical safety, clinical notes, and MPI support;
- encounter workspace, department transfer/receive, doctor assignment,
  department/user notifications, cancellation, reopen, and completion/discharge;
- Consultation, Emergency, Vital Signs, Nursing, Dressing Book, Drug Chart, DM
  Sheet, Laboratory, Radiology/X-Ray, ECG, Plaster, Physiotherapy, Theatre,
  Pharmacy, and Admissions;
- Pharmacy-owned price catalogue, Store inventory intake/barcode lookup, Store
  to Pharmacy stock movement, Pharmacy-led department stock movement, Stock
  Requests, Patient Stock Usage, Pharmacy dispensing, Billing Requests,
  automatic charges, invoices, payments, receipts, and controlled billing
  discounts;
- Basic Reports / registers, including Emergency Register, Laboratory Report
  Book, Radiology Report Book, and Theatre Operation Register;
- Patient Communications tracking for manual WhatsApp handoff from Laboratory,
  Radiology/X-Ray, ECG reports/attachments, and Medical Documents.

## Current Workflow Notes

- Normal registration initially captures only first name, middle name, last name,
  and phone number. Accounts must clear the generated registration bill before a
  hospital number is issued and the rest of the registration fields are enabled.
- Monthly renewal billing is required when registration validity expires before
  a new encounter can be created.
- Doctor-created clinical requests are sent to Accounts first. Departments can
  see the request but cannot work on it until Accounts/payment clearance is
  satisfied, except for Super Admin and configured emergency override paths.
- Request-only departments such as Laboratory, Radiology/X-Ray, ECG, Plaster,
  Physiotherapy, and Pharmacy work from clinical requests; they do not require
  encounter transfer ownership.
- Accounts worklists show both pending billing requests and auto-charged
  requests that still need payment/clearance.
- Pharmacy requests can include multiple billable items and multiple medication
  or inventory items using the round plus buttons.

## Medical Records / MPI

- Possible duplicate patient detection is active during registration and MPI
  review. Suspected duplicates are stored as duplicate candidate cases.
- Records/Medical Records users with duplicate-candidate permission can open
  Medical Records → Master Patient Index → Duplicate Cases to compare and
  review possible duplicates.
- Registration can be acknowledged and continued only through the duplicate
  review acknowledgement path when blocking duplicate candidates are shown.

## Printing

- Patient printing belongs on the Patient Profile / Medical Records side of the
  workflow.
- Use Patient Profile → Print Patient Face Sheet to open the dedicated printable
  patient summary page.
- Reports and department books keep their own print buttons inside the relevant
  report/register pages.

## Deployment Notes

Configuration is environment-aware. The same codebase can run locally under
`/hospital_management_system`, through an Apache alias such as `/zimran`, or at
web root `/` when `HMS_BASE_URL` and database environment variables are set
appropriately.
