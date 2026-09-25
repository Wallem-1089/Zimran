# Zimran E-HMIS / Hospital Management System

Version: **1.1**

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

- patient registration, search, patient chart, medical documents, clinical
  safety, clinical notes, and MPI support;
- encounter workspace, department transfer/receive, doctor assignment,
  department/user notifications, cancellation, reopen, and completion/discharge;
- Consultation, Vital Signs, Nursing, Dressing Book, Drug Chart, DM Sheet,
  Laboratory, Radiology/X-Ray, ECG, POP, Physiotherapy, Theatre, Pharmacy, and
  Admissions;
- Accounts price catalogue, Store inventory, Stock Requests, Patient Stock
  Usage, Pharmacy dispensing, Billing Requests, charges, invoices, payments,
  receipts, and controlled billing discounts;
- Basic Reports / registers, including Emergency Register, Laboratory Report
  Book, Radiology Report Book, and Theatre Operation Register;
- Patient Communications tracking for manual WhatsApp handoff from Radiology
  reports and Medical Documents.

## Deployment Notes

Configuration is environment-aware. The same codebase can run locally under
`/hospital_management_system`, through an Apache alias such as `/zimran`, or at
web root `/` when `HMS_BASE_URL` and database environment variables are set
appropriately.
