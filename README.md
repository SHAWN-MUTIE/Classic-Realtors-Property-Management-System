# Classic Realtors Property Management System (CRPMS)

A web-based platform designed to automate and streamline rental property management for landlords and tenants. CRPMS replaces manual rent collection and record‑keeping with a centralized digital system featuring automated M‑Pesa payments, vacancy tracking, and digital receipts.

---

## 🚀 Features

### Landlord Module

* **Property Management:** Add, view, and delete properties.
* **Tenant Management:** Register new tenants, assign units, and manage tenant statuses (Active, Inactive, Deleted) with full audit logging.
* **Financial Dashboard:** Track total income, view real‑time occupancy charts using Chart.js, and monitor tenant arrears.
* **Service Requests:** View and resolve maintenance requests submitted by tenants.
* **Payment Collection:** Initiate M‑Pesa STK Push requests directly to tenants with outstanding balances.

### Tenant Module

* **Secure Access:** Login using assigned house numbers and secure passwords.
* **Rent Payments:** Direct integration with the Safaricom Daraja API (M‑Pesa STK Push) for instant rent payments.
* **Digital Receipts:** View payment history and download PDF receipts automatically generated after successful payments.
* **Service Requests:** Submit maintenance issues directly to the landlord.

---

## 🧱 System Architecture

**Frontend**
HTML5 • CSS3 • Bootstrap 5 • JavaScript

**Backend**
PHP 8.x using procedural architecture with MySQLi prepared statements

**Database**
MySQL 8.0

**External Integrations**

* Safaricom Daraja API (M‑Pesa STK Push)
* Chart.js (analytics dashboard)
* html2pdf.js (digital receipt generation)

**Environment**

* XAMPP (Apache + MySQL)
* Windows or Linux

---

## 📋 Prerequisites

To run this project locally you will need:

* XAMPP with Apache and MySQL enabled
* Ngrok (required to receive M‑Pesa API callbacks on localhost)
* A modern web browser

---

## ⚙️ Installation & Setup

### 1. Clone or Extract the Project

Place the project folder inside your XAMPP `htdocs` directory.

**Windows**

```
C:\xampp\htdocs\crpms
```

**Linux**

```
/opt/lampp/htdocs/crpms
```

---

### 2. Database Setup

1. Open XAMPP and start **Apache** and **MySQL**.
2. Navigate to `http://localhost/phpmyadmin`.
3. Create a new database named `crpms`.
4. Import the `crpms.sql` file.

This will create the required tables:

* landlords
* properties
* tenants
* payments
* service_requests
* audit_logs

---

### 3. Apache Virtual Hosts Configuration

The system separates the landlord and tenant interfaces using different ports to enforce role isolation.

* **Landlord Panel:** Port 4500
* **Tenant Portal:** Port 5500

#### Step A: Enable Ports

Open the Apache configuration file:

```
apache/conf/httpd.conf
```

Add the following lines:

```apache
Listen 80
Listen 4500
Listen 5500
```

---

### Step B: Configure Virtual Hosts

Open:

```
apache/conf/extra/httpd-vhosts.conf
```

Add:

```apache
<VirtualHost *:4500>
    DocumentRoot "C:/xampp/htdocs/crpms/landlord"
    ServerName landlord.crpms.local
</VirtualHost>

<VirtualHost *:5500>
    DocumentRoot "C:/xampp/htdocs/crpms/tenant"
    ServerName tenant.crpms.local
</VirtualHost>
```

Restart Apache after saving the configuration.

---

## 💳 M‑Pesa Integration Setup

1. Create a developer account at the Safaricom Developer Portal.
2. Generate sandbox credentials for the Daraja API.
3. Configure your API keys in the project configuration file.
4. Use **Ngrok** to expose your localhost callback URL to the internet.

Example callback:

```
https://your-ngrok-url.ngrok.io/crpms/mpesa/callback.php
```

---

## 📊 Dashboard Capabilities

The landlord dashboard provides:

* Real‑time occupancy visualization
* Tenant arrears tracking
* Total revenue analytics
* Maintenance request monitoring

All charts are rendered using **Chart.js**.

---

## 🔐 Security Features

* Prepared SQL statements to prevent SQL injection
* Role‑based interface separation
* Password hashing for tenant authentication
* Audit logging for sensitive actions

---

## 📦 Future Improvements

Potential features for future versions:

* SMS payment confirmations
* Email notifications for receipts
* Automated rent reminders
* Mobile‑optimized tenant portal
* Multi‑property analytics

---

## 👨‍💻 Author

**Shawn David**
Founder, Pixel Pioneers

---

## 📄 License

This project is provided for educational and demonstration purposes.
