# Safe Report26: Trustworthy Whistleblowing Infrastructure

> An open-source, cryptographically secure, and tamper-evident whistleblowing platform designed for low-bandwidth environments.

---

## 📖 About the Project

Across Sub-Saharan Africa, traditional corruption reporting mechanisms—such as physical suggestion boxes and analogue hotlines—remain vulnerable to interference and lack independent verifiability. 

This repository contains the source code for the prototype typical for trsustworthy whistleblowing infrastructure. Built using Design Science Research Methodology (DSRM), this platform provides a categorically different reporting ecosystem: one that is simultaneously anonymous, secure, institutionally integrated, and independently auditable.

## 🎯 Six-Requirement Evaluation Framework

The architecture of this platform is engineered to explicitly fulfill six critical requirements for public trust and safety in jurisdictions lacking statutory whistleblower protection:

1. **Network Anonymity:** Integration with Tor hidden service routing.
2. **Evidentiary Integrity:** Military-grade payload encryption.
3. **Accessibility:** Progressive Web Application (PWA) architecture optimized for low-bandwidth regions.
4. **Institutional Workflow Integration:** Seamless backend management for investigating bodies.
5. **Tamper-Evident Auditability:** Blockchain-inspired data structures.
6. **Open-Source Sustainability:** Fully verifiable, open-source codebase preventing "black-box" security flaws.

---

## 🛠️ Technology Stack

This system is built for long-term sustainability, leveraging the **TALL Stack** and robust relational data management:

* **Framework:** [Laravel 13](https://laravel.com/) (PHP)
* **Frontend:** [Tailwind CSS](https://tailwindcss.com/) & [Alpine.js](https://alpinejs.dev/)
* **Reactivity:** [Laravel Livewire](https://livewire.laravel.com/)
* **Database:** [PostgreSQL](https://www.postgresql.org/) (Chosen for strict data integrity and advanced JSONB support for encrypted payloads)

### Key Cryptographic Features
* **Encryption at rest (implemented):** Report text, reporter contact details, staff case notes and evidence files are encrypted with AES-256 (Laravel's AES-256-CBC with an HMAC-SHA256 signature) using a dedicated key, `SAFEREPORT_ENCRYPTION_KEY`, kept separate from `APP_KEY`.
* **Evidence protection (implemented):** Photos are re-encoded on upload to remove hidden metadata (GPS location, device model, capture time). Every evidence file gets a SHA-256 fingerprint and is stored under a random name; original filenames are never kept.
* **SHA-256 Chained-Hash Audit Log (planned):** Each record will be cryptographically linked to the previous one, creating a mathematically verifiable, tamper-evident record of system activity. The evidence fingerprints above are the first building block.

---

## 🚀 Getting Started

### Prerequisites
* PHP 8.3 or higher
* PostgreSQL 15 or higher
* Composer & NPM
* Git

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/dmasikondo/safe-report26.git
   cd safe-report26
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Create the environment file and application key**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Generate the report encryption key**
   ```bash
   php artisan safereport:key
   ```
   This writes `SAFEREPORT_ENCRYPTION_KEY` to `.env`. Report content and evidence files are encrypted with it.

   > ⚠️ **Back this key up somewhere safe, away from the server.** If it is lost, every report and evidence file becomes permanently unreadable. The command refuses to replace a key that is already set.

5. **Configure the database and mail** in `.env`
   ```dotenv
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=safereport
   DB_USERNAME=your_user
   DB_PASSWORD=your_password

   # Staff invitations are sent by email
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.example.com
   MAIL_PORT=587
   MAIL_USERNAME=you@example.com
   MAIL_PASSWORD=your_app_password
   MAIL_FROM_ADDRESS=you@example.com
   ```

6. **Run the migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```
   This creates the tables and seeds the staff roles, the default superadmin account and the institution's departments. The encryption key from step 4 must be set first, because one migration encrypts any existing report data.

   > ⚠️ The superadmin account uses `SAFEREPORT_ADMIN_EMAIL` and `SAFEREPORT_ADMIN_PASSWORD` from `.env`. Set the email to your own address before seeding. If the password is left empty, a random one is generated and printed once in the seeder output, so copy it then. Re-running the seeder never changes an existing account's password.

7. **Build the frontend and start the app**
   ```bash
   npm run build
   php artisan serve
   ```
   For development with hot reloading, queue worker and logs, use `composer run dev` instead.

8. **Run the tests** (optional)
   ```bash
   php artisan test
   ```

### Updating an existing installation

```bash
git pull
composer install
npm install && npm run build
php artisan migrate
php artisan db:seed --class=DepartmentSeeder
```
The department seeder is safe to run repeatedly; it only adds departments that are missing. Edit `database/seeders/DepartmentSeeder.php` to adapt the list to another institution.

### Moving to another server

When moving an existing database to a new server, **copy the original `SAFEREPORT_ENCRYPTION_KEY`** into the new `.env`. Do **not** run `php artisan safereport:key` there: a newly generated key cannot decrypt data encrypted with the old one. Also copy the contents of `storage/app/private/reports`, where the encrypted evidence files are kept.

### Optional settings

| Variable | Purpose |
| --- | --- |
| `SAFEREPORT_ONION_URL` | The Tor hidden-service address, shown on the homepage when set. |
