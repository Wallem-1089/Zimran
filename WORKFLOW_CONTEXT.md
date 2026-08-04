# Workflow Context

> **Read this document before implementing or modifying any workflow.**
>
> This document defines the business processes, workflow rules, state transitions, permissions, validations, and audit requirements for the Enterprise Hospital Management Information System (E-HMIS).

---

# Purpose

This project models the workflow of a real hospital.

The system is **workflow-driven**, not CRUD-driven.

Every clinical and administrative activity belongs to a single **Encounter (Visit)**, which serves as the central record throughout the patient's journey.

---

# Workflow Philosophy

A patient moves through the hospital in controlled stages.

Each stage must:

* Validate business rules.
* Record audit history.
* Create encounter events.
* Preserve workflow integrity.
* Prevent unauthorized actions.
* Be fully traceable.

Every workflow action must be reproducible from the encounter timeline.

---

# Core Workflow

```
Patient Registration
        │
        ▼
Create Encounter
        │
        ▼
Transfer to Department
        │
        ▼
Department Receives Patient
        │
        ▼
Assign Doctor (where applicable)
        │
        ▼
Clinical Activities
        │
        ▼
Billing
        │
        ▼
Discharge
        │
        ▼
Encounter Completed
```

---

# Encounter Lifecycle

States

```
Created

↓

Transferred

↓

Pending Reception

↓

Received

↓

Assigned

↓

Clinical Work

↓

Completed

↓

Closed
```

Once an encounter is completed or cancelled, it becomes read-only except for authorized administrative actions.

---

# Patient Registration Workflow

Responsible Department

* Reception

Steps

1. Search for existing patient.
2. Register new patient if not found.
3. Generate hospital number.
4. Save demographics.
5. Record audit log.

Output

```
Patient Record
```

---

# Encounter Creation Workflow

Responsible Department

* Reception

Steps

1. Select patient.
2. Choose visit type.
3. Select initial department.
4. Generate visit number.
5. Create encounter.
6. Create initial transfer record.
7. Create encounter event.
8. Create audit log.

Validation

* Patient must exist.
* Department must exist.
* Visit type required.

Output

```
Active Encounter
```

---

# Transfer Workflow

Responsible Department

Current department staff.

Purpose

Move an encounter from one department to another.

Steps

1. Validate encounter.
2. Validate destination.
3. Prevent transfer to same department.
4. Prevent transfer of closed encounters.
5. Save transfer history.
6. Update encounter.
7. Reset department receive status.
8. Create encounter event.
9. Create audit log.

Transfer Types

```
Forward

Return

Referral

Discharge

Completion

Cancellation
```

Transfer Record

Stores

* From Department
* To Department
* Transfer Type
* Remarks
* Transferred By
* Transfer Time
* Received By
* Received Time

---

# Receive Workflow

Responsible Department

Receiving department.

Purpose

Officially accepts responsibility for the patient.

Rules

Cannot receive twice.

Cannot receive cancelled encounters.

Must have pending transfer.

Steps

1. Validate encounter.
2. Find pending transfer.
3. Mark transfer received.
4. Update encounter receive fields.
5. Create encounter event.
6. Create audit log.

After receiving

```
Department Workspace Unlocks
```

Before receiving

Department workspace remains locked.

---

# Department Workspace Gate

Every department must verify

```
current_department_received_status == Received
```

If Pending

Display

```
Receive Patient
```

Hide

* Consultation
* Laboratory
* Pharmacy
* Nursing
* Billing
* Clinical forms

---

# Doctor Assignment Workflow

Responsible Department

Doctor Department

Rules

Doctor must exist.

Doctor must belong to current department.

Encounter must be active.

Encounter must already be received.

Steps

1. Select doctor.
2. Assign doctor.
3. Record event.
4. Record audit.

Output

```
attending_doctor_id
```

---

# Consultation Workflow

Responsible

Doctor

Prerequisites

Encounter received.

Doctor assigned.

Workflow

Open consultation.

↓

History

↓

Examination

↓

Diagnosis

↓

Orders

↓

Prescription

↓

