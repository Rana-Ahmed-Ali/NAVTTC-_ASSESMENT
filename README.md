# NAVTTC Trade Assessment System

A lightweight web application for managing **trades**, **students**, and **assessment photos** for the National Apprenticeship and Vocational Training Trust Council (NAVTTC).

## Features
- **Trade Management** – Create, read, update, and delete trade records.
- **Student Enrollment** – Assign students to trades, edit details, and remove entries.
- **Assessment Capture** – Capture practical, subjective, and objective photos directly in the browser.
- **Responsive UI** – Modern glass‑morphism design with a dark‑mode friendly colour palette.
- **RESTful PHP API** – Simple CRUD operations powered by PDO.

## Tech Stack
- **Frontend** – HTML5, CSS (vanilla, custom glass‑morphism styles), JavaScript (ES6) with Font Awesome icons.
- **Backend** – PHP 8+, PDO for MySQL interaction.
- **Database** – MySQL (or MariaDB) – a `trades` table, a `students` table, and an `uploads` folder for captured images.

## Project Structure
```
trade_app/
├─ api/
│   ├─ db.php          # PDO connection
│   └─ trades.php      # CRUD endpoint for trades & cascade delete of student uploads
├─ css/
│   └─ style.css      # Core styling (glass‑morphism, colour tokens)
├─ js/
│   └─ app.js          # UI logic, fetch API calls, camera handling
├─ uploads/            # Generated per‑student image folders (auto‑created)
├─ index.html          # Main entry point
└─ README.md           # *This file*
```

## Getting Started
### Prerequisites
- PHP 8+ with the `pdo_mysql` extension enabled.
- A MySQL/MariaDB server.
- (Optional) Git to clone the repo.

### 1. Clone / copy the repository
```bash
# If you have a remote repo
git clone <repo-url> "NAVTTC Assessment"
cd "NAVTTC Assessment/trade_app"
```
> **Note:** The folder name contains a space (`NAVTTC _ASSESMENT`). Keep the path as‑is or rename it without spaces for easier terminal use.

### 2. Set up the database
```sql
CREATE DATABASE navttc_assessment;
USE navttc_assessment;

CREATE TABLE trades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trade_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    father_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE
);
```
- Update `api/db.php` with your DB credentials (`$host`, `$dbname`, `$username`, `$password`).

### 3. Start the built‑in PHP server
```bash
php -S localhost:8000 -t .
```
- The server serves **index.html** and the **api/** folder automatically.

### 4. Open the app
Navigate to `http://localhost:8000` in your browser. You should see the NAVTTC portal UI.

##  API End‑points (PHP)
| Method | URL | Description |
|--------|-----|-------------|
| `GET` | `/api/trades.php` | List all trades (sorted newest first) |
| `POST` | `/api/trades.php` | Create a new trade – JSON body `{ "name": "Web Development" }` |
| `PUT` | `/api/trades.php` | Update a trade – JSON body `{ "id": 3, "name": "New Name" }` |
| `DELETE` | `/api/trades.php?id=3` | Delete a trade and cascade‑delete associated student upload folders |

> The student‑related endpoints live in separate API files (e.g., `students.php`, `enrollments.php`). Review those files for further CRUD actions.

## UI Customisation
- The colour palette is defined in `css/style.css` using CSS variables (`--primary-color`, `--secondary-color`, etc.).
- To switch to a dark‑mode look, adjust the `body` background or toggle the `dark-mode` class (add your own toggle button if desired).

## Uploads
Captured assessment photos are stored under `uploads/<student_id>/`. The server‑side `deleteDirectory()` helper (in `api/trades.php`) removes these folders when a trade is deleted.

## Development Tips
- **Live reload** – Run a simple file‑watcher (e.g., `nodemon` or `browser-sync`) to auto‑refresh the browser on HTML/CSS/JS changes.
- **Debugging** – PHP errors are output as JSON; check the browser console Network tab for response payloads.
- **Security** – This demo is for local/educational use. For production, add proper authentication, input sanitisation, and CSRF protection.

## License
This project is provided **as‑is** for learning purposes. Feel free to fork, modify, and adapt it to your own training programmes.

---
*Created by Ahmed Ali – NAVTTC Assessment System*
