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

<img width="1365" height="721" alt="image" src="https://github.com/user-attachments/assets/37dbb65f-5261-4c44-a2fb-010159b7c02a" />


### Teacher Dashboard

<img width="1365" height="719" alt="image" src="https://github.com/user-attachments/assets/d6ce1bd8-dfdf-418c-9a79-f44ac30471be" />


### Student Dashboard

<img width="1364" height="720" alt="image" src="https://github.com/user-attachments/assets/c25c5962-f2c7-40f4-a361-f4d1ea14a23d" />


### Parent Dashboard
<img width="1365" height="712" alt="image" src="https://github.com/user-attachments/assets/1aeb2914-d94c-4064-93ae-318c1ef49ca2" />


## 🚀 Future Improvements

- Email notifications
- Advanced analytics dashboard
- PDF result generation
- Improved responsive design
- Result publication workflow
- Additional reporting features

## 👨‍💻 Author

**Wadud Muktadir**

Developed as a Web Programming project using HTML, CSS, PHP, and MySQL.
