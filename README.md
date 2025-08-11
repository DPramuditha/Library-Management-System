# 📚 Library Management System

<div align="center">
  <img src="https://readme-typing-svg.herokuapp.com?font=Fira+Code&pause=900&color=0C8CE9&center=true&vCenter=true&width=520&lines=Welcome+to+the+Library+Management+System;Manage+Books+and+Members+Easily;Fast.+Simple.+Reliable." alt="Typing Animation" />
</div>

<div align="center">
  
  ![Status](https://img.shields.io/badge/Status-Active-success?style=for-the-badge)
  ![Maintained](https://img.shields.io/badge/Maintained-Yes-brightgreen?style=for-the-badge)
  ![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)
  
</div>

---

## 🛠 Tech Stack

<div align="center">
  
  ![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
  ![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=for-the-badge&logo=mysql&logoColor=white)
  ![TailwindCSS](https://img.shields.io/badge/tailwindcss-%2338B2AC.svg?style=for-the-badge&logo=tailwindcss&logoColor=white)
  ![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white)
  ![CSS3](https://img.shields.io/badge/css3-%231572B6.svg?style=for-the-badge&logo=css3&logoColor=white)
  ![JavaScript](https://img.shields.io/badge/javascript-%23323330.svg?style=for-the-badge&logo=javascript&logoColor=%23F7DF1E)

</div>

---

## ✨ Features

<div align="center">
  <table>
    <tr>
      <td align="center">
        <img src="https://media.giphy.com/media/l4FGuhL4U2WyjdkaY/giphy.gif" width="90" height="90"><br>
        <b>Student Portal</b><br/><sub>Search & view book info</sub>
      </td>
      <td align="center">
        <img src="https://media.giphy.com/media/xT9IgzoKnwFNmISR8I/giphy.gif" width="90" height="90"><br>
        <b>Librarian Panel</b><br/><sub>Manage books & members</sub>
      </td>
      <td align="center">
        <img src="https://media.giphy.com/media/26tn33aiTi1jkl6H6/giphy.gif" width="90" height="90"><br>
        <b>Issue / Return</b><br/><sub>Track borrow records</sub>
      </td>
      <td align="center">
        <img src="https://media.giphy.com/media/du3J3cXyzhj75IOgvA/giphy.gif" width="90" height="90"><br>
        <b>Reports</b><br/><sub>Basic usage stats</sub>
      </td>
    </tr>
  </table>
</div>

---

## 🚀 Quick Start

### 1. Prerequisites
- PHP 7.4+ (or 8.x)
- MySQL / MariaDB
- A local server stack (XAMPP / WAMP / Laragon / MAMP)
- Web browser

### 2. Clone the Repository
```bash
git clone https://github.com/DPramuditha/Library-Management-System.git
cd Library-Management-System
```

### 3. Database Setup
1. Create a database (example):
   ```sql
   CREATE DATABASE library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Import the provided SQL file (if exists):  
   phpMyAdmin > Import > select `database.sql` (or similar)  
   (If no SQL file yet, export your current structure and commit it later.)

### 4. Configure Connection
Edit config file (example path):
```php
// config.php (example)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "library_db";
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die("Failed to connect: " . $mysqli->connect_error);
}
```

### 5. Run
- Place the project inside your server root (e.g., htdocs/ or www/)
- Visit in browser:
  ```
  http://localhost/Library-Management-System/
  ```

---



(If different, update this section to reflect the real structure.)

---

## 🖼️ Screenshots

<div align="center">
  <!-- Replace placeholders with real screenshots -->
  <img src="https://via.placeholder.com/520x260/0C8CE9/FFFFFF?text=Dashboard" alt="Dashboard" width="45%">
  <img src="https://via.placeholder.com/520x260/3F8E4D/FFFFFF?text=Book+List" alt="Book List" width="45%"><br><br>
  <img src="https://via.placeholder.com/520x260/7952B3/FFFFFF?text=Issue+Book" alt="Issue Book" width="45%">
  <img src="https://via.placeholder.com/520x260/F57C00/FFFFFF?text=Members" alt="Members" width="45%">
</div>

---

## 🔐 Basic Roles (Example)

| Role       | Capabilities                                  |
|------------|-----------------------------------------------|
| Student    | View/search books, personal profile           |
| Librarian  | CRUD books, manage members, issue/return       |


(Adjust to match actual implementation.)

---

## 🤝 Contributing

1. Fork the repository  
2. Create a feature branch:  
   ```bash
   git checkout -b feature/your-feature
   ```
3. Commit changes:  
   ```bash
   git commit -m "Add your feature"
   ```
4. Push branch:  
   ```bash
   git push origin feature/your-feature
   ```
5. Open a Pull Request

---

## 📝 License

Distributed under the MIT License. See LICENSE for details.

---

## 📧 Contact

Author: **DPramuditha**  
GitHub: https://github.com/DPramuditha  
Project: https://github.com/DPramuditha/Library-Management-System  

---

<div align="center">
  <img src="https://readme-typing-svg.herokuapp.com?font=Fira+Code&pause=900&color=FF6B6B&center=true&vCenter=true&width=500&lines=Thank+you+for+visiting!;Star+⭐+the+repo+if+you+found+it+useful;Happy+Coding!" alt="Thanks Animation" />
</div>

<div align="center">
  Made with ❤️ using PHP & MySQL
</div>

---
