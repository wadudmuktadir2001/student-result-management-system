# 🎓 Student Result Management System

A clean, role-based **Student Result Management System** developed with
**PHP, MySQL, HTML, and CSS**. It provides dedicated workspaces for
**Admin, Teacher, Student, and Parent** users to manage and access
academic information through a single web application.

The system covers core academic workflows including users, students,
courses, semesters, teacher assignments, enrollments, results,
attendance, result audit history, and parent notifications.

------------------------------------------------------------------------

## 📸 Project Screenshots

### Login Page

<img width="1365" height="767" alt="image" src="https://github.com/user-attachments/assets/d81219ce-3bb4-4e92-bbd6-84cafe0136c6" />


### Admin Dashboard

<img width="1365" height="767" alt="image" src="https://github.com/user-attachments/assets/1df032de-0547-423d-bd5a-211812c8fc93" />


### Teacher Dashboard

<img width="1365" height="767" alt="image" src="https://github.com/user-attachments/assets/c5ddcf00-ec59-4044-b386-03d2fc03af34" />


### Student Dashboard


<img width="1365" height="766" alt="Screenshot 2026-10-15 233300" src="https://github.com/user-attachments/assets/1489fb36-e23e-4e23-8dc2-55385c0b414f" />



### Parent Dashboard

<img width="1365" height="767" alt="image" src="https://github.com/user-attachments/assets/8469de24-d08b-40db-8300-d7f5e8d84e56" />


------------------------------------------------------------------------

## ✨ Core Features

### 👨‍💼 Admin

-   Dedicated admin access
-   Manage academic and user-related data
-   Manage courses and semesters
-   Manage teacher-course-semester assignments
-   Manage student enrollments
-   Manage results
-   Manage attendance
-   Review result audit/change history
-   Support parent-student relationships

### 👨‍🏫 Teacher

-   Dedicated teacher dashboard
-   Manage student grades/results
-   Manage attendance
-   Access academic reports
-   Work with assigned courses and semesters

### 👨‍🎓 Student

-   Dedicated student dashboard
-   View academic results
-   Access report information
-   PDF report-card download
-   View academic progress through available result data

### 👨‍👩‍👧 Parent

-   Dedicated parent dashboard
-   Access linked student's academic information
-   View report/result information
-   Receive academic notifications stored by the system
-   PDF report-card download

------------------------------------------------------------------------

## 🛠️ Tech Stack

  Technology       Purpose
  ---------------- -------------------------------
  HTML             Page structure
  CSS              User interface and styling
  PHP              Server-side application logic
  MySQL            Relational database
  PDO              Secure database communication
  Apache / XAMPP   Local development environment

------------------------------------------------------------------------

## 🔐 Security Features

The application includes several security-focused implementations:

-   Role-based authorization
-   Session-based authentication
-   Password hashing and verification
-   PDO prepared statements
-   CSRF protection
-   HTML output escaping
-   HTTP-only session cookies
-   Session ID regeneration after login
-   Login attempt throttling
-   POST-only logout flow

------------------------------------------------------------------------

## 👥 User Roles

  Role          Main Access
  ------------- ---------------------------------------------------------
  **Admin**     Academic administration and system management
  **Teacher**   Grades, attendance, assigned academic work and reports
  **Student**   Personal academic results and reports
  **Parent**    Linked student's academic information and notifications

After authentication, users are directed to role-specific areas of the
application.

------------------------------------------------------------------------

## 🗄️ Database Structure

The project uses the `student_results` MySQL database.

Main tables:

``` text
users
students
courses
semesters
assignments
enrollments
results
attendance
audit_logs
notifications
```

### Main Relationships

-   A user has one of four roles: Admin, Teacher, Student, or Parent.
-   Student records are connected to user accounts.
-   A student can optionally be linked to a parent account.
-   Teachers can be assigned to a course for a specific semester.
-   Students enroll in courses by semester.
-   Results belong to enrolled students, courses, and semesters.
-   Attendance is recorded by student, course, semester, and date.
-   Result changes can be recorded in audit logs.
-   Parent accounts can receive notifications.

------------------------------------------------------------------------

## 📁 Project Structure

``` text
student-result-management-system/
│
├── admin/
├── assets/
├── config/
├── includes/
├── parent/
├── student/
├── teacher/
├── screenshots/
│   ├── login-page.png
│   ├── admin-dashboard.png
│   ├── teacher-dashboard.png
│   ├── student-dashboard.png
│   └── parent-dashboard.png
│
├── admin.php
├── audit.php
├── bootstrap.php
├── config.php
├── database.sql
├── grades.php
├── index.php
├── login.php
├── logout.php
├── results.php
├── seed.php
└── README.md
```

------------------------------------------------------------------------

## ⚙️ Installation & Setup

### 1. Clone the Repository

``` bash
git clone https://github.com/wadudmuktadir2001/student-result-management-system.git
```

### 2. Open the Project

Move or clone the project inside the XAMPP `htdocs` directory.

Example:

``` text
C:\xampp\htdocs\student-result-management-system
```

### 3. Start XAMPP

Open **XAMPP Control Panel** and start:

``` text
Apache
MySQL
```

### 4. Set Up the Database

Open **phpMyAdmin** and import:

``` text
database.sql
```

The SQL script creates the database:

``` text
student_results
```

and defines the required tables and relationships.

### 5. Database Configuration

The application supports the following environment variables:

``` text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASS
```

For local development, make sure the database configuration matches your
XAMPP/MySQL environment.

### 6. Run the Application

Open your browser and visit:

``` text
http://localhost/student-result-management-system/
```

------------------------------------------------------------------------

## 🌱 Demo Data

A `seed.php` script is included for generating sample users and academic
data for development/testing.

Run it from the terminal:

``` bash
php seed.php
```

The seed script is restricted to CLI execution and skips seeding if the
database already contains users.

> Demo data is intended for local development/testing only. Do not use
> demo credentials in production.

------------------------------------------------------------------------

## 🔑 Authentication Flow

``` text
Login
  ↓
Validate Request & Credentials
  ↓
Create Authenticated Session
  ↓
Identify User Role
  ↓
Redirect to Role Dashboard
  ↓
Admin / Teacher / Student / Parent Workspace
```

The application verifies protected access through authentication and
role checks.

------------------------------------------------------------------------

## 📊 Academic Workflow

``` text
Admin creates/manages academic data
              ↓
Teacher assigned to Course + Semester
              ↓
Student enrolled in Course + Semester
              ↓
Teacher manages Result / Attendance
              ↓
Result data stored and changes can be audited
              ↓
Student accesses academic information
              ↓
Linked Parent can access relevant information/notifications
```

------------------------------------------------------------------------

## 🚀 Future Improvements

Potential improvements for future versions:

-   Password reset workflow
-   Email notification support
-   Advanced academic analytics
-   Dashboard charts and visualization
-   Automated testing
-   Production deployment configuration


------------------------------------------------------------------------

Web Programming Project

Built with **PHP • MySQL • HTML • CSS**

------------------------------------------------------------------------


## 📄 Project Purpose

This project was developed as part of the **Web Programming Course** to apply practical knowledge of web application development using **HTML, CSS, PHP, and MySQL**.

It demonstrates role-based access, database management, authentication, academic result processing, attendance management, and other core web programming concepts through a practical **Student Result Management System**.
