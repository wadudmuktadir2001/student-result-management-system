CREATE DATABASE IF NOT EXISTS student_results 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE student_results;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student', 'parent') NOT NULL
);

CREATE TABLE students (
    user_id INT PRIMARY KEY,
    roll_no VARCHAR(50) UNIQUE NOT NULL,
    parent_id INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parent_id) REFERENCES users(id)
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) UNIQUE NOT NULL,
    name VARCHAR(120) NOT NULL,
    credits DECIMAL(4,1) NOT NULL CHECK (credits > 0)
);

CREATE TABLE semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    UNIQUE (teacher_id, course_id, semester_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id)
);

CREATE TABLE enrollments (
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    PRIMARY KEY (student_id, course_id, semester_id),
    FOREIGN KEY (student_id) REFERENCES students(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id)
);

CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    marks DECIMAL(5,2) NOT NULL CHECK (marks BETWEEN 0 AND 100),
    comment TEXT NOT NULL,
    published TINYINT NOT NULL DEFAULT 0,
    updated_by INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (student_id, course_id, semester_id),
    FOREIGN KEY (student_id, course_id, semester_id) REFERENCES enrollments(student_id, course_id, semester_id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

CREATE TABLE attendance (
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    day DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,
    PRIMARY KEY (student_id, course_id, semester_id, day),
    FOREIGN KEY (student_id, course_id, semester_id) REFERENCES enrollments(student_id, course_id, semester_id)
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    result_id INT NOT NULL,
    actor_id INT NOT NULL,
    old_data TEXT NULL,
    new_data TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (result_id) REFERENCES results(id),
    FOREIGN KEY (actor_id) REFERENCES users(id)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id)
);