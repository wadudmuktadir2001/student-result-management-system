# 🎓 Student Result Management System

A web-based **Student Result Management System** built with **HTML, CSS, PHP, and MySQL**.  
The system provides separate role-based workspaces for **Admin, Teacher, Student, and Parent** to manage and access academic information efficiently.

## ✨ Features

### 👨‍💼 Admin
- Manage users
- Manage courses and semesters
- Assign teachers to courses
- Manage student enrollments
- Link parents with students
- Manage academic results
- Monitor attendance
- View change history / audit records

### 👨‍🏫 Teacher
- Access teacher dashboard
- Manage student grades
- Record attendance
- View class reports

### 👨‍🎓 Student
- Access student dashboard
- View academic results
- View result history
- View report card
- Manage profile

### 👨‍👩‍👧 Parent
- Access parent dashboard
- View child's result history
- View report card
- Receive notifications
- Manage profile

## 🛠️ Technologies Used

- HTML
- CSS
- PHP
- MySQL
- PDO
- XAMPP

## 🔐 Security Features

- Role-based access control
- CSRF protection
- PHP sessions
- HTTP-only session cookies
- Prepared SQL statements using PDO
- Output escaping
- Secure password handling

## 📁 Project Structure

```text
student-result-management-system/
│
├── admin/
├── assets/
├── config/
├── includes/
├── parent/
├── student/
├── teacher/
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

## ⚙️ Installation

### 1. Clone the repository

```bash
git clone https://github.com/wadudmuktadir2001/student-result-management-system.git
```

### 2. Move the project

Place the project inside your XAMPP `htdocs` directory.

Example:

```text
C:\xampp\htdocs\student-result-management-system
```

### 3. Start XAMPP

Start:

- Apache
- MySQL

### 4. Create the database

Open phpMyAdmin and create a database named:

```text
student_results
```

### 5. Import the database

Import the included:

```text
database.sql
```

file into the `student_results` database.

### 6. Run the application

Open the project through your local Apache server.

Example:

```text
localhost/student-result-management-system
```

## 🗄️ Database Configuration

Default local development configuration:

```text
Host: 127.0.0.1
Port: 3306
Database: student_results
User: root
Password: empty
```

Database settings can also be configured using environment variables:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASS
```

## 👥 User Roles

| Role | Main Responsibility |
|---|---|
| Admin | Manages the complete academic system |
| Teacher | Manages grades, attendance, and reports |
| Student | Views results, history, and report cards |
| Parent | Monitors linked student's academic progress |

## 📸 Screenshots

### Login Page

<img width="1357" height="767" alt="LoginPage" src="https://github.com/user-attachments/assets/5818fbac-7fc9-4ce2-9902-74a5201d08b4" />


### Admin Dashboard

<img width="1365" height="767" alt="image" src="https://github.com/user-attachments/assets/be6fdf4e-1115-47ac-8cfa-7fe1f09fd4f8" />



### Teacher Dashboard

<img width="1365" height="767" alt="image" src="https://github.com/user-attachments/assets/403e3caa-fbbb-4659-bf99-cc1b9c3a1414" />



### Student Dashboard

<img width="1365" height="767" alt="image" src="https://github.com/user-attachments/assets/cbfef824-53e4-4f60-ad8b-90bcb19944ec" />



### Parent Dashboard

<img width="1365" height="767" alt="image" src="https://github.com/user-attachments/assets/bffa2187-1da8-488d-91a7-470bee1f3b91" />


## 🚀 Future Improvements

- Email notifications
- Advanced analytics dashboard
- Improved responsive design
- Result publication workflow
- Additional reporting features



Developed as a Web Programming project using HTML, CSS, PHP, and MySQL.
