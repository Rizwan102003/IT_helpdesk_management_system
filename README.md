# Helpdesk Management System

## Overview

The Helpdesk Management System is a web-based platform designed to streamline IT-related complaint management in an organizational setting. This system allows employees to register complaints related to software, hardware, and networking issues, which are then escalated through hierarchical levels for resolution. It replaces traditional call-based support with a more structured, trackable workflow.

---

## Features

- **User Roles:**
  - **Employee (L3):** Submit complaints, track status, and view remarks.
  - **Senior Officer (L2):** Review complaints, add remarks, forward to admin, or reject.
  - **Junior Admin (L1):** Resolve and update complaint statuses.
  - **Super Admin (L0):** Assign complaints, oversee system, generate reports.

- **Complaint Lifecycle:**
  - Complaint submission by employees.
  - Escalation through movement logs.
  - Remarks from different levels stored in a separate `movement` table.
  - File upload support for complaint evidence.

- **Authentication & Authorization:**
  - Secure login based on session.
  - Role-based access control for features and pages.

- **Email Notifications:**
  - Email alerts sent to senior officers when complaints are assigned.

- **Reports & Analytics:**
  - Super Admin dashboard shows count of pending and solved complaints.

---

## Problem Solved

Previously, IT-related issues were reported informally via phone calls, resulting in:
- No proper tracking or status monitoring.
- Delayed responses and lack of accountability.
- Communication gaps between complainants and support staff.

This system digitizes and organizes the complaint process, providing:
- Transparency in complaint status.
- Structured escalation paths.
- Faster, more reliable resolutions.
- Digital records for accountability.

---

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Web Server:** Apache (XAMPP for local development)

---

## Database Schema - database name : helpdesk

### Users Table
Stores user details and roles.

```sql
CREATE TABLE users (
  employee_id VARCHAR(10) PRIMARY KEY,
  employee_name VARCHAR(100),
  designation VARCHAR(50),
  dept VARCHAR(50),
  section VARCHAR(50),
  level VARCHAR(50),
  email VARCHAR(100),
  contact VARCHAR(100),
  password VARCHAR(255)
);
```

### Complaint Table
Stores complaint details.

```sql
CREATE TABLE complaint (
  sl INT AUTO_INCREMENT PRIMARY KEY,
  complaint_id VARCHAR(20),
  employee_id VARCHAR(10) PRIMARY KEY,
  type VARCHAR(100),
  date date(4),
  status VARCHAR(50),
  description VARCHAR(50),
  file_name VARCHAR(50),
  designation VARCHAR(100),
  senior_officer VARCHAR(100),
);

```

### Movement Table
Stores movement of all complaints 

```sql
CREATE TABLE movement (
  id INT AUTO_INCREMENT PRIMARY KEY,
  complaint_id VARCHAR(20),
  sent_from VARCHAR(10),
  designation_from VARCHAR(50),
  sent_to VARCHAR(10),
  designation_to VARCHAR(50),
  status VARCHAR(50),
  remark TEXT,
  timestamp DATETIME
);
```
