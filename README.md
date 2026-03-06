# Classic Realtors Property Management System (CRPMS)

A web-based platform designed to automate and streamline rental property management for landlords and tenants. CRPMS replaces manual rent collection and record-keeping with a centralized digital system featuring automated M-Pesa payments, vacancy tracking, and digital receipts.

## 🚀 Features

### Landlord Module
* [cite_start]**Property Management:** Add, view, and delete properties[cite: 181].
* [cite_start]**Tenant Management:** Register new tenants, assign units, and manage tenant statuses (Active/Inactive/Deleted) with full audit logging[cite: 74].
* [cite_start]**Financial Dashboard:** Track total income, view real-time occupancy charts (via Chart.js), and monitor tenant arrears[cite: 101, 102].
* **Service Requests:** View and resolve maintenance requests submitted by tenants.
* **Payment Collection:** Initiate M-Pesa STK push requests directly to tenants with outstanding balances.

### Tenant Module
* **Secure Access:** Login using assigned house numbers and secure passwords.
* [cite_start]**Rent Payments:** Direct integration with Safaricom Daraja API (M-Pesa STK Push) for instant rent payments[cite: 59].
* [cite_start]**Digital Receipts:** View payment history and download PDF receipts automatically generated upon successful payment[cite: 89].
* **Service Requests:** Submit maintenance issues directly to the landlord.

## 🛠️ Tech Stack

* [cite_start]**Backend:** PHP 8.x (Procedural + MySQLi Prepared Statements) [cite: 68]
* [cite_start]**Database:** MySQL 8.0 [cite: 68]
* [cite_start]**Frontend:** HTML5, CSS3, Bootstrap 5 (Dark Theme), JavaScript [cite: 68]
* [cite_start]**Integrations:** Safaricom Daraja API (Sandbox) for M-Pesa[cite: 65], html2pdf.js, Chart.js
* [cite_start]**Environment:** XAMPP (Apache), Windows/Linux [cite: 78]

## 📋 Prerequisites

To run this project locally, you will need:
* **XAMPP** (with Apache and MySQL enabled)
* **Ngrok** (required for receiving M-Pesa API callbacks on localhost)
* A modern web browser

## ⚙️ Installation & Setup

### 1. Clone or Extract the Project
Place the `crpms` folder inside your XAMPP `htdocs` directory:
* Windows: `C:\xampp\htdocs\crpms`
* Linux: `/opt/lampp/htdocs/crpms`

### 2. Database Setup
1. Open XAMPP and start **Apache** and **MySQL**.
2. Go to `http://localhost/phpmyadmin`.
3. Create a new database named `crpms`.
4. Import the `crpms.sql` file (or run the provided database creation scripts) to set up the tables: `landlords`, `properties`, `tenants`, `payments`, `service_requests`, and `audit_logs`.

### 3. Apache Virtual Hosts Configuration (Required)
This system uses separate ports for the Landlord (4500) and Tenant (5500) interfaces to ensure strict role separation.

**Step A: Enable Ports**
Open `apache/conf/httpd.conf` and add:
```apache
Listen 80
Listen 4500
Listen 5500
