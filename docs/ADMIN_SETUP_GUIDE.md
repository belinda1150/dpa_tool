# Zimbabwe DPA Tool - Administrator Setup Guide
**Complete system setup and configuration**

---

## Table of Contents

1. [Initial Setup](#1-initial-setup)
2. [Database Migration](#2-database-migration)
3. [Organization Configuration](#3-organization-configuration)
4. [Department Setup](#4-department-setup)
5. [User Account Creation](#5-user-account-creation)
6. [System Testing](#6-system-testing)
7. [Go-Live Checklist](#7-go-live-checklist)
8. [Maintenance & Backup](#8-maintenance--backup)

---

## 1. Initial Setup

### Prerequisites

**Server Requirements:**
- PHP 8.0 or higher
- MySQL 5.7 or higher / MariaDB 10.3+
- Apache/Nginx web server
- 100MB minimum disk space (1GB recommended)
- SSL certificate (recommended for production)

**Software Installed:**
- XAMPP (Windows) / LAMP (Linux) / MAMP (Mac)
- Web browser (Chrome, Firefox, Edge, Safari)
- Database management tool (phpMyAdmin, MySQL Workbench)

### Installation Steps

**Step 1: Extract Files**
```
Extract Zimbabwe DPA Tool files to:
Windows: C:\xampp\htdocs\data_protection\
Linux: /var/www/html/data_protection/
Mac: /Applications/MAMP/htdocs/data_protection/
```

**Step 2: Verify File Structure**
```
data_protection/
├── config/
│   ├── database.php
│   └── config.php
├── includes/
│   ├── auth.php
│   ├── header.php
│   └── sidebar.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── docs/
│   ├── USER_MANUAL.md
│   ├── QUICK_START_GUIDE.md
│   └── ROLE_GUIDE.md
├── database_migrations/
├── dpa_database_schema.sql
└── [module files].php
```

**Step 3: Set File Permissions** (Linux/Mac only)
```bash
cd /var/www/html/data_protection/
chmod 755 -R .
chmod 777 uploads/
chown www-data:www-data -R .
```

---

## 2. Database Migration

### Create Database

**Using phpMyAdmin:**
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "New" in left sidebar
3. Database name: `dpa_compliance`
4. Collation: `utf8mb4_general_ci`
5. Click "Create"

**Using MySQL Command Line:**
```sql
CREATE DATABASE dpa_compliance CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### Import Database Schema

**Method 1: phpMyAdmin**
1. Select `dpa_compliance` database
2. Click "Import" tab
3. Choose file: `dpa_database_schema.sql`
4. Click "Go"
5. Wait for "Import successful" message

**Method 2: Command Line**
```bash
mysql -u root -p dpa_compliance < dpa_database_schema.sql
```

**Verify Import:**
```sql
USE dpa_compliance;
SHOW TABLES;
```

You should see 20+ tables including:
- organizations
- users
- roles
- departments
- ropa_entries
- dpia_assessments
- notifications
- audit_log
- etc.

### Apply Critical Migration: Fix Notification Types

**IMPORTANT: Run this migration immediately after schema import**

```bash
mysql -u root -p dpa_compliance < database_migrations/fix_notification_types.sql
```

**Or in phpMyAdmin:**
1. Select `dpa_compliance` database
2. Click "SQL" tab
3. Paste contents of `database_migrations/fix_notification_types.sql`
4. Click "Go"

**Why This Is Critical:**
The application uses 12 notification types, but the original schema only defines 8. This migration adds the missing types to prevent notification creation errors.

### Configure Database Connection

**Edit:** `config/database.php`

```php
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Your MySQL username
define('DB_PASS', '');                // Your MySQL password
define('DB_NAME', 'dpa_compliance');  // Database name
define('DB_CHARSET', 'utf8mb4');

// Test connection (remove in production)
$test_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$test_conn) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    echo "Database connected successfully!";
}
?>
```

**Test Connection:**
1. Navigate to: http://localhost/data_protection/config/database.php
2. Should see: "Database connected successfully!"
3. **Remove the test code** after verification

---

## 3. Organization Configuration

### Seed Initial Data

**Required: Create Default Organization and Admin User**

**Run this SQL:**
```sql
USE dpa_compliance;

-- Insert default organization
INSERT INTO organizations (org_name, org_email, org_phone, org_address, potraz_reg_number, dpo_name, dpo_email, dpo_phone, status)
VALUES (
    'Your Organization Name',           -- Update this
    'info@yourorg.com',                 -- Update this
    '+263 123 456789',                  -- Update this
    '123 Main Street, Harare, Zimbabwe', -- Update this
    'POTRAZ/REG/2025/001',              -- Update this
    'John Doe',                         -- DPO name
    'dpo@yourorg.com',                  -- DPO email
    '+263 987 654321',                  -- DPO phone
    'active'
);

-- Get the organization ID (should be 1)
SET @org_id = LAST_INSERT_ID();

-- Insert Admin role (if not exists)
INSERT INTO roles (role_name, role_description) VALUES
('Admin', 'Full system access'),
('DPO', 'Data Protection Officer'),
('Department Owner', 'Department-level access'),
('Staff', 'Limited user access'),
('Auditor', 'Read-only audit access')
ON DUPLICATE KEY UPDATE role_name=role_name;

-- Insert default admin user
INSERT INTO users (org_id, role_id, first_name, last_name, email, password, status)
VALUES (
    @org_id,
    1,                                  -- Admin role ID
    'System',                           -- First name
    'Administrator',                    -- Last name
    'admin@yourorg.com',               -- Update this
    'admin123',                        -- CHANGE THIS IMMEDIATELY after first login!
    'active'
);

SELECT 'Setup complete! Login with: admin@yourorg.com / admin123' AS message;
```

**CRITICAL SECURITY NOTE:**
- The default password `admin123` is INSECURE
- Log in immediately and change it
- See Priority 1 below for password security improvements

### Update Organization Settings

**Login as Admin:**
1. Navigate to: http://localhost/data_protection/
2. Username: `admin@yourorg.com`
3. Password: `admin123`

**Update Settings:**
1. Go to **Settings** > **Organization**
2. Update all organization details:
   - Organization Name
   - Contact Information
   - DPO Details
   - POTRAZ Registration Number
   - Timezone (default: Africa/Harare)
3. Click **Save**

### Configure Compliance Parameters

**Edit:** `config/config.php` (lines 34-39)

```php
// Compliance settings
define('DSR_SLA_DAYS', 30);                    // Zimbabwe DPA requirement
define('BREACH_NOTIFICATION_HOURS', 72);        // POTRAZ requirement
define('CONSENT_EXPIRY_ALERT_DAYS', 30);        // Alert 30 days before expiry
define('DPIA_THRESHOLD_SUBJECTS', 10000);       // DPIA required if >10k subjects
define('RISK_ACCEPTABLE_THRESHOLD', 6);         // Risk score ≥6 requires action
```

**Adjust if needed, but recommended values are set to Zimbabwe DPA standards.**

---

## 4. Department Setup

### Create Departments

**Navigate to:** Settings > Departments > Add Department

**Recommended Departments:**
1. **IT / Technology**
   - Code: IT
   - Handles technical systems, databases, security

2. **Human Resources**
   - Code: HR
   - Handles employee data, payroll, recruitment

3. **Finance**
   - Code: FIN
   - Handles financial transactions, invoices, payments

4. **Marketing**
   - Code: MKT
   - Handles customer data, campaigns, communications

5. **Operations**
   - Code: OPS
   - Handles day-to-day operations, logistics

6. **Compliance / Legal**
   - Code: COMP
   - DPO typically belongs here

**Add Department:**
```
Department Name: Human Resources
Department Code: HR
Description: Manages all employee-related data and processes
Department Head: [Leave blank, assign after creating users]
```

### Department Best Practices

- Create departments matching your org structure
- Each department should have 1 Department Owner
- Department codes should be short (2-5 chars)
- You can add/modify departments later

---

## 5. User Account Creation

### Create DPO User

**Settings > Users > Add User**

```
First Name: [DPO First Name]
Last Name: [DPO Last Name]
Email: dpo@yourorg.com
Password: [Generate strong password]
Department: Compliance / Legal
Role: DPO
Status: Active
```

**Important:**
- DPO should be independent
- Provide credentials securely
- Instruct to change password on first login

### Create Department Owners

**One owner per department:**

**Example: HR Department Owner**
```
First Name: Jane
Last Name: Smith
Email: jane.smith@yourorg.com
Password: [Generate strong password]
Department: Human Resources
Role: Department Owner
Status: Active
```

Repeat for each department.

### Create Staff Users

**Settings > Users > Add User**

```
First Name: [Staff First Name]
Last Name: [Staff Last Name]
Email: [staff.email@yourorg.com]
Password: [Generate strong password]
Department: [Appropriate Department]
Role: Staff
Status: Active
```

### Create Auditor User (If Applicable)

```
First Name: [Auditor Name]
Last Name: [Auditor Surname]
Email: auditor@yourorg.com
Password: [Generate strong password]
Department: Compliance / Finance
Role: Auditor
Status: Active
```

### User Management Best Practices

**Password Policy:**
- Minimum 8 characters (configured in config.php)
- Use password generator for initial passwords
- Communicate securely (not via email)
- Require users to change on first login

**Account Security:**
- Never create generic accounts (e.g., "admin", "user")
- Use real names and work emails
- Deactivate accounts immediately when staff leave
- Review user access quarterly

**Bulk User Import:**
If you have many users, create them via SQL:

```sql
INSERT INTO users (org_id, role_id, dept_id, first_name, last_name, email, password, status)
VALUES
(1, 4, 2, 'John', 'Doe', 'john.doe@yourorg.com', 'TempPass123', 'active'),
(1, 4, 3, 'Jane', 'Smith', 'jane.smith@yourorg.com', 'TempPass123', 'active');
-- role_id: 1=Admin, 2=DPO, 3=Dept Owner, 4=Staff, 5=Auditor
-- dept_id: Check departments table for IDs
```

---

## 6. System Testing

### Test Checklist

**Authentication:**
- [ ] Admin login works
- [ ] DPO login works
- [ ] Department Owner login works
- [ ] Staff login works
- [ ] Logout works
- [ ] Failed login lockout (5 attempts) works
- [ ] Session timeout (30 min) works

**ROPA Module:**
- [ ] Admin can create ROPA entry
- [ ] Department Owner can create ROPA entry for their department
- [ ] ROPA list displays correctly
- [ ] ROPA export works (CSV/PDF)
- [ ] DPIA trigger check works

**DPIA Module:**
- [ ] Department Owner can create DPIA
- [ ] DPIA wizard all steps work
- [ ] DPO can approve/reject DPIA
- [ ] Notifications sent correctly

**DSR Module:**
- [ ] Can create DSR request
- [ ] Due date calculated correctly (30 days)
- [ ] Assigned user receives notification
- [ ] SLA warning works (7 days before)
- [ ] Can mark as completed

**Incident Module:**
- [ ] Any user can report incident
- [ ] DPO receives notification for breaches
- [ ] Severity levels display correctly

**Risk Module:**
- [ ] Can create risk
- [ ] Risk score calculated (likelihood × impact)
- [ ] Can link controls
- [ ] Heat map displays correctly
- [ ] Owner receives notification

**Notifications:**
- [ ] Notification bell shows unread count
- [ ] Can view notification details
- [ ] Can mark as read
- [ ] Can mark all as read
- [ ] Can delete notification

**Reports:**
- [ ] Can generate compliance report
- [ ] Can export to PDF
- [ ] Can export to Excel/CSV
- [ ] Audit log accessible (Admin/Auditor)

### Sample Test Data

**Create Test ROPA Entry:**
```
Activity Name: Test - Employee Payroll Processing
Department: Human Resources
Purpose: Salary and benefits administration
Legal Basis: Contract
Data Categories: Names, ID numbers, bank details, salaries
Data Subjects: Employees (current and past)
Sources: HR department, employee applications
Recipients: Finance department, tax authorities, banks
Retention Period: 7 years after employment ends
Security Measures: Encrypted database, access controls, regular backups
Cross-Border Transfers: None
```

**Create Test Risk:**
```
Risk Title: Test - Weak Password Policy
Category: Technical
Description: Current password requirements too weak, risk of unauthorized access
Likelihood: 4 (Likely)
Impact: 4 (Major)
Risk Score: 16 (High)
Treatment: Mitigate
Owner: IT Department Head
```

**Create Test DSR:**
```
Request Type: Access
Subject Name: Test Subject
Email: test@example.com
Description: Request copy of all personal data held
Assigned To: HR Department Owner
```

**After Testing:**
- You can keep test data for training
- Or delete if preferred (use cautiously, test delete functions)

---

## 7. Go-Live Checklist

### Pre-Launch (1 Week Before)

**Technical:**
- [ ] Database backed up
- [ ] All migrations applied (especially fix_notification_types.sql)
- [ ] File permissions correct
- [ ] Uploads directory writable
- [ ] SSL certificate installed (production)
- [ ] Error logging configured
- [ ] Email notifications configured (if applicable)

**Configuration:**
- [ ] Organization details updated
- [ ] DPO contact information correct
- [ ] All departments created
- [ ] Compliance parameters set correctly
- [ ] Timezone configured (Africa/Harare)

**Users:**
- [ ] All user accounts created
- [ ] Strong passwords generated
- [ ] Credentials communicated securely
- [ ] Roles assigned correctly
- [ ] Department owners assigned to departments

**Data:**
- [ ] Test data created (or removed)
- [ ] Sample ROPA entries if desired
- [ ] Policies uploaded (Privacy Policy, etc.)
- [ ] Training materials uploaded

**Testing:**
- [ ] All modules tested
- [ ] Notifications working
- [ ] Reports generating correctly
- [ ] Export functions working
- [ ] No error messages in logs

### Launch Day

**Morning:**
1. **Final Backup:**
   ```bash
   mysqldump -u root -p dpa_compliance > backup_go_live_2025.sql
   ```

2. **Announce Go-Live:**
   - Email all users
   - Provide login URL
   - Attach Quick Start Guide
   - Schedule training sessions

3. **Monitor:**
   - Watch error logs
   - Monitor user login issues
   - Check database performance
   - Be available for support

**First Week:**
- Provide hands-on support
- Conduct training sessions by role:
  - DPO training (1 hour)
  - Department Owner training (1 hour)
  - Staff overview (30 min)
- Gather feedback
- Document issues
- Quick fixes as needed

### Training Rollout

**DPO Training (1 hour):**
- System overview
- DPIA approval process
- Breach response workflow
- Reports and dashboards
- Q&A

**Department Owner Training (1 hour):**
- ROPA creation
- DPIA process
- DSR handling
- Risk management
- Incident reporting

**Staff Training (30 min):**
- Login and navigation
- View notifications
- Complete training
- Report incidents
- Access policies

**Materials:**
- Provide USER_MANUAL.md
- Provide QUICK_START_GUIDE.md
- Provide ROLE_GUIDE.md
- Record training video (optional)

---

## 8. Maintenance & Backup

### Daily Tasks

**Automated (if possible):**
- Database backup
- Error log review

**Manual (5 min):**
- Check dashboard for critical alerts
- Review overnight incidents/breaches
- Monitor system performance

### Weekly Tasks

**Monday Morning (15 min):**
- Review weekend activity
- Check user access issues
- Verify backup integrity
- Check disk space

**Friday Afternoon (15 min):**
- Generate weekly status report
- Review audit logs for anomalies
- Plan next week's maintenance

### Monthly Tasks

**First Monday of Month (1 hour):**
1. **User Access Review:**
   - Verify all active users still employed
   - Deactivate leavers
   - Update roles if job changes
   - Review department assignments

2. **Database Maintenance:**
   ```sql
   -- Optimize tables
   USE dpa_compliance;
   OPTIMIZE TABLE users, ropa_entries, notifications, audit_log;

   -- Check table sizes
   SELECT table_name,
          ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
   FROM information_schema.TABLES
   WHERE table_schema = 'dpa_compliance'
   ORDER BY (data_length + index_length) DESC;
   ```

3. **Cleanup Old Data:**
   ```sql
   -- Delete old notifications (>90 days)
   DELETE FROM notifications
   WHERE is_read = 1
   AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

   -- Note: Do NOT delete audit_log (7-year retention required)
   ```

4. **Generate Monthly Reports:**
   - Compliance dashboard
   - User activity summary
   - Module utilization
   - Training completion
   - Share with management

### Quarterly Tasks

**Every 3 Months (2-3 hours):**

1. **Comprehensive Backup:**
   - Full database dump
   - Uploaded files backup
   - Configuration files backup
   - Store off-site

2. **Security Audit:**
   - Review user permissions
   - Check for inactive accounts
   - Review failed login attempts
   - Verify SSL certificate validity
   - Update passwords (admin accounts)

3. **System Updates:**
   - Check for application updates
   - Update PHP/MySQL if needed
   - Test updates on staging first
   - Apply to production

4. **Compliance Review:**
   - ROPA completeness check
   - DPIA status review
   - DSR SLA performance
   - Training completion rates
   - Policy review dates

### Annual Tasks

**Once Per Year (Full Day):**

1. **Complete System Audit:**
   - All modules tested
   - All reports generated
   - Performance benchmarking
   - Security assessment

2. **Disaster Recovery Test:**
   - Restore from backup on test server
   - Verify all data intact
   - Document restore process
   - Update DR plan

3. **Compliance Certification:**
   - POTRAZ annual filing
   - External audit (if required)
   - Management presentation
   - Board reporting

4. **User Training Refresh:**
   - Annual mandatory training
   - New features training
   - Policy updates
   - Q&A session

### Backup Strategy

**Daily Backups:**
```bash
#!/bin/bash
# Save as: /usr/local/bin/dpa_backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/dpa_compliance"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u root -pYOURPASSWORD dpa_compliance | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploaded files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/data_protection/uploads/

# Keep only last 30 days
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

**Schedule with cron:**
```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * /usr/local/bin/dpa_backup.sh
```

**Backup Verification:**
- Test restore monthly
- Store off-site (cloud/external drive)
- Encrypt backups if containing sensitive data
- Document restore procedure

---

## Appendix A: Troubleshooting Common Issues

### Database Connection Errors

**Error:** "Access denied for user"
- **Solution:** Check DB_USER and DB_PASS in config/database.php
- Verify MySQL user has permissions:
  ```sql
  GRANT ALL PRIVILEGES ON dpa_compliance.* TO 'root'@'localhost';
  FLUSH PRIVILEGES;
  ```

**Error:** "Unknown database"
- **Solution:** Database not created. Run:
  ```sql
  CREATE DATABASE dpa_compliance;
  ```

### File Upload Errors

**Error:** "Failed to upload file"
- **Solution:** Check uploads/ directory permissions:
  ```bash
  chmod 777 uploads/
  ```
- Verify PHP upload settings in php.ini:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 10M
  ```

### Notification Creation Fails

**Error:** "Data truncated for column 'notification_type'"
- **Solution:** Run fix_notification_types.sql migration:
  ```bash
  mysql -u root -p dpa_compliance < database_migrations/fix_notification_types.sql
  ```

### Session Timeout Issues

**Error:** Users logged out too quickly
- **Solution:** Increase session timeout in config/config.php:
  ```php
  define('SESSION_TIMEOUT', 3600); // 1 hour instead of 30 min
  ```

### Slow Performance

**Issue:** Pages load slowly
- **Solution:**
  - Optimize database: `OPTIMIZE TABLE table_name;`
  - Add indexes to frequently queried columns
  - Clean old notifications/logs
  - Check server resources (RAM, CPU)

---

## Appendix B: Security Hardening

### Recommended Security Improvements

**Priority 1: Password Hashing** (CRITICAL)
- Current: Passwords stored in plain text
- Required: Implement bcrypt/Argon2 hashing
- See BRD recommendations for implementation

**Priority 2: Two-Factor Authentication**
- Add 2FA for admin/DPO accounts
- Use TOTP (Google Authenticator) or email-based

**Priority 3: HTTPS Only**
- Install SSL certificate
- Redirect HTTP to HTTPS
- Set secure cookie flags

**Priority 4: SQL Injection Prevention**
- Already implemented: Prepared statements used
- Continue using db_query() with parameter binding
- Never concatenate user input in SQL

**Priority 5: XSS Prevention**
- Already implemented: htmlspecialchars() used
- Continue escaping all user output
- Use Content Security Policy headers

**Priority 6: CSRF Protection**
- Implement CSRF tokens on all forms
- Validate tokens on POST requests

**Priority 7: File Upload Validation**
- Already implemented: File type whitelist
- Add: File content validation (not just extension)
- Add: Virus scanning if possible

### Hardening Checklist

- [ ] Change all default passwords
- [ ] Disable directory browsing
- [ ] Remove test/debug code
- [ ] Disable error display (production)
- [ ] Enable error logging
- [ ] Set secure session parameters
- [ ] Implement rate limiting (login attempts)
- [ ] Regular security updates
- [ ] Security headers (X-Frame-Options, X-XSS-Protection, etc.)
- [ ] Database user with minimum required privileges

---

## Appendix C: Support Resources

**Official Documentation:**
- Zimbabwe Data Protection Act
- POTRAZ Guidelines: https://www.potraz.gov.zw

**System Documentation:**
- USER_MANUAL.md - Complete user guide
- QUICK_START_GUIDE.md - Get started quickly
- ROLE_GUIDE.md - Role-specific instructions
- This file (ADMIN_SETUP_GUIDE.md) - Admin setup

**Technical Support:**
- System Administrator: admin@yourorganization.com
- Database Issues: dba@yourorganization.com
- Security Concerns: security@yourorganization.com

**Compliance Support:**
- DPO: dpo@yourorganization.com
- POTRAZ Helpdesk: [POTRAZ contact]
- Legal Counsel: legal@yourorganization.com

---

**Document Version:** 1.0
**Last Updated:** October 2025
**Next Review:** April 2026

**Good luck with your deployment!**
