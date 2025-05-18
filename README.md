Hearing Aid and Accessories Stock Tracking System
This project is a simple web-based stock tracking system for hearing aids and accessories. The system allows admin users to manage product stock levels.

Features
The core features implemented so far include:

Admin Login: Only predefined admin users can log in to the system.

Session Management: The session of the logged-in admin user is maintained.

Product Addition: New products (category, brand, product name, stock quantity) can be added to the system.

List All Products: All products in the system are listed in a table.

Search and Filtering: The product list can be searched and filtered by category, brand, and product name.

Stock Query (Dropdowns): The stock status of a single product can be queried by selecting category, brand, and product name using dropdowns.

Product Sale (Listing and Query Pages): The stock quantity of a product can be decreased. The sell button is disabled when the stock is 0. The quantity to sell can be specified.

Product Editing: Existing product information can be updated.

Product Deletion: Products can be removed from the system.

Database: MySQL database is used to store product and admin user information.

User Interface: A professional-looking interface focused on desktop usage.

Technologies
The project utilizes the following technologies:

Backend: PHP

Database: MySQL (used with XAMPP)

Frontend: HTML, CSS, JavaScript (including AJAX)

Setup
To set up and run the project on your local machine, follow these steps:

Install XAMPP: Download and install XAMPP on your computer.

Create Database: Start Apache and MySQL using the XAMPP control panel. Go to http://localhost/phpmyadmin in your browser. Create a new database (e.g., isitme_cihazlari_stok). Then, execute the SQL code provided in the first step of the project development to create the urunler and admin_users tables and add the sample admin user.

Place Files: Copy the project files (PHP, CSS, JS files) into a new folder under XAMPP's htdocs directory (e.g., C:\xampp\htdocs\stock_tracking).

Configure Database Connection: Update the database connection details ($servername, $username, $password, $dbname) in all PHP files to match your XAMPP configuration. Typically, the default settings are as provided in the code.

Usage
Navigate to http://localhost/stock_tracking (or whatever you named the folder where you copied the project files) in your web browser.

The admin login page will open. Log in with the admin username and password you added to the database (default: username admin, password admin123). Remember to hash the password in a real application for security.

After successful login, you will be redirected to the admin panel home page.

You can navigate to Product Addition, List All Products, or Stock Query/Sale pages using the menu or links on the home page.

Future Enhancements
Some potential features that could be added to the project include:

Stock movement logging

Low stock alerts

More detailed reporting

Inbound product recording and supplier management

Enhanced security measures (password hashing, login attempt limiting, etc.)

