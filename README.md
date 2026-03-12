# SWAP - Student-Faculty Exchange Portal

A PHP-based web application for managing student and faculty profile exchanges, built with MySQL database and PHPMailer for email notifications.

## 📋 Project Overview

SWAP is a web-based system that allows students and faculty members to manage their profiles, with separate dashboards and access controls for different user roles (Admin, Faculty, Student).

---

## 📁 Project Structure

### **Core Pages**
- **`login.php`** - User authentication and login interface
- **`homepage.php`** - Landing page for the application
- **`logout.php`** - User session termination
- **`password_reset_request.php`** - Request password reset
- **`reset_password.php`** - Complete password reset process

### **Admin Functions**
- **`admin_dashboard.php`** - Admin control panel
- **`admin_profile.php`** - Admin profile management

### **Faculty Functions**
- **`faculty_dashboard.php`** - Faculty control panel
- **`faculty_profile.php`** - Faculty profile management and updates
- **`faculty_to_school.php`** - Faculty school assignment functionality

### **Student Functions**
- **`stu_dashboard.php`** - Student control panel
- **`student_profile.php`** - Student profile management

### **Database & Configuration**
- **`xyz_polytechnic.sql`** - MySQL database schema and initial data dump
- **`composer.json`** & **`composer.lock`** - PHP dependencies (PHPMailer)

### **UI & Styling**
- **`styles.css`** - Main stylesheet
- **`styles-old.css`** - Deprecated styles
- **`*.png`** - Image assets (logos, backgrounds, icons)

### **Development Folders**
- **`CRUD1/`, `CRUD2/`, `CRUD3/`, `CRUD4/`** - CRUD operation implementations (Create, Read, Update, Delete)
- **`vendor/`** - Composer dependencies directory

### **Other**
- **`UNUSED_create_user.php`** - Deprecated user creation file

---

## 🚀 Getting Started

### Requirements
- PHP 7.0+
- MySQL/MariaDB
- Composer (for dependency management)
- A web server (Apache/Nginx)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/dmadz6/SWAP.git
   cd SWAP
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Set up the database**
   - Create a new MySQL database
   - Import the schema:
     ```bash
     mysql -u your_user -p your_database < xyz_polytechnic.sql
     ```

4. **Configure database connection**
   - Update database credentials in the appropriate configuration files (check individual PHP files for connection details)

5. **Run on a local server**
   ```bash
   php -S localhost:8000
   ```
   Or deploy to your web server.

---

## 🔐 User Roles

- **Admin** - Full system control (access `admin_dashboard.php`)
- **Faculty** - Profile management and school assignments (access `faculty_dashboard.php`)
- **Student** - Profile management (access `stu_dashboard.php`)

---

## 📧 Dependencies

- **PHPMailer (v6.9)** - Used for email notifications (password resets, confirmations, etc.)

---

## 📝 License

Please check the repository for license information.

---
