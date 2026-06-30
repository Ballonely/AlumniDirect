# API Endpoints Backend

This directory contains the PHP backend scripts and API endpoints for the application. 

> [!CAUTION]
> **GitHub Pages Compatibility Notice:**
> These API endpoints **will not work** when hosted on GitHub Pages. GitHub Pages only supports static hosting (HTML, CSS, and client-side JavaScript). It does not provide a PHP runtime environment, nor does it host or support backend servers, databases (**MySQL**), or local stack environments like **XAMPP**.

---

## Prerequisites & Local Setup

To run and test these API endpoints locally, you need a local server environment that supports PHP and MySQL. 

### 1. Local Server Environment
We recommend using **XAMPP**, but any equivalent stack (WAMP, MAMP, or Docker) will work.
* Download and install [XAMPP](https://www.apachefriends.org/).
* Open the XAMPP Control Panel and start the **Apache** and **MySQL** modules.

### 2. File Placement
* Move this entire project folder into your local machine's server root directory:
  * **Windows:** `C:\xampp\htdocs\`
  * **macOS:** `/Applications/XAMPP/xamppfiles/htdocs/`
  * **Linux:** `/opt/lampp/htdocs/`

### 3. Database Setup
1. Open your browser and navigate to `http://localhost/phpmyadmin/`.
2. Create a new database (ensure the name matches your database connection script configuration).
3. Import your `.sql` schema file if one is provided in the repository.

---

## Testing the Endpoints

Once your XAMPP server is running and the files are in `htdocs`, you can access your endpoints locally.

* **Base URL:** `http://localhost/[your-project-folder-name]/api/`
* 
You can use API client tools like **Postman**, **Thunder Client** (VS Code Extension), or `curl` to test the requests and responses (`GET`, `POST`, `PUT`, `DELETE`).

---

## Folder Directory

* `*.php` — Live API endpoints interacting with the MySQL database.
* *(Optional)* `config/` or `db.php` — Contains database connection logic and credentials.