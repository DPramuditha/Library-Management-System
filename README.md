<div align="center">

# 📚 Library Management System
**A Modern Web-Based Solution for Digital Library Operations**

<div align="center">
  <img src="https://readme-typing-svg.herokuapp.com?font=Fira+Code&pause=1000&color=0C8CE9&center=true&vCenter=true&width=600&lines=Welcome+to+the+Library+Management+System;Manage+Books%2C+Members+%26+AI+Summaries;Built+with+PHP+%26+Modern+Animations;Experience+Smart+Library+Management" alt="Typing SVG" />
</div>


[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://html.spec.whatwg.org/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://javascript.com)
![AI Powered](https://img.shields.io/badge/AI-Powered-purple?style=for-the-badge&logo=openai&logoColor=white)

---
</div>

## 🎯 Overview
The Library Management System is a comprehensive web application designed to streamline library operations across three distinct user roles. Built with modern web technologies, it provides an intuitive interface for managing books, users, and borrowing activities.

## ✨ Features 
<img src="https://user-images.githubusercontent.com/74038190/216122041-518ac897-8d92-4c6b-9b3f-ca01dcaf38ee.png" width="150" height="150">
<div>
  
  ## AI Book Summarizer Integration <img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Smilies/Robot.png" alt="Robot" width="50" height="50" />
 
  
  The **AI Book Summarizer** is seamlessly integrated into the student dashboard, providing:

### ✨ Features:
- **📝 Intelligent Summaries**: Get concise book summaries instantly
- **🔍 Context-Aware Responses**: AI understands book context and user queries
- **💾 Chat History**: Save and review previous conversations
</div>

### 🎓 Student Portal
| Feature | Description |
|---------|-------------|
| **Book Search & Browse** | Fast and accurate catalogue search with advanced filters |
| **Borrowing Management** | Easy book borrowing and return processes |
| **Personal Dashboard** | View borrowing history, current loans, and due dates |
| **Notifications** | Receive alerts for due dates and library announcements |

### 👩‍💼 Librarian Portal
| Feature | Description |
|---------|-------------|
| **Book Management** | Add, update, remove, and categorize book records |
| **User Administration** | Manage student accounts and borrowing privileges |
| **Transaction Processing** | Handle book issuing and returning operations |
| **Records Maintenance** | Comprehensive borrowing history and overdue tracking |
| **Reporting Tools** | Generate reports on library usage and inventory |

### ⚙️ Admin Portal
| Feature | Description |
|---------|-------------|
| **System Oversight** | Monitor overall library system performance |
| **Staff Management** | Create and manage librarian accounts |
| **Configuration** | System settings and parameter adjustments |
| **Analytics Dashboard** | Advanced reporting and usage analytics |

---
## 📸 System Dashboards

### 🎓 Student Dashboard
<div align="center">
<img src="assets/dashborad_images/student.jpg" alt="Student Dashboard" width="800" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
</div>

### 👩‍💼 Librarian Dashboard
<div align="center">
<img src="assets\dashborad_images\Librarian.jpg" alt="Librarian Dashboard" width="800" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
</div>

### ⚙️ Admin Dashboard
<div align="center">
<img src="assets\dashborad_images\admin.jpg" alt="Admin Dashboard" width="800" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
</div>

<table>
<tr>
<td width="10%">

### 📋 PREREQUISITES CHECKLIST

```yaml
🔧 System Requirements:
  ├── 💻 Windows OS
  ├── 🌐 WAMP Server 3.0+
  ├── 🐘 PHP 7.4+
  ├── 🗄️ MySQL 5.7+
  ├── 🌍 Modern Web Browser
  └── 💾 500MB+ Storage
```

</td>
</tr>
</table>

## 📁 Project Architecture

```
📦 librarymanagementsystem/
├──  admin_dashboard.php             # Admin portal files
├── librarian_dashboard.php          # Librarian portal files  
├── student_dashboard.php            # Student portal files
├──  pages/ 
│   ├── index.css                    # Common CSS files
│   └── assets/                      # CSS files
├── 🔧 login.php                     # Login and authentication
├── 🔐 logout.php                    # Logout script
├── 📂 register.php                  # User registration
├── 📜 README.md                     # Project documentation
├── 🗄️ database/                     # Database files
|         |__ libraryms.sql          # Database connection script
├── config.php                       # Configuration files
└── 🏠 index.php                     # Main entry point
└──  admin.js                       # Admin-specific scripts
└──  librarian.js                    # Librarian-specific scripts
└──  main.js                        # Common scripts
```

### ⚡ Installation Steps


<b>Step 1: WAMP Server Setup</b>

```bash
# Download WAMP Server from official website
# 🌐 Visit: http://www.wampserver.com/
# 📥 Download and install WAMP64
# ▶️ Start WAMP Server
# 🟢 Wait for green icon (all services running)
```


<b>Step 2: Setup Project</b>

```bash
# 📋 Copy project to WAMP directory
# Default path: C:\wamp64\www\librarymanagementsystem\

# 🗄️ Setup Database
# 1. Open phpMyAdmin: http://localhost/phpmyadmin
# 2. Create database: libraryms
# 3. Import SQL file (if available)
```
### 3. Project Deployment
1. Copy the project folder to WAMP's `www` directory:
   ```
   C:\wamp64\www\librarymanagementsystem\
   ```
2. Ensure all files are properly placed in the directory

### 4. Configuration
1. Update database connection settings in `config/database.php`:
   ```php
   $host = "localhost";
   $username = "root";
   $password = "";
   $database = "libraryms";
   ```

## 🌐 Running the Application

1. Start WAMP Server
2. Open your web browser
3. Navigate to: `http://localhost/librarymanagementsystem/`
4. Use the appropriate login credentials for your role

## 🔐 Default Login Credentials

### Administrator Access
- **Email**: admin@gmail.com
- **Password**: password
- **Portal**: Admin Dashboard

### Librarian Access
- **Email**: librarian@gmail.com
- **Password**: 12345678
- **Portal**: Librarian Dashboard

### Student Access
- **Email**: student@gmail.com
- **Password**: 12345678
- **Portal**: Student Dashboard
---

##  Contributing <img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Hand%20gestures/Handshake.png" alt="Handshake" width="50" height="50" />
<div align="center">

![Contributions Welcome](https://img.shields.io/badge/Contributions-Welcome-brightgreen?style=for-the-badge)

</div>

We love contributions! Here's how you can help:

1. 🍴 Fork the repository
2. 🌿 Create a feature branch (`git checkout -b feature`)
3. 💻 Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. 📤 Push to the branch (`git push origin feature`)
5. 🎉 Open a Pull Request

---

## 📧 Contact

<div align="center">

**DPramuditha**

[![GitHub](https://img.shields.io/badge/GitHub-DPramuditha-181717?style=for-the-badge&logo=github)](https://github.com/DPramuditha)
[![Email](https://img.shields.io/badge/Email-Contact%20Me-D14836?style=for-the-badge&logo=gmail)](mailto:your.email@example.com)

</div>

---

## 🌟 Show Your Support <img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Travel%20and%20places/Glowing%20Star.png" alt="Glowing Star" width="50" height="50" />

<div align="center">

If you like this project, please consider giving it a ⭐️ on GitHub!

![Star History Chart](https://api.star-history.com/svg?repos=DPramuditha/Library-Management-System&type=Date)

**Made with ❤️ by DPramuditha** <img src="https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Smilies/Red%20Heart.png" alt="Red Heart" width="25" height="25" />

<img src="https://readme-typing-svg.demolab.com?font=Fira+Code&size=16&pause=1000&color=F75C7E&center=true&vCenter=true&width=435&lines=Thank+you+for+visiting!;Happy+Coding!+%F0%9F%9A%80" alt="Footer Typing SVG" />

</div>

---
