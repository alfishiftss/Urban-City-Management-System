# City Management System 🏙️

A comprehensive, role-based web application built with PHP and MySQL designed to streamline urban administration, enhance public safety, and provide data-driven insights into city operations. 

## 🌟 Features

*   **Role-Based Access Control (RBAC):** Secure authentication system tailored for Admins, Police Officers, Owners, and Renters.
*   **Citizen & Property Management:** Complete administrative CRUD capabilities to track citizens, assign roles, and map buildings to specific city areas.
*   **Crime Reporting & Verification:** 
    *   Citizens can file official (or anonymous) crime reports.
    *   Police Officers access a secure portal to review, verify, or reject pending incidents.
*   **Criminal Records Database:** A searchable dossiers system allowing law enforcement to look up citizens by NID/Phone and log historical offenses and penalties.
*   **Targeted Announcements:** A smart communications engine allowing admins to broadcast notices globally, or target specific areas/buildings.
*   **Live Analytics Dashboard:** Automatically aggregates city demographics, calculating total populations, average area rent prices, and verified crime rates using dynamic visual progress bars.

## 🛠️ Technology Stack

*   **Frontend:** HTML5, CSS3 (Custom responsive styling with a modern dashboard aesthetic)
*   **Backend:** Core PHP (MySQLi for database interactions with Prepared Statements)
*   **Database:** MySQL / MariaDB (Relational design with strict Foreign Key constraints)
*   **Environment:** XAMPP (Apache server)

## 🗄️ Database Schema Overview

The system strictly adheres to a normalized database schema to ensure data integrity:
*   `Citizen`: Tracks user details, NID, Phone (used for auth), and assigned roles.
*   `Area` & `Building`: Maps the physical infrastructure of the city.
*   `Crime` & `Crime_Report`: Separates crime definitions from actual reported incidents.
*   `Criminal_Record`: Logs permanent offenses against citizens.
*   `Announcement`: Stores active broadcasts and targeting metadata.
*   `Rent`: Tracks real estate leasing to dynamically calculate area averages.

## 🚀 Installation & Setup

1.  **Clone the Repository:**
    ```bash
    git clone https://github.com/yourusername/city-management-system.git
    ```
2.  **Move to Local Server:**
    Move the project folder into your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\city-management`).
3.  **Database Configuration:**
    *   Open phpMyAdmin (`http://localhost/phpmyadmin/`).
    *   Create a new database named `city_management`.
    *   Import the provided `city_management.sql` file to automatically build the tables and insert default values.
4.  **Run the Application:**
    Open your web browser and navigate to: `http://localhost/city-management/`


## 👥 Development Team
Sadman Rahman
Oyshi Sarkar
Nafiul Islam
