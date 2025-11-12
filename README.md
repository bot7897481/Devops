# Local 39 Stationary Engineers Apprenticeship Management System

A comprehensive web-based apprenticeship management system built with **Laravel 10** and **Vue.js 3** to handle up to 3,000 applicants every 2 years.

## 📋 Overview

This system replaces a fully paper-based process and provides:
- **Pre-Registration Module** - Countdown timer, email notifications
- **Application Module** - Multi-step forms, document uploads, in-person validation
- **Exam Management Module** - 5-section timed exam, question bank, auto-scoring
- **Dispatch & Indenture Module** - Automated ranking, job matching, digital contracts

## 🚀 Tech Stack

- **Backend**: Laravel 10+ (PHP 8.1+)
- **Frontend**: Vue.js 3 with Composition API
- **Database**: MariaDB/MySQL
- **Authentication**: Laravel Sanctum (SPA)
- **Styling**: Tailwind CSS
- **State Management**: Pinia
- **Build Tool**: Vite

### Key Packages
- `spatie/laravel-permission` - Role-based access control
- `owen-it/laravel-auditing` - Audit trail logging
- `maatwebsite/excel` - Data export functionality
- `barryvdh/laravel-dompdf` - PDF generation
- `intervention/image` - Image processing

## 📁 Project Structure

```
/home/user/Devops/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── Admin/          # Admin controllers
│   │   │       ├── Applicant/      # Applicant controllers
│   │   │       ├── Auth/           # Authentication
│   │   │       ├── Chief/          # Chief/contractor controllers
│   │   │       └── Public/         # Public endpoints
│   │   └── Middleware/
│   │       └── RoleMiddleware.php  # Role-based access
│   ├── Models/                     # Eloquent models (12 models)
│   └── Notifications/              # Email/SMS notifications
├── database/
│   ├── migrations/                 # 15 database tables
│   └── seeders/                    # Test data and roles
├── resources/
│   ├── js/
│   │   ├── components/             # Vue components
│   │   ├── stores/                 # Pinia stores
│   │   ├── views/                  # Vue pages
│   │   │   ├── Admin/
│   │   │   ├── Applicant/
│   │   │   ├── Auth/
│   │   │   ├── Chief/
│   │   │   └── Public/
│   │   ├── router/                 # Vue Router
│   │   └── App.vue
│   └── css/
│       └── app.css                 # Tailwind styles
├── routes/
│   ├── api.php                     # API routes
│   └── web.php                     # Web routes
└── config/                         # Laravel configuration

```

## ⚙️ Installation & Setup

### Prerequisites
- PHP 8.1 or higher
- Composer
- Node.js 18+ and npm
- MySQL/MariaDB
- Git

### Step 0: Clone the Repository

```bash
git clone <your-fork-or-repo-url>
cd Devops

# (Optional) verify you are in the project root
ls
# You should see files such as composer.json, package.json, artisan, and the .env.example template
```

If you do not see `composer.json` or `.env.example`, double-check that you cloned the correct repository and that you changed into the repository folder before running the remaining commands.

### Step 1: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### Step 2: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 3: Configure Database

Edit `.env` and update database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=local39_apprenticeship
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 4: Run Migrations and Seeders

```bash
# Run migrations to create all tables
php artisan migrate

# Seed the database with roles, permissions, and test users
php artisan db:seed
```

### Step 5: Build Frontend Assets

```bash
# Development mode (with hot reload)
npm run dev

# Production build
npm run build
```

### Step 6: Start the Application

```bash
# Start Laravel development server
php artisan serve

# In another terminal, start Vite dev server
npm run dev
```

Visit `http://localhost:8000` in your browser.

## 👤 Default Test Users

After running seeders, the following test accounts are available:

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| Super Admin | admin@local39.org | password | Full system access |
| Admin | testadmin@local39.org | password | Application/exam/dispatch management |
| Chief | chief@local39.org | password | Dispatch requests only |
| Applicant | applicant@local39.org | password | Apply, take exam, view offers |

## 📊 Database Schema

### Core Tables (15 total)

1. **users** - Authentication and user accounts
2. **pre_registrations** - Pre-registration data before applications open
3. **applicants** - Full application data with validation status
4. **exam_sessions** - Scheduled exam sessions
5. **exam_assignments** - Applicant exam assignments
6. **exam_questions** - Question bank for all 5 sections
7. **exam_attempts** - Exam attempts with scores
8. **exam_responses** - Individual question responses
9. **rankings** - Calculated rankings after exams
10. **dispatch_requests** - Job requests from contractors
11. **dispatch_offers** - Offers sent to candidates
12. **indentures** - Apprenticeship contracts
13. **jobs** - Queue system jobs
14. **personal_access_tokens** - Sanctum API tokens
15. **sessions** - User sessions

## 🔑 API Endpoints

