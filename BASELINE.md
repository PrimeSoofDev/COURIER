# GaaTiTrack - Safe Development Baseline & Rollback Guide

**Date established:** October 8, 2026  
**Environment:** Local XAMPP (Apache / MariaDB / PHP 8.2)  
**Workspace:** `c:\xampp\htdocs\courer`  
**Backup Archive:** `c:\xampp\htdocs\courer\_backup_original`

---

## 1. Baseline Backup Inventory

A complete backup has been secured before making any architectural or visual changes:

1. **File Snapshot**: All 32 original PHP files, assets, plugins, and original databases copied into `_backup_original/`.
2. **Database Snapshot**: Full MySQL dump created at `_backup_original/gaatitrack_dump_baseline.sql` (11,765 bytes).
3. **HTTP Baseline Test**: Verified `http://localhost/courer/login.php` returns HTTP 200 OK.

---

## 2. Active Database Schema & Record Counts

| Table | Count | Primary Key | Description |
|---|---|---|---|
| `branches` | 7 | `id` (AI) | Logistics branch hubs and service points |
| `parcels` | 13 | `id` (AI) | Package records, dimensions, pricing, and statuses |
| `parcel_tracks` | 12 | `id` (AI) | Historical audit trail of parcel transitions |
| `system_settings` | 1 | `id` (AI) | Organization name, contact info, and branding |
| `users` | 5 | `id` (AI) | System accounts (Admins & Branch Staff) |

---

## 3. Account & Role Inventory (Credentials Redacted)

| ID | Name | Email | Role | Branch Assignment | Status |
|---|---|---|---|---|---|
| 1 | Mayuri K | `mayuri.infospace@gmail.com` | **Admin** (Type 1) | Global (0) | Active |
| 4 | Suhit Chavan | `suhit@gmail.com` | **Staff** (Type 2) | Branch ID 5 | Active |
| 5 | Mayuri Kale | `mayuri@admin.com` | **Staff** (Type 2) | Branch ID 5 | Active |
| 6 | Nilesh Chaure | `n@admin.com` | **Staff** (Type 2) | Branch ID 6 | Active |
| 7 | Mayuri K | `mayuri1@gmail.com` | **Staff** (Type 2) | Branch ID 9 | Active |

*Note: All account passwords are currently stored using legacy MD5 hashes in the database. During Phase 9 & 12, passwords will be transparently migrated to modern `password_hash()` (bcrypt).*

---

## 4. Current Application Routes

- **Public**:
  - `GET /courer/login.php` (Only unauthenticated entry point)
- **Admin & Staff Area** (`index.php?page={module}`):
  - `?page=home` - Operations dashboard
  - `?page=new_parcel` / `?page=edit_parcel&id={id}` - Shipment intake
  - `?page=parcel_list` / `?page=parcel_list&s={status}` - Parcel tables
  - `?page=view_parcel&id={id}` - Parcel detail modal
  - `?page=track` - Internal parcel tracker
  - `?page=branch_list` / `?page=new_branch` / `?page=edit_branch&id={id}` - Branch management
  - `?page=staff_list` / `?page=new_staff` / `?page=edit_staff&id={id}` - Staff management
  - `?page=reports` - Date & status filtered reporting
  - `?page=system_settings` - App settings
- **AJAX Router** (`POST /courer/ajax.php?action={action}`):
  - `login`, `logout`, `save_user`, `update_user`, `delete_user`, `save_branch`, `delete_branch`, `save_parcel`, `delete_parcel`, `update_parcel`, `get_parcel_heistory`, `get_report`.

---

## 5. Security & Configuration Infrastructure

1. **Environment Configuration**:
   - Central configuration file: `config.php`
   - Local environment configuration: `.env`
   - Template configuration: `.env.example`
2. **Database Connector**:
   - `db_connect.php` utilizes `.env` settings.
   - Provides `$conn` (MySQLi) for backward compatibility and `$pdo` (PDO) with prepared statements for modern features.
3. **Logging & Information Disclosure**:
   - Error logs are stored in `logs/app.log` with timestamped entries.
   - Web access to `logs/`, `.env`, `.sql`, and `_backup_original/` is blocked via `.htaccess`.
   - `display_errors` is disabled in production to eliminate sensitive error leakage.

---

## 6. Rollback Procedures

If at any point a rollback to the original pre-redesign baseline is required:

### 1. Database Rollback
Run in PowerShell or Command Prompt:
```cmd
c:\xampp\mysql\bin\mysql.exe -u root gaatitrack < c:\xampp\htdocs\courer\_backup_original\gaatitrack_dump_baseline.sql
```

### 2. Filesystem Rollback
Run in PowerShell:
```powershell
Copy-Item -Path "c:\xampp\htdocs\courer\_backup_original\*" -Destination "c:\xampp\htdocs\courer\" -Recurse -Force -Exclude "gaatitrack_dump_baseline.sql"
```
