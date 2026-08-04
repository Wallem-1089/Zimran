# Hospital Management System AI Guidelines

## Coding Style

- PHP 8.3
- Strict types enabled
- PDO only
- Enterprise architecture
- Service-oriented design
- Thin controllers
- Business logic belongs in services
- Bootstrap 5
- Prepared statements only
- Transactions for write operations

## Naming

Methods:

createVisit()
receiveVisit()
transferVisit()
assignDoctor()

Database:

snake_case

PHP:

camelCase

## Workflow

Patient

↓

Visit

↓

Receive

↓

Queue

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

## Requirements

Never duplicate business logic.

Use transactions.

Return structured arrays:

[
    'success' => true,
    'errors' => []
]

Always write encounter events.

Always write audit logs.

Never perform SQL directly in views.

Follow existing VisitService patterns.