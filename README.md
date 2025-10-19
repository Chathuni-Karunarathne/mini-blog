# Mini Blog — PHP (no frameworks)

## Overview
A minimal blog application built with plain PHP, PDO (MySQL), and Bootstrap for UI. Features:
- User registration, login, logout (passwords hashed)
- Create / edit / delete blog posts with Markdown content
- Featured image upload (safe filenames, max 2MB)
- Slug generation for friendly URLs
- CSRF protection for destructive actions (delete)
- Markdown safely escaped before rendering with Parsedown (or fallback)
- Simple session-based auth and permissions

## Requirements
- PHP 7.4+ (XAMPP recommended for local)
- MySQL / MariaDB
- Apache (or other web server)
- `uploads/` directory writable by PHP

## Setup (local/XAMPP)
1. Place the project under `C:\xampp\htdocs\mini-blog`
2. Create DB via phpMyAdmin (or CLI) and import schema:
   - Database name: `mini_blog` (or change in config.php)
   - Tables: `user`, `blogPost` (see SQL in project root or in assignment)
3. Copy `config.php` at project root and update DB credentials.
4. Start Apache & MySQL. Visit `http://localhost/mini-blog/public/`

## Security notes
- Passwords hashed using `password_hash()`.
- All SQL uses prepared statements (PDO) to prevent injection.
- CSRF token added for delete action; verify token in server-side code.
- Markdown input is escaped (via `htmlspecialchars`) before parsing to avoid raw HTML.

## Files of interest
- `public/` — web-accessible pages (register, login, posts, new_post, edit_post, view_post)
- `src/helpers/` — helper files (db, auth, slugify, markdown)
- `uploads/` — user-uploaded images
- `config.php` — DB credentials (excluded from git)

## How to run demo (3-minute)
1. Register a user, log in.
2. Create and publish a post with Markdown and image.
3. View the post, edit it, and delete it (show CSRF token in HTML form).
4. Briefly explain security decisions (password hashing, prepared statements, CSRF, Markdown escape).

## Author
Your Name — Student ID (if required)

