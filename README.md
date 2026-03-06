Gemini said
Classic Realtors Property Management System (CRPMS)
A web-based platform designed to automate and streamline rental property management for landlords and tenants. CRPMS replaces manual rent collection and record-keeping with a centralized digital system featuring automated M-Pesa payments, vacancy tracking, and digital receipts.

🚀 Features
Landlord Module

Property Management: Add, view, and delete properties.


Tenant Management: Register new tenants, assign units, and manage tenant statuses (Active/Inactive/Deleted) with full audit logging.


Financial Dashboard: Track total income, view real-time occupancy charts (via Chart.js), and monitor tenant arrears.

Service Requests: View and resolve maintenance requests submitted by tenants.

Payment Collection: Initiate M-Pesa STK push requests directly to tenants with outstanding balances.

Tenant Module
Secure Access: Login using assigned house numbers and secure passwords.


Rent Payments: Direct integration with Safaricom Daraja API (M-Pesa STK Push) for instant rent payments.


Digital Receipts: View payment history and download PDF receipts automatically generated upon successful payment.

Service Requests: Submit maintenance issues directly to the landlord.

🛠️ Tech Stack

Backend: PHP 8.x (Procedural + MySQLi Prepared Statements) 


Database: MySQL 8.0 


Frontend: HTML5, CSS3, Bootstrap 5 (Dark Theme), JavaScript 


Integrations: Safaricom Daraja API (Sandbox) for M-Pesa, html2pdf.js, Chart.js


Environment: XAMPP (Apache), Windows/Linux 

📋 Prerequisites
To run this project locally, you will need:

XAMPP (with Apache and MySQL enabled)

Ngrok (required for receiving M-Pesa API callbacks on localhost)

A modern web browser

⚙️ Installation & Setup
1. Clone or Extract the Project
Place the crpms folder inside your XAMPP htdocs directory:

Windows: C:\xampp\htdocs\crpms

Linux: /opt/lampp/htdocs/crpms

2. Database Setup
Open XAMPP and start Apache and MySQL.

Go to http://localhost/phpmyadmin.

Create a new database named crpms.

Import the crpms.sql file (or run the provided database creation scripts) to set up the tables: landlords, properties, tenants, payments, service_requests, and audit_logs.

3. Apache Virtual Hosts Configuration (Required)
This system uses separate ports for the Landlord (4500) and Tenant (5500) interfaces to ensure strict role separation.

Step A: Enable Ports
Open apache/conf/httpd.conf and add:

Apache
Listen 80
Listen 4500
Listen 5500
