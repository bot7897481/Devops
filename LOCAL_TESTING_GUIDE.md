# Local Testing Guide - Local 39 Apprenticeship System

## 📋 Prerequisites

Before you begin, ensure you have:

- **PHP 8.1 or higher** (`php -v`)
- **Composer** (`composer -V`)
- **Node.js 18+** and **npm** (`node -v` && `npm -v`)
- **MySQL/MariaDB** (`mysql --version`)
- **Git** (already have this)

---

## 🚀 Step 1: Install Dependencies

```bash
cd /home/user/Devops

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

---

## 🗄️ Step 2: Database Setup

### Create Database

```bash
# Login to MySQL (adjust credentials as needed)
mysql -u root -p

# Create database
CREATE DATABASE local39_apprenticeship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Exit MySQL
exit;
```

### Configure Environment

```bash
# Copy environment file if not already done
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Edit `.env` file

Open `.env` and update these settings:

```env
APP_NAME="Local 39 Apprenticeship"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=local39_apprenticeship
DB_USERNAME=root
DB_PASSWORD=your_mysql_password

# Application Settings
APPLICATION_OPEN_DATE="2025-01-15 08:00:00"
EXAM_PASSING_SCORE=70

# Mail (use 'log' for testing - emails will be written to storage/logs/laravel.log)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@local39.org
MAIL_FROM_NAME="${APP_NAME}"

# Queue (use 'sync' for testing - runs jobs immediately)
QUEUE_CONNECTION=sync

# Session
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

---

## 🗃️ Step 3: Run Migrations and Seeders

```bash
# Run all database migrations
php artisan migrate

# Seed the database with roles, permissions, and test users
php artisan db:seed
```

**Expected Output:**
- 15 database tables created
- Roles and permissions created
- 4 test user accounts created

---

## 📁 Step 4: Create Storage Directories

```bash
# Create storage link for public access
php artisan storage:link

# Create required directories
mkdir -p storage/app/private/applications/documents
mkdir -p storage/app/private/applications/biometric
mkdir -p storage/app/private/indentures
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

## 🖥️ Step 5: Start Development Servers

Open **3 terminal windows/tabs**:

### Terminal 1: Laravel Backend
```bash
cd /home/user/Devops
php artisan serve
```
**Backend will run on:** `http://localhost:8000`

### Terminal 2: Vite Frontend (Hot Reload)
```bash
cd /home/user/Devops
npm run dev
```
**Vite dev server will run on:** `http://localhost:5173`

### Terminal 3: Queue Worker (for email jobs)
```bash
cd /home/user/Devops
php artisan queue:work
```
*Note: Since we're using `QUEUE_CONNECTION=sync`, this is optional for testing*

---

## 👥 Step 6: Test User Accounts

After seeding, you have these test accounts:

| Role | Email | Password | What You Can Do |
|------|-------|----------|-----------------|
| **Super Admin** | admin@local39.org | password | Full system access |
| **Admin** | testadmin@local39.org | password | Manage applications, exams, dispatch |
| **Chief** | chief@local39.org | password | Submit dispatch requests |
| **Applicant** | applicant@local39.org | password | Apply, take exam, view offers |

---

## 🧪 Step 7: Complete Workflow Testing

### Part 1: Pre-Registration (Public)

1. **Open browser:** `http://localhost:8000`
2. Click **"Pre-Register Now"**
3. Fill out form:
   - First Name: John
   - Last Name: Doe
   - Email: john.doe@example.com
   - Phone: (555) 123-4567
   - High School: Any High School
   - Graduation Year: 2020
4. **Submit**
5. ✅ **Verify:** Success message with reference number appears

---

### Part 2: Application Submission (Applicant)

#### Login as Applicant
1. Navigate to: `http://localhost:8000/login`
2. Email: `applicant@local39.org`
3. Password: `password`
4. Click **Login**

