![QwePic Logo](assets/images/logo-dark.svg) 

---

## Overview

**QwePic** is a modern digital marketplace empowering photographers to showcase and sell their work, while making it simple for individuals and event-goers to access and purchase meaningful photo memories.

---

## Mission Statement

To empower photographers by giving them a platform to showcase and sell their work while making it simple for individuals and event-goers to access and purchase meaningful photo memories.

## Vision Statement

A world where every captured moment is easily shared, preserved, and cherished through an accessible, photographer-driven digital marketplace.

---

## Brand Story

QwePic bridges photographers and communities, ensuring captured moments are accessible, fairly valued, and beautifully preserved.

---

## Core Services & Features

- **Photographer Profiles:** Display portfolios and contact info
- **Photo Albums for Sale:** Upload and sell albums from events or creative shoots
- **Hiring & Connections:** Clients can hire photographers; private albums for clients
- **Public/Private Albums:** Control album visibility
- **Digital Downloads:** Instant purchase and download of high-quality images
- **Revenue Sharing:** Photographers set prices; QwePic earns commission
- **Homepage Search:** Discover photographers, albums, and events

---

## Current Challenges

- Digital Rights Management (DRM)
- Photographer adoption and onboarding
- Customer education for platform navigation
- Platform scaling (storage, servers)
- Marketing reach and brand trust

---

## Target Customers

- Event-goers: wedding, birthday, concert guests
- Individuals & families: portraits, milestones
- Professional photographers
- Organizations & small businesses

---

## Tech Stack

- **Twig** (templates)
- **CSS** (styling, brand colors)
- **JavaScript** (interactivity)
- **PHP** (backend logic)

---

## Local Development

  - Docker Desktop 4.0+
  - Node.js 18+ (for asset builds, optional when using containers)

- **Quick Start**
  1. Copy `.env.example` to `.env` and adjust values if needed.
     ```bash
     cp .env.example .env # Windows PowerShell: copy .env.example .env
     ```
     Update `APP_SECRET`, `MYSQL_PASSWORD`, and `MYSQL_ROOT_PASSWORD` with secure values; the defaults in `.env.example` are placeholders for local development only.
     - `APP_SECRET`: A secret key used for generating tokens and hashing passwords.
     - `MYSQL_PASSWORD`: The password for the `qwepic` database user.
     - `MYSQL_ROOT_PASSWORD`: The password for the MySQL root user.
  2. Install PHP and JS dependencies (first run only):
     ```bash
     docker-compose up --build
     ```
  4. Access the services:
     - App: http://localhost:8000
     - phpMyAdmin: http://localhost:8080 (user `${MYSQL_USER}` / password `${MYSQL_PASSWORD}`)

- **Daily Commands**
  - Start in background: `docker-compose up -d`
  - Stop containers: `docker-compose down`
  - View logs: `docker-compose logs -f app`
  - Run Symfony console inside container: `docker-compose exec app php bin/console`
  - Run migrations: `docker-compose exec app php bin/console doctrine:migrations:migrate`

- **Data Persistence**
  - MySQL data is stored in the named volume `db_data`. It survives restarts; verify by creating a test table, running `docker-compose down` (without `--volumes`), and `docker-compose up` again.
  - Remove persisted data only when necessary via `docker-compose down --volumes` (wipes `db_data`) or `docker volume rm qwepic-dev_db_data`.

- **Troubleshooting**
  - *Port in use*: Stop other services using ports 8000/8080/3306 or change `APP_PORT`, `PHPMYADMIN_PORT`, `DB_PORT` in `.env`.
  - *Permission issues on bind mounts*: On Windows, ensure the project directory is shared in Docker Desktop; on Linux/macOS, run `sudo chown -R $USER:$USER vendor var` if needed.
  - *Containers unhealthy*: Check database health with `docker-compose logs db` and confirm credentials match `.env`.

---

## Brand Guide

- **Logo:** Wordmark “QwePic” in Montserrat, stylized “Q” (lens icon); standalone “Q” for favicon/app
- **Colors:**
  - Space Cadet `#1d1e35` (primary text)
  - Vivid Sky Blue `#42deff` (main accent)
  - Anti-flash White `#ededed` (background)
  - Accent Coral `#FF6B6B` (call-to-action)
  - Slate Gray `#6c757d` (secondary text)
- **Typography:**
  - Logo: Montserrat Bold
  - Headings: Poppins SemiBold
  - Body: Inter Regular
- **Style:** Minimalist, grid-based layouts, vibrant photos, simple icons

---

## License

> _To be added._
