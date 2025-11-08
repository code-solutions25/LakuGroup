# LakuGroup Kasir Application 💰

This project is a web-based Point of Sale (POS) system designed for the LakuGroup. It provides a user-friendly interface for cashiers ("Petugas" role) to manage transactions, view daily statistics, and handle user authentication. The application ensures secure login, session management, and efficient data retrieval from a MySQL database.

## 🚀 Key Features

- **User Authentication:** Secure login functionality with password verification against hashed passwords stored in the database.
- **Role-Based Access Control:** Restricts access to the dashboard based on user roles, ensuring only authorized personnel can access sensitive information.
- **Session Management:** Maintains user sessions to track logged-in users and their associated data.
- **Transaction Statistics:** Displays key transaction statistics for the current day, including total transactions, total revenue, and total items sold.
- **Database Integration:** Connects to a MySQL database to retrieve and store user and transaction data.
- **Logout Functionality:** Provides a secure logout process that clears session data and redirects users to the login page.
- **Outlet-Specific Data:** Retrieves transaction data specific to the cashier's assigned outlet.

## 🛠️ Tech Stack

- **Frontend:** HTML, CSS, Bootstrap CSS, Bootstrap Icons
- **Backend:** PHP
- **Database:** MySQL
- **Server:** (Assumed) Apache or similar web server
- **Other:** PHP's built-in session management functions, MySQLi extension

## 📦 Getting Started

### Prerequisites

- PHP 7.0 or higher
- MySQL database server
- Web server (e.g., Apache, Nginx)
- Composer (optional, for dependency management if applicable)

### Installation

1.  **Clone the repository:**

    ```bash
    git clone [repository_url]
    cd [repository_directory]
    ```

2.  **Database Setup:**

    - Create a new database named `lakugroup` in your MySQL server.
    - Import the database schema (if available) or create the necessary tables (e.g., `user`, `outlet`).  The `user` table should include fields for `username`, `password` (hashed), `nama`, `role`, `id_outlet`. The `outlet` table should include `id_outlet` and `nama_outlet`.

3.  **Configure Database Connection:**

    - Edit the `database.php` file and update the database credentials:

    ```php
    <?php
    $host = "localhost"; // or your host
    $username = "root"; // or your database username
    $password = ""; // or your database password
    $database = "lakugroup"; // or your database name

    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    ?>
    ```

4.  **Web Server Configuration:**

    - Configure your web server to point to the project's root directory.
    - Ensure that PHP is properly configured and enabled on your web server.

### Running Locally

1.  **Start the web server:**

    - If you're using Apache, ensure it's running.
    - If you're using PHP's built-in server, you can start it from the project's root directory:

    ```bash
    php -S localhost:8000
    ```

2.  **Access the application:**

    - Open your web browser and navigate to `http://localhost:8000` (or the appropriate URL based on your web server configuration).
    - You should see the login page (`index.php`).

## 💻 Usage

1.  **Login:**

    - Enter your username and password in the login form.
    - Click the "Login" button.

2.  **Dashboard:**

    - If the login is successful and you have the "Petugas" role, you will be redirected to the cashier dashboard (`dashboard_kasir/index.php`).
    - The dashboard displays key transaction statistics for the current day.

3.  **Logout:**

    - Click the "Logout" button to end your session and return to the login page.

## 📂 Project Structure

```
.
├── database.php
├── index.php
├── logout.php
├── dashboard_kasir
│   └── index.php
└── ... (other files and directories)
```

## 📸 Screenshots

(Screenshots of the login page and dashboard will be added here)

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1.  Fork the repository.
2.  Create a new branch for your feature or bug fix.
3.  Make your changes and commit them with descriptive messages.
4.  Push your changes to your forked repository.
5.  Submit a pull request.

## 📬 Contact

If you have any questions or suggestions, please feel free to contact us at [rega algifary](algifaryrega@gmail.com).

