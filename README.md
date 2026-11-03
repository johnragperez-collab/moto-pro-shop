# MOTO-PRO SPEEDWORKS & PARTS - POS & Computer Accounting System

> **Course**: IT-ACT311 3102 (BSIT 3102)  
> **Course Module**: POS Computer Accounting System  
> **Instructor**: Jewel Jan Apuya  
> **Architecture**: Single-File Web Application (`index.html`) + Supabase Cloud PostgreSQL Backend  

---

## 📌 Project Overview

**Moto-Pro Speedworks & Parts** is a comprehensive Point of Sale (POS) and Computer Accounting System built to satisfy all requirements of the BSIT 3102 POS Accounting System rubric. The system features a responsive, high-performance UI with realistic thermal receipt printing animations, built-in Web Audio synthesis sound effects, complete mouseless keyboard operation, multi-level role-based access control, and real-time two-way synchronization with a live Supabase PostgreSQL database.

---

## 🔑 Default User Accounts

The system includes pre-configured accounts representing the three distinct role tiers:

| Role | Username | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin` | `admin123` | Full access to POS, Inventory, Payroll, Accounting Reports, User Accounts, and System Audit Logs |
| **Store Manager** | `manager` | `manager123` | Access to POS, Inventory Catalog, Restocking, Suppliers, Losses, and Sales Reports |
| **Cashier** | `cashier` | `cashier123` | POS Terminal transactions, item lookup, checkout, receipt printing, and personal shift summary |

---

## 🚀 Key Features by Syllabus Module

### 1. Sales & POS Terminal
- **Barcode & Name Lookup**: Real-time product search with instant barcode matching and keyboard selection.
- **Mouseless Operation**: Complete checkout flow executable via keyboard shortcuts:
  - `F1`: Focus Barcode / Product Search input
  - `F2`: Apply Discounts (PWD / Senior Citizen / Custom)
  - `F3`: Switch Payment Method (Cash, GCash, Maya, Bank Transfer)
  - `F4`: Open Checkout & Tender Cash Modal
  - `Enter`: Confirm action / Finalize checkout
  - `Esc`: Close open modal / cancel
- **Configurable Tax Calculation**: Automatic tax calculation with selectable rates (`12% VAT`, `16%`, `20%`, or `Tax Exempt / 0%`).
- **Discounts (Senior Citizen & PWD)**: Republic Act compliant 20% discount with mandatory ID tracking and automated recalculation of tax exemptions.
- **Cashless Payment QR Display**: Displays dynamic Philippine QR codes for GCash, Maya, and Bank Transfers with transaction reference logging.
- **Soft-Copy Thermal Receipt**: Animated thermal receipt paper feed with realistic printing audio sounds, barcode, detailed item breakdown, tax/discount details, and browser print (`Ctrl + P`) support.

### 2. Inventory Management & Real-Time Sync
- **Product Catalog**: Live inventory tracking with cost price, retail price, stock level, low stock thresholds, and expiration dates.
- **Restock Management**: Record inventory batches, specify supplier, log cost, and auto-update warehouse quantities.
- **Damaged & Losses Tracking**: Log damaged items, expired products, or inventory write-offs with automatic stock deduction and accounting expense entry.
- **Low Stock & Expiration System Alerts**: Real-time notification banners and visual badges for critical inventory levels and upcoming product expiries.
- **Live Supabase Cloud Database Sync**: Native `fetch()` REST API integration with Supabase PostgreSQL (`products`, `transactions`, `inventory_logs`). Stock counts update instantly in Supabase upon checkout, restocking, or loss recording.

### 3. Supplier Management
- Comprehensive supplier directory linking vendors to product SKUs with contact details, address, and status.

### 4. HR & Payroll
- Employee directory management (Full-time, Part-time, Contractual).
- Automated salary computation: `Gross Pay = (Hourly Rate × Hours Worked) + Overtime Pay`.
- Standard deduction computation (SSS, PhilHealth, Pag-IBIG, Withholding Tax).
- Net pay payslip generator.

### 5. Accounting & Reports
- **Executive Dashboard**: Real-time KPI metrics (Today's Sales, Low Stock Count, Active Transactions, Revenue vs. Cost).
- **Comprehensive Sales & PWD Reports**: Date-range filtered sales reports, payment method breakdown, and PWD/Senior discount tax audit logs.
- **Income Statement**: Filterable statement of financial performance (`Total Sales Revenue - Cost of Goods Sold - Operating Expenses - Inventory Losses = Net Income`).
- **System Audit Logs**: Immutable chronological record of system events (user logins, stock changes, finalized sales, voided transactions, losses).

---

## 📁 Repository Structure

```text
├── index.html              # The complete, standalone POS & Accounting System (All HTML, CSS, JS)
├── supabase_schema.sql     # Full Supabase PostgreSQL schema, tables, policies, and seed data
└── README.md               # Project documentation and user guide
```

---

## 💻 How to Run

### Method 1: Direct Browser Launch (Easiest)
Simply double-click `index.html` or open it with any modern web browser (Google Chrome, Microsoft Edge, Brave, Firefox). No build step, node modules, or local server installation is required.

### Method 2: GitHub Pages (Live Online Hosting)
1. Push `index.html`, `supabase_schema.sql`, and `README.md` to your GitHub repository.
2. In your GitHub repository, navigate to **Settings** > **Pages**.
3. Under **Branch**, select `main` (or `master`) and folder `/ (root)`.
4. Click **Save**. Your live POS link will be ready in seconds!

### Method 3: Local XAMPP / Live Server
1. Place the repository folder in `C:\xampp\htdocs\motorshop pos\`.
2. Start Apache in the XAMPP Control Panel.
3. Open `http://localhost/motorshop pos/index.html` (or `http://127.0.0.1:5500/index.html` via VS Code Live Server).

---

## 🗄️ Supabase Cloud Database Configuration

The application is pre-configured to connect to the Supabase Cloud project:
- **REST Endpoint**: `https://qrybhfizyymtlgktutaj.supabase.co/rest/v1/`
- To run or inspect the SQL tables, execute `supabase_schema.sql` in the **Supabase SQL Editor**.