#### Submit Application
1. Navigate to: `http://localhost:8000/applicant/application`
2. **Step 1: Personal Information**
   - Fill all required fields
   - SSN: 123-45-6789
   - Date of Birth: 01/01/1995
   - Address: 123 Main St, San Francisco, CA 94102
   - Phone: (555) 123-4567
   - Click **Next**

3. **Step 2: Educational Background**
   - High School: Lincoln High School
   - Graduation Date: 06/2013
   - Upload diploma (any PDF/image file)
   - Click **Next**

4. **Step 3: Work Experience** (optional)
   - Can skip or fill work history
   - Click **Next**

5. **Step 4: Identification Documents**
   - Upload ID front (any JPG/PNG)
   - Upload ID back (optional)
   - Click **Next**

6. **Step 5: Review & Submit**
   - Check all certification boxes
   - Type your name for digital signature
   - Click **Submit Application**

7. ✅ **Verify:**
   - Success modal appears with confirmation number
   - Email notification in `storage/logs/laravel.log`

#### Try Duplicate Submission
1. Try to access `/applicant/application` again
2. ✅ **Verify:** Should show "Application already submitted" message

---

### Part 3: Application Validation (Admin)

#### Login as Admin
1. **Logout** from applicant account
2. Login with:
   - Email: `admin@local39.org`
   - Password: `password`

#### Validate Application
1. Navigate to: `http://localhost:8000/admin/applications`
2. ✅ **Verify:** Application list appears
3. Click **"View Details"** on the application
4. **In the modal:**
   - Review all application data
   - Click documents to view/download
   - Check **"Documents verified"** checkbox
   - Check **"ID verified"** checkbox
   - Click **"Mark as Validated"**
5. ✅ **Verify:**
   - Success message appears
   - Status updates to "validated"
   - Email sent (check logs)

#### Capture Biometric Photo (Optional)
1. In application detail modal
2. Click **"Capture Photo"**
3. Upload a test photo
4. ✅ **Verify:** Success message

#### Export to Excel
1. Click **"Export to Excel"** button
2. ✅ **Verify:** Excel file downloads with application data

---

### Part 4: Exam Management (Admin)

#### Create Exam Session
1. Navigate to: `http://localhost:8000/admin/exams`
2. Click **"Create Session"**
3. Fill form:
   - Name: "January 2025 Exam"
   - Description: "Regular exam session"
   - Scheduled Date: Select a date
   - Time per Section: 30 minutes
4. Click **Create**
5. ✅ **Verify:** Session appears in list

#### Add Exam Questions
1. Go to **"Question Bank"** tab
2. Click **"Add Question"**
3. Fill form:
   - Section: 1 (Reading)
   - Type: Multiple Choice
   - Question: "What is 2+2?"
   - Options: 2, 3, 4, 5
   - Correct Answer: 4
   - Points: 10
4. Click **Add**
5. **Repeat 20+ times** for each section (1-5)
   - Section 1: Reading
   - Section 2: Math Computation
   - Section 3: Applied Math
   - Section 4: Language
   - Section 5: Aptitude

#### Activate Session & Assign Applicants
1. Back to **"Exam Sessions"** tab
2. Click **"Activate"** on your session
3. Click **"Auto Assign"** to assign validated applicants
4. ✅ **Verify:** Applicants assigned to session

---

### Part 5: Take Exam (Applicant)

#### Login as Applicant
1. Logout from admin
2. Login as: `applicant@local39.org`

#### Start Exam
1. Navigate to: `http://localhost:8000/applicant/exam`
2. Click **"Check In"**
3. Click **"Start Exam"**
4. **Answer all questions** in each section
5. Click **"Complete Section"** after each section
6. After Section 5, click **"Submit Exam"**
7. ✅ **Verify:**
   - Exam submitted successfully
   - Scores calculated
   - Can view results

---

### Part 6: Generate Rankings (Admin)

#### Login as Admin
1. Login as: `admin@local39.org`

