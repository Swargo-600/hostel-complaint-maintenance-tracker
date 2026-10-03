# HostelCare — PHP + MySQL Setup Guide

This is your HostelCare frontend, now wired to a real PHP backend and your
MySQL database. Follow these steps in order.

## 1. Install XAMPP

XAMPP gives you Apache, PHP, and MySQL all in one installer. For this
project you technically only need **MySQL** from it (see Step 5 for why) —
but install the full thing, it's easier.

1. Download XAMPP for Windows from https://www.apachefriends.org
2. Install it (default settings are fine).
3. Open the **XAMPP Control Panel** and click **Start** next to **MySQL**.
   That row should turn green. (You don't need to start Apache — see Step 5.)

## 2. Open the project folder in VS Code

Unzip this project anywhere you like (Desktop, Documents, etc — it does
**not** need to go inside `htdocs`, unlike a normal XAMPP project, because
of how we're running it in Step 5).

In VS Code: `File > Open Folder` → select the `HostelCare` folder.

## 3. Import the database

1. With MySQL running in XAMPP, open your browser and go to
   `http://localhost/phpmyadmin`.
2. Click **Import** in the top menu.
3. Click **Choose File**, select `database.sql` from the HostelCare folder.
4. Click **Go**. You should see a success message and a new database called
   `HostelComplaintMaintenanceTracker` in the left sidebar.

That single file creates every table (Users, Complaint, Room, etc.) and
fills them with sample data.

## 4. Check the database connection settings

Open `config/db.php` in VS Code. By default XAMPP's MySQL uses:
```
username: root
password: (empty)
```
That's already what's in the file. You only need to change `$DB_PASS` if
you've set a MySQL root password yourself.

## 5. Run and open the project — the easy way

You don't have to copy files into `htdocs` and type a long URL every time.
PHP has its own built-in web server, and this project already has a VS
Code task set up for it:

1. In VS Code, press **Ctrl+Shift+B** (or go to **Terminal > Run Task... >
   Start HostelCare Server**).
2. A terminal panel opens and prints something like:
   `[Sat Aug 16 ...] PHP 8.3 Development Server (http://localhost:8000) started`
3. **Ctrl+Click** (Cmd+Click on Mac) that `http://localhost:8000` link
   right in the terminal — it opens your browser straight to the site.
4. Go to `http://localhost:8000/login.php` once, then just use the
   sidebar links to navigate — you never need to type a URL again.
5. To stop the server later, click in that terminal and press
   **Ctrl+C**, or just close VS Code.

This way MySQL runs through XAMPP (Step 1) and the actual website runs
through VS Code's own terminal — no Apache, no `htdocs`, no retyping URLs.

*(If you'd rather use the traditional XAMPP/htdocs/Apache method instead,
that still works: copy the `HostelCare` folder into `C:\xampp\htdocs`,
start Apache too, and visit `http://localhost/HostelCare/login.php`.)*

## 6. Test logins (from the sample data)

| Role     | Email                | Password     |
|----------|-----------------------|--------------|
| Student  | swargo@student.com    | Student@123  |
| Student  | rakib@student.com     | Student@123  |
| Admin    | admin@hostel.com      | Admin@123    |
| Staff    | rahim@staff.com       | Staff@123    |
| Staff    | karim@staff.com       | Staff@123    |

Or register a brand-new student account yourself from the login page
("New student? Create an account") — see the **Registration** section below.

Try this end-to-end flow to see everything connect:
1. Register a new student (or log in as one), submit a new complaint.
2. Log in as **Admin**, verify it, then assign it to a maintenance staff member.
3. Log in as that **Staff** member, mark the task "In Progress" then "Resolved".
4. Log back in as the **Student** — the complaint now shows Resolved, and
   you can leave feedback for it.

## Registration — why only students can self-register

`register.php` is a public sign-up page for **Students only**. This is a
deliberate, realistic design choice, not a missing feature — explain it
like this in your viva:

- Any student can register themselves because there's nothing to verify
  beyond "I'm a person who needs a hostel account" — low risk.
- **Admin** and **Maintenance Staff** accounts are *not* self-service.
  In a real hostel system you don't want a random visitor signing up as
  an Admin. Those accounts are created by an existing Admin, through
  **User Management** (`admin-users.php`), after logging in — this is the
  same pattern used by real university/company systems (HR or IT creates
  staff logins; the public only self-registers for the "customer" role).

If your teacher specifically wants Admin/Staff to also self-register, that
would be a two-line change: point `register.php`'s role to the value from
a dropdown, but you'd want to explain why that's less secure.

## How the three login types work (for your viva)

There's one shared `Users` table with a `Role` column: `'Student'`,
`'Admin'`, or `'MaintenanceStaff'`. This is called **role-based access
control (RBAC)** — a very standard real-world pattern, worth naming
explicitly if asked.

1. **Login**: `login.php` shows one form with a role radio button.
   `login_process.php` checks the email + password + role together against
   the `Users` table.
2. **Session**: once matched, PHP stores `role`, `user_id`, and the
   name/id in `$_SESSION` — a per-browser memory that persists across
   pages without a database lookup every time.
3. **Page protection**: every dashboard/page starts with
   `require_role('Admin')` (or `'Student'` / `'MaintenanceStaff'`). This
   function (in `includes/auth.php`) checks `$_SESSION['role']` and
   redirects to the login page if it doesn't match — so a student can't
   just type `admin-dashboard.php` in the address bar and get in.
4. **Different sidebar per role**: `includes/sidebar.php` picks which
   menu links to show based on `$_SESSION['role']`, so each role only
   ever sees its own pages.
5. **Different data per role**: e.g. `student-dashboard.php` always
   filters by `WHERE StudentID = ?` using the logged-in student's own ID
   from the session — never a value typed by the user — so one student
   can never see another student's complaints.

## Your other question: "the SQL data is hardcoded, but a real website
## should be real-time" — this is already true now

The `database.sql` file only contains **starting sample data** (a "seed"),
the same way a fresh app might ship with a couple of demo accounts. Once
you import it and start using the site through the browser, everything you
do is now live in the database, not hardcoded:

- Registering a new student → inserts a new row into `Users` and `Student`.
- Submitting a complaint → inserts into `Complaint`.
- Admin verifying/assigning → updates `Complaint` and inserts into
  `ComplaintAssignment` and `ComplaintStatusHistory`.
- Staff updating a task → updates `Complaint.Status`.
- Feedback → inserts into `Feedback`.

You can prove this live in your viva: open phpMyAdmin, browse the
`Complaint` table, submit a new complaint from the site, refresh
phpMyAdmin — the new row appears immediately. That's the real-time part:
the "hardcoded" SQL was only ever meant to give you a starting point so
the dashboards aren't empty on day one.

## What to do in VS Code

1. Install the **PHP Intelephense** extension (autocomplete/error-checking
   for PHP) and the **MySQL** extension if you want to browse the database
   from inside VS Code.
2. Every page follows the same pattern — this is the part worth
   understanding, since your teacher will likely ask about it:
   - `require_role('Student')` at the top blocks the page unless someone
     is logged in with that role.
   - A `$pdo->prepare(...)->execute([...])` block runs a SQL query safely
     (using `?` placeholders instead of pasting variables into the SQL
     string — this prevents SQL injection).
   - The HTML further down loops over the query results with
     `foreach ($rows as $row): ... endforeach;` to print real data instead
     of the hardcoded text that was in the original `.html` files.
3. To edit a page's look, edit `style.css` — none of the backend files
   change how anything is styled, they only insert data into the same
   HTML structure your designer already built.

## Known simplifications (worth knowing for your report/viva)

- **Passwords are stored in plain text** in the `Users` table, matching
  your original sample data. In a real system you'd hash them with
  PHP's `password_hash()` when an account is created, and check them with
  `password_verify()` at login — mention this if asked about security.
- I fixed one bug in your original `database.sql`: a row in
  `ComplaintAssignment` referenced a `StaffID` (3) that didn't exist in
  `MaintenanceStaff` (only 2 staff were seeded), which would stop the
  import with a foreign key error. It now points to an existing staff ID.
- File attachment upload (seen in the original complaint form) isn't
  wired up — the database has no column for it. Ask if you want that added.

## If something goes wrong

- **"Database connection failed"** → MySQL isn't running in XAMPP, or the
  database wasn't imported yet (Step 3).
- **Blank white page** → temporarily add
  `error_reporting(E_ALL); ini_set('display_errors', 1);` to the top of
  `config/db.php` while debugging, then check the terminal running the
  PHP server for the exact error.
- **"Invalid email, password, or role"** → double check you picked the
  matching role radio button for that account (e.g. Rahim is Staff, not Admin).
- **"php: command not found" in the VS Code task** → PHP isn't on your
  system PATH. Easiest fix: add `C:\xampp\php` to your Windows PATH
  environment variable, then restart VS Code.