Save consultation.

Future Outputs

* Diagnosis
* Procedures
* Notes
* Orders
* Follow-up

---

# Nursing Workflow

Responsible

Nurse

Activities

Vitals

Pain assessment

Weight

Height

Observations

Fluid chart

Medication administration

Each save creates

Encounter Event

Audit Log

---

# Laboratory Workflow

Doctor

↓

Creates laboratory request.

↓

Laboratory receives request.

↓

Sample collection.

↓

Analysis.

↓

Results.

↓

Doctor reviews.

Every stage recorded.

---

# Radiology Workflow

Doctor

↓

Request

↓

Radiology

↓

Imaging

↓

Report

↓

Doctor review

---

# Pharmacy Workflow

Doctor issues prescription.

↓

Pharmacy verifies.

↓

Dispense medication.

↓

Update stock.

↓

Record dispensing history.

---

# Billing Workflow

Every bill belongs to

```
visit_id
```

Sources

Consultation

Laboratory

Radiology

Pharmacy

Physiotherapy

Theatre

Payments recorded separately.

---

# Physiotherapy Workflow

Referral

↓

Assessment

↓

Treatment

↓

Sessions

↓

Completion

---

# Theatre Workflow

Surgical request.

↓

Scheduling.

↓

Operation.

↓

Operation note.

↓

Recovery.

---

# Discharge Workflow

Requirements

No pending orders.

No pending bills.

Doctor approval.

Steps

1. Generate summary.
2. Billing complete.
3. Mark discharged.
4. Encounter event.
5. Audit log.

---

# Cancellation Workflow

Only authorized users.

Requires reason.

Creates

Audit

Encounter Event

---

# Timeline Rules

Every workflow action creates an encounter event.

Examples

Encounter Created

Transferred

Received

Doctor Assigned

Consultation Started

Diagnosis Recorded

Laboratory Requested

Sample Collected

Result Available

Prescription Issued

Medication Dispensed

Invoice Generated

Payment Made

Discharged

Timeline is chronological.

Timeline never deletes history.

---

# Audit Rules

Audit logs are separate from encounter events.

Audit answers

WHO

WHEN

WHAT

WHERE

Examples

Login

Logout

Transfer

Receive

Assign Doctor

Delete

Update

Discharge

---

# Permission Model

Reception

* Register Patient
* Create Encounter
* Transfer

Records

* Update demographics
* View records

Doctor

* Consultation
* Orders
* Prescriptions

Nursing

* Nursing notes
* Vitals

Laboratory

* Results

Radiology

* Reports

Pharmacy

* Dispense medication

Accounts

* Billing
* Payments

Administrator

Full access

---

# Business Rules

Never

Delete encounters.

Delete audit logs.

Delete encounter events.

Overwrite history.

Every workflow creates history.

---

# Validation Rules

Every workflow validates

Encounter exists.

User authorized.

Department correct.

Encounter active.

Workflow sequence valid.

---

# Error Handling

Business errors return

```
success = false

errors = [...]
```

Database errors

Rollback transaction.

Return meaningful error.

---

# Future Workflow Extensions

Appointments

Bed Management

Admissions

Emergency Department

ICU

Operating Theatre

Insurance

Inventory

Patient Portal

SMS Notifications

REST API

Multi-Hospital

Multi-Branch

Telemedicine

Referral Network

Clinical Decision Support

---

# AI Development Guidelines

Before implementing a workflow

1. Understand the business process.
2. Preserve workflow order.
3. Validate every transition.
4. Record encounter event.
5. Record audit log.
6. Use database transactions.
7. Prevent invalid state transitions.
8. Never duplicate workflow logic.
9. Keep controllers thin.
10. Place business logic inside Services.

---

# Development Principle

This project should behave like an enterprise Electronic Medical Record (EMR), where every patient movement, clinical action, financial transaction, and administrative decision is fully traceable.

The system should prioritize:

* Patient safety
* Workflow integrity
* Auditability
* Maintainability
* Scalability
* Production readiness

Every new module must integrate into the encounter lifecycle rather than operate as an isolated feature.
