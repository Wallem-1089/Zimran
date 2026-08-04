# Enterprise Hospital Management Information System (E-HMIS)

> **Read this file before making any code changes.**
>
> This document defines the architecture, coding standards, workflow, project goals, and development roadmap for this repository.

---

# Project Overview

This project is an Enterprise Hospital Management Information System (E-HMIS) designed to model real-world hospital workflows rather than simple CRUD operations.

The system is intended to be modular, scalable, auditable, maintainable, and production-ready.

Every patient interaction revolves around a single **Encounter (Visit)** which becomes the central object of the entire system.

---

# Technology Stack

Backend

- PHP 8+
- PDO
- MySQL
- Apache (XAMPP during development)

Frontend

- HTML5
- CSS3
- JavaScript (Vanilla)
- Responsive Design

Architecture

- Service-Oriented
- Thin Controllers
- Modular
- Enterprise Workflow

Version Control

- Git
- GitHub

Development Environment

- Visual Studio Code
- OpenAI Codex
- ChatGPT

---

# Architecture

The application follows a layered architecture.

```
Presentation Layer
    │
Controllers
    │
Business Services
    │
Database
```

Views must never contain SQL.

Controllers must never contain business logic.

Business rules belong inside Services.

---

# Folder Structure

```
authentication/
assets/
config/
database/
layouts/
modules/
services/
```

Example

```
modules/

    patients/

    visits/

    consultation/

    nursing/

    laboratory/

    radiology/

    pharmacy/

    physiotherapy/

    theatre/

    billing/

    records/

    reports/
```

---

# Core Design Principles

This project is **NOT** a CRUD application.

It is a workflow-driven hospital information system.

Every action must:

- validate business rules
- maintain workflow integrity
- create audit history
- update encounter history
- preserve data consistency

---

# Coding Standards

Always use

```php
declare(strict_types=1);
```

Use

- PDO only
- Prepared statements
- Transactions for writes

Never

- concatenate SQL
- duplicate business logic
- perform SQL inside views
- place business logic inside controllers

---

# Service Layer

Business logic belongs inside Services.

Examples

```
PatientService

VisitService

AuthService

AuditService

UserService

EncounterEventService (future)

BillingService (future)

LaboratoryService (future)
```

Controllers should simply call services.

---

# Return Convention

All service methods return

```php
[
    'success' => bool,
    'errors' => []
]
```

Additional values may be returned when appropriate.

Example

```php
[
    'success'=>true,
    'visit_id'=>15,
    'visit_number'=>'VIS-20260803-00015',
    'errors'=>[]
]
```

---

# Database Naming

Tables

snake_case

Columns

snake_case

Foreign Keys

xxx_id

Primary Keys

id

---

# PHP Naming

Classes

PascalCase

```
VisitService
```

Methods

camelCase

```
createVisit()

transferVisit()

receiveVisit()

assignDoctor()
```

Variables

camelCase

---

# Core Modules

Completed

Authentication

Patient Management

Visit Management

Transfer Workflow

Receive Workflow

Doctor Assignment

Encounter Timeline

Audit Logging

Encounter Events

---

# Future Modules

Consultation

Nursing

Laboratory

Radiology

Pharmacy

Accounts

Physiotherapy

Theatre

Medical Records

Inventory

Reports

Administration

Notifications

Appointments

API

---

# Patient Workflow

Patient Registration

↓

Create Encounter

↓

Transfer

↓

Receive

↓

Doctor Assignment

↓

Consultation

↓

Orders

↓

Laboratory

↓

Radiology

↓

Pharmacy

↓

Billing

↓

Discharge

---

# Encounter Workflow

Every encounter is tracked from creation until completion.

Nothing happens outside an encounter.

All modules contribute to the same encounter.

---

# Transfer Workflow

Transfer

↓

Pending

↓

Receive

↓

Department Workspace Opens

Transfers must create

- visit_transfers record
- encounter event
- audit log

---

# Receive Workflow

Receiving a patient

- validates encounter
- validates pending transfer
- marks transfer received
- updates encounter
- records encounter event
- records audit log

---

# Doctor Assignment

Rules

Doctor must exist.

Doctor must belong to current department.

Encounter must be active.

Assignment creates

- encounter event
- audit log

---

# Encounter Timeline

Timeline is generated from

Encounter Creation

Transfers

Receives

Assignments

Consultations

Orders

Laboratory

Radiology

Prescriptions

Billing

Payments

Discharge

Future events should automatically appear.

---

# Audit Logging

Every important workflow action should create an audit record.

Examples

Login

Logout

Transfer

Receive

Assign Doctor

Consultation

Billing

Payment

Discharge

---

# Encounter Events

Every workflow action should create an encounter event.

Future service

```
EncounterEventService
```

will centralize all event creation.

---

# Transactions

Every write operation should use

```php
beginTransaction()

commit()

rollBack()
```

No partial writes.

---

# Security

Always

Validate input.

Escape output.

Use prepared statements.

Verify authentication.

Verify permissions.

Never trust POST data.

---

# Performance

Prefer

JOINs

Indexes

Pagination

Lazy loading

Avoid

N+1 queries

Duplicate SQL

Repeated database calls

---

# UI Philosophy

Enterprise

Minimal

Consistent

Accessible

Responsive

Clear workflow

No unnecessary popups

---

# Current Project Status

Completed

✔ Authentication

✔ Patients

✔ Encounters

✔ Transfers

✔ Receive Workflow

✔ Doctor Assignment

✔ Timeline

✔ Encounter Events

✔ Audit Logs

Current Phase

➡ Consultation Module

---

# Development Roadmap

Phase 1

Authentication

Users

Roles

Departments

Completed

---

Phase 2

Patients

Completed

---

Phase 3

Visits

Completed

---

Phase 4

Transfers

Completed

---

Phase 5

Receive Workflow

Completed

---

Phase 6

Doctor Assignment

Completed

---

Phase 7

Consultation

Current

---

Phase 8

Nursing

---

Phase 9

Laboratory

---

Phase 10

Radiology

---

Phase 11

Pharmacy

---

Phase 12

Billing

---

Phase 13

Theatre

---

Phase 14

Physiotherapy

---

Phase 15

Medical Records

---

Phase 16

Reports

---

Phase 17

Administration

---

Phase 18

API

---

Phase 19

Testing

---

Phase 20

Production Deployment

---

# AI Instructions

Before making changes

1. Read this file.

2. Analyze existing architecture.

3. Reuse existing services.

4. Never duplicate logic.

5. Keep controllers thin.

6. Maintain backwards compatibility.

7. Explain architectural changes before implementation.

8. Preserve enterprise workflow.

9. Always create encounter events for workflow actions.

10. Always create audit logs where appropriate.

11. Prefer refactoring over duplication.

12. Ask before introducing breaking database changes.

---

# Code Quality Rules

Never

Duplicate SQL.

Duplicate validation.

Duplicate business logic.

Duplicate workflow.

Prefer reusable methods.

Keep methods focused.

Split large methods when necessary.

Use descriptive names.

Comment complex business rules.

---

# Git Workflow

Before every milestone

```
git add .

git commit -m "Completed Visit Transfer Workflow"
```

Never commit broken code.

---

# Long-Term Goal

Deliver a production-quality Enterprise Hospital Management Information System capable of supporting:

- Multiple departments
- Complete encounter lifecycle
- Electronic Medical Records
- Auditability
- Reporting
- Scalability
- Future mobile/API integration

This repository should evolve toward enterprise software quality rather than a typical academic CRUD project.