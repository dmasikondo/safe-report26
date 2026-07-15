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
* **AES-256-GCM Encryption:** Secures all sensitive reporter data and evidentiary uploads at rest.
* **SHA-256 Chained-Hash Audit Log:** Each database transaction is cryptographically linked to the previous one, creating a mathematically verifiable, tamper-evident record of all system activity.

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
   git clone [https://github.com/dmasikondo/safe-report26.git](https://github.com/dmasikondo/safe-report26.git)
   cd safe-report26