### Public Endpoints
- `GET /api/public/countdown` - Get application open time
- `POST /api/public/pre-register` - Submit pre-registration

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/user` - Get current user

### Applicant Endpoints (requires authentication)
- `POST /api/applicant/application` - Submit application
- `POST /api/applicant/application/upload` - Upload documents
- `GET /api/applicant/exam/session` - Get assigned exam session
- `POST /api/applicant/exam/start` - Start exam
- `POST /api/applicant/exam/answer` - Submit answer
- `GET /api/applicant/ranking` - Get my ranking
- `GET /api/applicant/dispatch-offers` - View dispatch offers
- `POST /api/applicant/dispatch-offers/{id}/respond` - Accept/decline offer

### Admin Endpoints (requires admin role)
- `GET /api/admin/applications` - List all applications
- `POST /api/admin/applications/{id}/validate` - Validate application
- `POST /api/admin/exam-sessions` - Create exam session
- `GET /api/admin/exam-results` - View exam results
- `POST /api/admin/rankings/generate` - Generate rankings
- `GET /api/admin/dispatch-requests` - View dispatch requests
- `POST /api/admin/dispatch-offers` - Send dispatch offer
- `POST /api/admin/indentures/generate` - Generate indenture PDF

### Chief Endpoints (requires chief role)
- `POST /api/chief/dispatch-requests` - Submit dispatch request
- `GET /api/chief/dispatch-requests` - View my requests

## 🔒 Security Features

- **Laravel Sanctum** - SPA authentication
- **Role-based access control** (RBAC) via Spatie Permissions
- **Data encryption** - SSN and biometric data encrypted at rest
- **File validation** - Whitelist file types, max size limits
- **Rate limiting** - Prevent abuse on sensitive endpoints
- **Audit logging** - All critical actions logged
- **Password requirements** - Min 8 chars, uppercase, number, special character

## 🎯 Key Features Implemented

### ✅ Completed
- [x] Complete database schema (15 tables)
- [x] Eloquent models with relationships
- [x] API route structure
- [x] Authentication system (Sanctum)
- [x] Role-based access control
- [x] Vue.js 3 frontend setup
- [x] Vue Router with protected routes
- [x] Pinia state management
- [x] Tailwind CSS styling
- [x] Public landing page and countdown
- [x] Pre-registration functionality
- [x] Login/Register pages
- [x] Database seeders

### 🚧 To Be Implemented
- [ ] Complete all controller implementations
- [ ] Multi-step application form (5 steps)
- [ ] File upload handling
- [ ] In-person validation interface
- [ ] Biometric capture functionality
- [ ] Exam question bank management
- [ ] Exam portal with timed sections
- [ ] Auto-scoring algorithm
- [ ] Ranking calculation algorithm
- [ ] Dispatch matching system
- [ ] Email notification templates
- [ ] SMS notifications (Twilio integration)
- [ ] PDF generation for indentures
- [ ] Excel export functionality
- [ ] Admin analytics dashboard
- [ ] Audit log viewer
- [ ] System settings interface

## 📝 Development Guide

### Continuing Development

The project provides a complete **skeleton** with:
- ✅ All database tables and relationships
- ✅ API routes defined
- ✅ Authentication working
- ✅ Frontend routing configured
- ✅ Stub components for all views

**To complete a module:**

1. **Choose a module** (e.g., Application Form)
2. **Implement the controller** in `app/Http/Controllers/Api/`
3. **Build the Vue component** in `resources/js/views/`
4. **Test the API** using Postman or similar
5. **Test the UI** in the browser
6. **Move to next module**

### Recommended Development Order

1. **Module 1: Pre-Registration** (Partially done)
   - ✅ Basic functionality exists
   - Add countdown timer improvements
   - Add email notifications

2. **Module 2: Application**
   - Build multi-step form component
   - Implement file upload handling
   - Create validation interface for admins
   - Add biometric capture

3. **Module 3: Exam Management**
   - Build question bank management
   - Create exam portal with timer
   - Implement auto-scoring
   - Add session monitoring

4. **Module 4: Dispatch**
   - Implement ranking algorithm
   - Build candidate matching system
   - Create dispatch offer workflow
   - Add indenture generation

### Adding a New Controller

Example: Creating `ApplicationController.php`

```php
<?php

namespace App\Http\Controllers\Api\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function store(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            // ... more fields
        ]);

        // Create applicant record
        $applicant = Applicant::create([
            'user_id' => $request->user()->id,
            'confirmation_number' => Applicant::generateConfirmationNumber(),
            'application_timestamp' => now(),
            // ... more fields
        ]);

        return response()->json([
            'message' => 'Application submitted successfully',
            'data' => $applicant
        ], 201);
    }

    public function uploadDocument(Request $request)
    {
        // Implement file upload logic
    }

    public function getStatus(Request $request)
    {
        // Get applicant status
    }
}
```

### Adding a New Vue Component

Example: Multi-step application form

```vue
<template>
  <div class="multi-step-form">
    <div v-if="currentStep === 1">
      <!-- Step 1: Personal Information -->
    </div>
    <div v-if="currentStep === 2">
      <!-- Step 2: Educational Background -->
    </div>
    <!-- ... more steps -->
  </div>
</template>

<script setup>
import { ref } from 'vue';

const currentStep = ref(1);
const formData = ref({});

const nextStep = () => currentStep.value++;
const previousStep = () => currentStep.value--;
const submitApplication = async () => {
  // API call to submit
};
</script>
```

## 🧪 Testing

```bash
# Run PHP tests
php artisan test

# Run with coverage
php artisan test --coverage
```

## 📦 Production Deployment

### Build for Production

```bash
# Build frontend assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Environment Variables for Production

Update `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use Redis for better performance
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 🤝 Contributing

This is a private system for Local 39. Development guidelines:
- Follow PSR-12 coding standards
- Write tests for critical functionality
- Document complex logic
- Use meaningful commit messages

## 📄 License

Proprietary - Local 39 Stationary Engineers

## 📞 Support

For questions or issues:
- Email: support@local39.org
- Phone: (Contact information)

---

## 🎓 Next Steps

1. **Review the specification** in `Complete AI Prompt for Building Local 39 Apprenticeship System.pdf`
2. **Start with Module 2** (Application Form) - most critical
3. **Implement controllers** one by one
4. **Build Vue components** to match
5. **Test each module** before moving forward
6. **Deploy to staging** for user testing

**Good luck with the development! 🚀**