#### Generate Rankings
1. Navigate to: `http://localhost:8000/admin/dispatch-management`
2. Go to **"Rankings"** tab
3. Click **"Generate Rankings"**
4. ✅ **Verify:**
   - Success message: "Successfully generated rankings for X applicants"
   - Rankings table populated with ranked applicants
   - Sorted by exam score (highest first)

#### Export Rankings
1. Click **"Export to Excel"**
2. ✅ **Verify:** Excel file downloads with all rankings

---

### Part 7: Dispatch Request (Chief)

#### Login as Chief
1. Logout from admin
2. Login as: `chief@local39.org`

#### Submit Dispatch Request
1. Navigate to: `http://localhost:8000/chief/dispatch`
2. Click **"Create New Request"** (if UI exists) or use API:

```bash
# Via API (in new terminal):
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"chief@local39.org","password":"password"}' \
  -c cookies.txt

# Get token from response, then:
curl -X POST http://localhost:8000/api/chief/dispatch-requests \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "ACME Construction",
    "job_location_address": "123 Industrial Blvd",
    "job_location_city": "San Francisco",
    "job_location_state": "CA",
    "job_type": "commercial",
    "start_date": "2025-03-01",
    "positions_needed": 2,
    "job_description": "Commercial HVAC installation and maintenance",
    "special_requirements": "Must have experience with chillers"
  }'
```

3. ✅ **Verify:** Request created with status "pending"

---

### Part 8: Match Candidates & Send Offers (Admin)

#### View Dispatch Requests
1. Login as admin: `admin@local39.org`
2. Navigate to: `http://localhost:8000/admin/dispatch-management`
3. **"Dispatch Requests"** tab should show the chief's request

#### Find Candidates
1. Click **"Find Candidates"** on the request
2. ✅ **Verify:** Modal opens showing ranked candidates
3. **Select** 1-2 candidates using checkboxes
4. Click **"Send Offers (2)"**
5. ✅ **Verify:**
   - Success message: "Successfully sent 2 dispatch offer(s)"
   - Modal closes
   - Request status updates to "in_progress"

#### View Offers
1. Go to **"Dispatch Offers"** tab
2. ✅ **Verify:** Offers shown with status "pending"

---

### Part 9: Respond to Offer (Applicant)

#### Login as Applicant
1. Login as: `applicant@local39.org`

#### View Offers
1. Navigate to: `http://localhost:8000/applicant/dashboard` or use API:

```bash
curl -X GET http://localhost:8000/api/applicant/dispatch-offers \
  -H "Authorization: Bearer APPLICANT_TOKEN"
```

#### Accept Offer (via API)
```bash
# Replace {offer_id} with actual ID from previous step
curl -X POST http://localhost:8000/api/applicant/dispatch-offers/1/respond \
  -H "Authorization: Bearer APPLICANT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"response": "accept"}'
```

✅ **Verify:**
- Success message
- Applicant status → "dispatched"

---

### Part 10: Generate Indenture (Admin)

#### Login as Admin
1. Login as: `admin@local39.org`

#### Generate Indenture Contract
```bash
# Via API:
curl -X POST http://localhost:8000/api/admin/indentures/generate \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "dispatch_offer_id": 1,
    "term_length_years": 4,
    "wage_schedule": [
      {"year": 1, "percentage": 50},
      {"year": 2, "percentage": 60},
      {"year": 3, "percentage": 75},
      {"year": 4, "percentage": 90}
    ]
  }'
```

#### View and Download Indenture
1. Navigate to: `http://localhost:8000/admin/dispatch-management`
2. Go to **"Indentures"** tab
3. ✅ **Verify:** Indenture appears in list
4. Click **"Download PDF"**
5. ✅ **Verify:** PDF downloads and opens properly

---

## 🐛 Common Issues & Solutions

### Issue 1: "SQLSTATE[HY000] [2002] Connection refused"
**Solution:**
```bash
# Start MySQL service
sudo systemctl start mysql
# or on macOS:
brew services start mysql
```

