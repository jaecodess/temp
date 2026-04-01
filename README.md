# Statik — Live Events & Tickets

A full-stack ticketing web application built for the INF1005 Web Systems and Technologies group project. Statik lets users discover live performances, purchase tickets securely via PayPal, manage their orders, and download PDF booking confirmations.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Local Development Setup](#local-development-setup)
- [Database Setup](#database-setup)
- [Default Credentials](#default-credentials)
- [Project Structure](#project-structure)

---

## Features

**Customer-facing**
- Browse and search events by genre, name, or keyword
- View event details with ticket category pricing and seat availability
- Shopping cart with quantity management
- Secure checkout via PayPal (Sandbox)
- Order history with downloadable PDF booking confirmations (Dompdf)
- Account self-service — update name, email, password, or delete account
- Booking confirmation email sent automatically after purchase (PHPMailer)

**Admin panel**
- CRUD management for events, ticket categories, genres, and members
- Analytics dashboard with summary stats, Chart.js visualisations (revenue by genre, top performances), seat fill rates, and recent transactions

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8 (plain, no framework) |
| **Database** | MySQL 8 via MySQLi with prepared statements |
| **Frontend** | HTML5, CSS3, Bootstrap 4, JavaScript (ES6) |
| **UI Libraries** | jQuery, Owl Carousel, Font Awesome 5, Magnific Popup |
| **Fonts** | Google Fonts — Barlow Condensed, Poppins |
| **Email** | PHPMailer (SMTP via Gmail) |
| **PDF Generation** | Dompdf v3 |
| **Charts** | Chart.js v4 (CDN) |
| **Payments** | PayPal JavaScript SDK (Sandbox) |
| **Dependency Management** | Composer |
| **Hosting** | LAMP server on Google Cloud |

---

## Local Development Setup

Because this project connects to a secure cloud database, you must set up a local configuration file and an SSH tunnel before running the code. **Do not commit your local configuration file to version control.**

**1. Create the local configuration file**

In the root directory of the project, create a file named `.env`. Add your database credentials and the PayPal sandbox client ID:

```ini
servername = "127.0.0.1"
username   = "inf1005-sqldev"
password   = "your_database_password_here"
dbname     = "statik"
port       = 3307
paypal_client_id = "your_paypal_sandbox_client_id"
```

**2. Open the SSH tunnel**

Open your terminal, Command Prompt, or PowerShell and run the following command to securely forward your local port `3307` to the remote database:

```bash
ssh -L 127.0.0.1:3307:127.0.0.1:3306 inf1005-dev@35.212.206.211
```

Leave this terminal window open in the background while you are developing.

**3. Install Composer dependencies**

```bash
composer install
```

This installs PHPMailer, Dompdf, and their dependencies into `vendor/`.

---

## Database Setup

Run the setup script once against the database to create tables and seed sample data:

```bash
mysql -u inf1005-sqldev -p -h 127.0.0.1 -P 3307 < setup.sql
```

## Project Structure

```
/
├── admin/          # Admin CRUD pages and analytics dashboard
├── inc/            # Shared includes (auth, db, header, footer)
├── css/            # Stylesheets (main, responsive, theme, cart)
├── js/             # JavaScript files
├── uploads/        # Uploaded performance images
├── vendor/         # Composer packages (gitignored)
├── setup.sql       # Database schema and seed data
├── .env            # Local credentials (gitignored)
└── composer.json   # PHP dependency definitions
```
