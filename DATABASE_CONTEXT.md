# Database Context

> **Read this document before modifying the database schema or writing SQL.**
>
> This document defines the database architecture, naming conventions, relationships, integrity rules, indexing strategy, and future expansion plan for the Enterprise Hospital Management Information System (E-HMIS).

---

# Database Engine

Database

```
MySQL 8+
```

Storage Engine

```
InnoDB
```

Character Set

```
utf8mb4
```

Collation

```
utf8mb4_unicode_ci
```

Timezone

```
UTC (recommended)
```

---

# Database Design Philosophy

The database is designed around one central concept:

```
Patient
    ↓
Encounter (Visit)
    ↓
Clinical Activities
```

Nothing happens outside an encounter.

Every consultation, laboratory request, prescription, transfer, billing record, or discharge belongs to a Visit.

---

# Naming Standards

## Tables

Use plural names.

Examples

```
patients

visits

departments

users

roles

audit_logs

visit_transfers

encounter_events
```

---

## Primary Keys

Always

```
id
```

Example

```
patients.id
```

---

## Foreign Keys

Always

```
xxx_id
```

Example

```
patient_id

visit_id

doctor_id

department_id

created_by

updated_by
```

---

## Date Columns

Use

```
created_at

updated_at
```

Workflow timestamps should use descriptive names.

Examples

```
received_at

transferred_at

assigned_at

discharged_at
```

---

# Core Tables

## departments

Purpose

Defines all hospital departments.

Examples

```
Reception

Records

Doctor

Nursing

Laboratory

Radiology

Pharmacy

Accounts

Physiotherapy

Theatre

Store

Administration
```

Primary Key

```
id
```

Important Columns

```
department_name

description
```

---

## roles

Purpose

Defines user roles.

Examples

```
System Administrator

Receptionist

Doctor

Nurse

Laboratory Scientist

Radiographer

Pharmacist

Cashier

Store Officer
```

---

## users

Purpose

System users.

Relationships

```
department_id

role_id
```

Future Columns

```
is_active

last_login

password_changed_at

failed_login_attempts

must_change_password
```

---

## patients

Purpose

Stores permanent patient demographic information.

Examples

```
Hospital Number

Name

Gender

DOB

Address

Phone

Next of Kin
```

One patient

↓

Many encounters

---

## visits

Purpose

Central encounter table.

This is the heart of the system.

Every workflow references this table.

Relationships

```
patient_id

current_department_id

attending_doctor_id

created_by

current_department_received_by
```

Current workflow fields

```
visit_number

visit_type

visit_date

visit_status

queue_number

current_department_id

attending_doctor_id

current_department_received_status

current_department_received_by

current_department_received_at
```

---

# visit_transfers

Purpose

Complete transfer history.

One encounter

↓

Many transfers

Columns

```
visit_id

from_department_id

to_department_id

transfer_type

remarks

transferred_by

transferred_at

received_by

received_at
```

Transfer Types

```
Forward

Return

Referral

Discharge

Completion

Cancellation
```

---

# encounter_events

Purpose

Chronological history of everything that happens during an encounter.

Examples

```
Encounter Created

Transferred

Patient Received

Doctor Assigned

Consultation Started

Laboratory Requested

Prescription Issued

Invoice Generated

Payment Received

Discharged
```

Columns

```
visit_id

event_type

event_title

event_description

department_id

performed_by

event_time
```

---

# audit_logs

Purpose

System audit trail.

Unlike encounter events,

Audit Logs describe

WHO performed

WHAT action

WHEN

FROM WHICH IP

Examples

```
Login

Logout

Transfer

Receive

Delete

Update

Assign Doctor
```

---

# Future Tables

## consultations

One visit

↓

One consultation

Relationship

```
visit_id
```

---

## consultation_diagnoses

One consultation

↓

Many diagnoses

---

## consultation_notes

One consultation

↓

Many notes

---

## prescriptions

One consultation

↓

Many prescriptions

---

## laboratory_requests

One consultation

↓

Many requests

---

## laboratory_results

One request

↓

One or many results

---

## radiology_requests

---

## radiology_reports

---

## nursing_assessments

---

## physiotherapy_sessions

---

## theatre_operations

---

## invoices

---

## invoice_items

---

## payments

---

## discharge_summaries

---

## medical_documents

---

# Relationship Overview

```
Patients

    |

    | 1

    |

    |------< Visits

                    |

                    |------< Visit Transfers

                    |

                    |------< Encounter Events

                    |

                    |------< Consultations

                    |

                    |------< Laboratory Requests

                    |

                    |------< Prescriptions

                    |

                    |------< Billing

                    |

                    |------< Discharge
```

---

# Workflow Relationships

Patient

↓

Visit

↓

Transfer

↓

Receive

↓

Assign Doctor

↓

Consultation

↓

Orders

↓

Results

↓

Billing

↓

Discharge

Every step references

```
visit_id
```

---

# Foreign Key Rules

Always enforce

```
ON UPDATE CASCADE
```

Prefer

```
ON DELETE RESTRICT
```

Never automatically delete

Patient

Visit

Clinical History

Audit History

---

# Soft Delete Policy

Never physically delete

Patients

Visits

Consultations

Laboratory Results

Invoices

Instead use

```
is_deleted

deleted_at

deleted_by
```

where applicable.

---

# Enumerations

## visit_status

Current

```
Waiting

Reception

Records

Doctor

Nursing

Laboratory

Radiology

Pharmacy

Accounts

Completed

Cancelled
```

Future

```
Discharged
```

---

## current_department_received_status

```
Pending

Received
```

---

## transfer_type

```
Forward

Return

Referral

Discharge

Completion

Cancellation
```

---

# Index Strategy

Index every

Foreign Key

Frequently searched field

Unique identifier

Examples

```
patient_id

visit_id

department_id

doctor_id

hospital_number

visit_number
```

Composite indexes

Examples

```
(visit_id,event_time)

(current_department_id,current_department_received_status)

(patient_id,visit_date)
```

---

# Transaction Rules

Every write operation must use

```
BEGIN

COMMIT

ROLLBACK
```

Examples

Transfer

Receive

Doctor Assignment

Consultation

Billing

Discharge

---

# Audit Rules

Every important workflow writes

Audit Log

Encounter Event

Never update without history.

---

# Reporting Philosophy

Reports should never reconstruct history from the current state.

Always use

```
encounter_events

visit_transfers

audit_logs
```

These are immutable historical records.

---

# Future Scalability

Database should support

Multi-hospital

Multi-branch

Appointment scheduling

Bed management

Inventory

Insurance

Electronic prescriptions

Patient portal

REST API

Mobile application

Analytics

Business Intelligence

without redesigning the core schema.

---

# AI Instructions

Before changing the database

1. Preserve referential integrity.

2. Preserve audit history.

3. Never remove historical records.

4. Prefer additive migrations over destructive changes.

5. Explain migration impact before implementation.

6. Add indexes for new foreign keys.

7. Document every new table in this file.

8. Ensure every clinical table references `visit_id` unless there is a strong architectural reason not to.

This database is intended to evolve into a production-quality Enterprise Electronic Medical Record (EMR), prioritizing workflow integrity, auditability, and long-term maintainability over short-term convenience.