### Issue 2: "Permission denied" when uploading files
**Solution:**
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage  # Linux
# or
chown -R _www:_www storage          # macOS
```

### Issue 3: Frontend not loading / blank page
**Solution:**
```bash
# Clear Vite cache
rm -rf node_modules/.vite
npm run dev
```

### Issue 4: "Class not found" errors
**Solution:**
```bash
# Regenerate autoload files
composer dump-autoload
```

### Issue 5: Database migration errors
**Solution:**
```bash
# Reset database (WARNING: deletes all data)
php artisan migrate:fresh --seed
```

---

## 📊 Verify Database Records

Check what's in your database:

```bash
# Enter MySQL
mysql -u root -p local39_apprenticeship

# Check tables
SHOW TABLES;

# Check pre-registrations
SELECT * FROM pre_registrations;

# Check applicants
SELECT id, confirmation_number, first_name, last_name, status FROM applicants;

# Check exam attempts
SELECT id, applicant_id, total_score, completed_at FROM exam_attempts;

# Check rankings
SELECT rank, exam_score, applicant_id FROM rankings ORDER BY rank;

# Check dispatch requests
SELECT id, company_name, status, positions_needed FROM dispatch_requests;

# Check dispatch offers
SELECT id, applicant_id, response_status, offered_at FROM dispatch_offers;

# Exit
exit;
```

---

## 📧 Check Email Notifications

Since we're using `MAIL_MAILER=log`, all emails are written to logs:

```bash
# View recent emails
tail -f storage/logs/laravel.log

# Search for specific email
grep "ApplicationSubmitted" storage/logs/laravel.log
```

---

## 🧹 Reset Everything (Clean Slate)

If you want to start fresh:

```bash
# Drop and recreate database
mysql -u root -p -e "DROP DATABASE IF EXISTS local39_apprenticeship; CREATE DATABASE local39_apprenticeship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Re-run migrations and seeders
php artisan migrate:fresh --seed

# Clear uploaded files
rm -rf storage/app/private/applications/*
rm -rf storage/app/private/indentures/*

# Restart servers
# Kill and restart: php artisan serve, npm run dev
```

---

## 🎯 Quick Test Checklist

- [ ] Can access landing page at `http://localhost:8000`
- [ ] Can submit pre-registration
- [ ] Can login as applicant
- [ ] Can submit full application (5 steps)
- [ ] Can login as admin
- [ ] Can view and validate application
- [ ] Can create exam session
- [ ] Can add exam questions
- [ ] Can assign applicants to exam
- [ ] Applicant can take and complete exam
- [ ] Admin can generate rankings
- [ ] Chief can submit dispatch request
- [ ] Admin can find candidates and send offers
- [ ] Applicant can accept/decline offer
- [ ] Admin can generate indenture PDF
- [ ] Can download indenture PDF

---

## 📱 API Testing with Postman/Insomnia

1. **Import this collection:** (Create file `api_collection.json`)
2. **Set base URL:** `http://localhost:8000/api`
3. **Authenticate first**, then use token in subsequent requests

Example authentication:
```json
POST http://localhost:8000/api/auth/login
{
  "email": "admin@local39.org",
  "password": "password"
}
```

Copy the `token` from response and use in headers:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 🎓 Next Steps After Testing

1. **Fix any bugs** you discover
2. **Create more test data** (multiple applicants, exams, etc.)
3. **Test edge cases** (expired offers, duplicate submissions, etc.)
4. **Enhance UIs** (Chief Dashboard, Applicant Dashboard)
5. **Add email notifications** (replace 'log' with real SMTP)
6. **Deploy to staging** server for user testing

---

## 🆘 Need Help?

- **Backend errors:** Check `storage/logs/laravel.log`
- **Frontend errors:** Check browser console (F12)
- **Database issues:** Check MySQL error log
- **Email issues:** Check `storage/logs/laravel.log`

---

**Happy Testing! 🚀**

All 4 modules are now complete and ready to test the entire apprenticeship lifecycle!
