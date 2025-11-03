# Zimbabwe DPA Tool - User Manual
**Version 1.0 | October 2025**

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [User Roles & Permissions](#3-user-roles--permissions)
4. [Dashboard Overview](#4-dashboard-overview)
5. [Module Guides](#5-module-guides)
6. [System Administration](#6-system-administration)
7. [Reports & Analytics](#7-reports--analytics)
8. [Notifications](#8-notifications)
9. [FAQ & Troubleshooting](#9-faq--troubleshooting)

---

## 1. Introduction

### 1.1 About the Zimbabwe DPA Tool

The Zimbabwe Data Protection Authority (DPA) Compliance Tool is a comprehensive web-based application designed to help organizations comply with Zimbabwe's Data Protection Act and POTRAZ regulations.

**Key Features:**
- Records of Processing Activities (ROPA)
- Data Protection Impact Assessments (DPIA)
- Consent Management
- Data Subject Rights (DSR) Request Handling
- Incident & Breach Management
- Risk & Control Management
- Cross-Border Transfer Tracking
- Policy & Document Management
- Staff Training & Awareness
- Audit Trail & Reporting

### 1.2 System Requirements

- **Browser:** Chrome 90+, Firefox 88+, Edge 90+, Safari 14+
- **Internet Connection:** Required
- **Screen Resolution:** Minimum 1280x720 (1920x1080 recommended)

---

## 2. Getting Started

### 2.1 Logging In

1. Navigate to the application URL provided by your administrator
2. Enter your **username** and **password**
3. Click **Login**
4. If successful, you'll be redirected to the Dashboard

**Note:** After 5 failed login attempts, your account will be locked for 15 minutes.

### 2.2 First Login

Upon first login, you should:
1. Review your profile information
2. Update your password if using a temporary one
3. Familiarize yourself with the dashboard
4. Check for any pending notifications

### 2.3 Navigating the Interface

**Header Navigation:**
- **Application Name:** Click to return to Dashboard
- **User Info:** Displays your name and role
- **Notification Bell:** Shows unread notifications (with badge count)
- **Logout Button:** End your session

**Sidebar Menu:**
- Organized by modules (ROPA, DPIA, DSR, etc.)
- Expandable submenus for related functions
- Highlights the current active page

---

## 3. User Roles & Permissions

### 3.1 Role Definitions

| Role | Access Level | Primary Responsibilities |
|------|-------------|-------------------------|
| **Admin** | Full system access | User management, system configuration, all modules |
| **DPO** | All compliance modules | DPIA approvals, breach notifications, policy oversight |
| **Department Owner** | Department-specific data | Manage processing activities, risks, and incidents for assigned department |
| **Staff** | Limited read/create | Submit DSRs, view assigned training, acknowledge policies |
| **Auditor** | Read-only access | Review audit logs, generate reports, monitor compliance |

### 3.2 Permission Matrix

| Function | Admin | DPO | Dept Owner | Staff | Auditor |
|----------|-------|-----|------------|-------|---------|
| View Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ |
| Manage Users | ✓ | - | - | - | - |
| Create ROPA | ✓ | ✓ | ✓ | - | - |
| Approve DPIA | ✓ | ✓ | - | - | - |
| Handle DSR | ✓ | ✓ | ✓ | - | - |
| Report Incidents | ✓ | ✓ | ✓ | ✓ | - |
| Manage Risks | ✓ | ✓ | ✓ | - | - |
| View Audit Logs | ✓ | ✓ | - | - | ✓ |
| Generate Reports | ✓ | ✓ | ✓ | - | ✓ |

---

## 4. Dashboard Overview

### 4.1 Dashboard Widgets

**Compliance Status Panel:**
- Shows overall compliance score
- Color-coded risk levels (Green/Yellow/Red)
- Quick links to critical items

**Recent Activities:**
- Latest system activities across all modules
- Timestamp and user information
- Direct links to referenced items

**Key Metrics:**
- Total Processing Activities
- Pending DSR Requests
- Open Incidents
- High-Risk Items
- Training Completion Rate

**Alerts & Upcoming Deadlines:**
- Consent expiring soon
- DSR SLA approaching
- Pending DPIA approvals
- Overdue training assignments

### 4.2 Quick Actions

Dashboard provides quick access to:
- Add New ROPA Entry
- Submit DSR Request
- Report Incident
- Create Risk Assessment
- View Notifications

---

## 5. Module Guides

### 5.1 ROPA (Records of Processing Activities)

**Purpose:** Maintain a comprehensive register of all data processing activities as required by Article 14 of Zimbabwe DPA.

#### Adding a Processing Activity

1. Click **ROPA** > **Add New** from sidebar
2. Complete required fields:
   - **Activity Name:** Descriptive title (e.g., "Employee Payroll Processing")
   - **Department:** Select responsible department
   - **Processing Purpose:** Legal basis for processing
   - **Data Categories:** Types of personal data (names, emails, ID numbers, etc.)
   - **Data Subjects:** Who the data is about (employees, customers, etc.)
   - **Data Sources:** Where data comes from
   - **Recipients:** Who receives/accesses the data
   - **Retention Period:** How long data is kept
   - **Security Measures:** Technical and organizational safeguards
3. Click **Save**

#### DPIA Trigger Check

The system automatically checks if a DPIA is required based on:
- Large-scale processing (>10,000 subjects)
- Sensitive data categories
- Automated decision-making
- Systematic monitoring

If triggered, you'll receive a notification to complete a DPIA.

#### Viewing ROPA Entries

- **ROPA List:** View all processing activities in a searchable table
- **Filter by:** Department, Status, DPIA Required
- **Export:** Download as CSV/PDF for reporting
- **Edit/Delete:** Click action buttons (requires permissions)

---

### 5.2 DPIA (Data Protection Impact Assessment)

**Purpose:** Assess and mitigate privacy risks for high-risk processing activities.

#### When DPIA is Required

A DPIA must be conducted for processing that is likely to result in high risk to individuals, including:
- Systematic and extensive profiling
- Large-scale processing of sensitive data
- Systematic monitoring of public areas
- New technologies with privacy implications

#### Conducting a DPIA (Wizard Process)

**Step 1: Basic Information**
- Link to ROPA entry (if applicable)
- Project/Processing name
- Department and responsible officer
- Start and completion dates

**Step 2: Processing Description**
- Nature of processing
- Scope and context
- Purposes of processing
- Data categories and subjects
- Data flow diagram/description

**Step 3: Necessity & Proportionality**
- Legal basis for processing
- Necessity assessment
- Proportionality measures
- Alternative options considered

**Step 4: Risk Identification**
- Identify privacy risks to data subjects
- Rate likelihood and severity (1-5 scale)
- Consider confidentiality, integrity, availability risks
- Document potential impacts

**Step 5: Mitigation Measures**
- Security measures to reduce risks
- Technical controls (encryption, access controls)
- Organizational measures (policies, training)
- Residual risk assessment

**Step 6: Review & Approval**
- Review all sections
- Submit to DPO for approval
- DPO can: Approve, Reject, or Request Revisions

#### DPIA Statuses

- **Draft:** In progress, not yet submitted
- **Pending Approval:** Submitted, awaiting DPO review
- **Approved:** Risk mitigation acceptable, processing may proceed
- **Rejected:** Risks too high, processing cannot proceed
- **Revision Required:** DPO requires changes before approval

---

### 5.3 Consent Management

**Purpose:** Track and manage data subject consents in compliance with GDPR-style consent requirements.

#### Adding Consent Records

1. Navigate to **Consent** > **Add New**
2. Enter consent details:
   - **Data Subject Name**
   - **Email/Contact**
   - **Consent Purpose:** What data is being collected and why
   - **Data Categories:** Types of data covered
   - **Consent Method:** Online form, Written, Verbal, etc.
   - **Consent Date:** When consent was obtained
   - **Expiry Date:** When consent expires (if applicable)
   - **Marketing Consent:** Opt-in for marketing communications
3. Click **Save**

#### Consent Features

- **Withdrawal Tracking:** Record when consent is withdrawn
- **Renewal Alerts:** Receive notifications before consent expires (30 days)
- **Audit Trail:** Complete history of consent lifecycle
- **Export:** Generate consent reports for POTRAZ audits

#### Managing Consent

- **View Details:** Click on any consent record
- **Withdraw Consent:** Mark consent as withdrawn (automated data deletion workflows recommended)
- **Renew Consent:** Create new consent record when renewed
- **Filter/Search:** Find consents by subject, status, or expiry date

---

### 5.4 DSR (Data Subject Rights) Requests

**Purpose:** Handle requests from individuals exercising their rights (access, rectification, erasure, portability, etc.)

#### Request Types

1. **Access:** Provide copy of personal data
2. **Rectification:** Correct inaccurate data
3. **Erasure:** Delete personal data ("right to be forgotten")
4. **Restriction:** Limit processing of data
5. **Portability:** Provide data in machine-readable format
6. **Objection:** Stop processing for specific purposes

#### Creating a DSR Request

1. Go to **DSR** > **Add New Request**
2. Fill in request details:
   - **Request Type:** Select from dropdown
   - **Subject Name & Contact:** Requestor details
   - **Request Description:** Full details of request
   - **Received Date:** When request was received
   - **Assigned To:** User responsible for handling
   - **Priority:** Normal or High
3. System automatically calculates **Due Date** (30 days from receipt)
4. Click **Submit**

#### Processing DSR Requests

1. **Review Request:** Verify identity of requestor
2. **Search Systems:** Locate all relevant personal data
3. **Prepare Response:** Compile data or perform requested action
4. **Update Status:** Mark as In Progress → Completed/Rejected
5. **Document Actions:** Add notes on actions taken
6. **Respond to Subject:** Within 30-day SLA

#### DSR Alerts

- **Approaching SLA:** Notification at 7 days before due date
- **Overdue:** Alert when SLA exceeded
- **Assignment:** Notification to assigned user

---

### 5.5 Incident & Breach Management

**Purpose:** Record, investigate, and respond to data protection incidents and breaches.

#### Reporting an Incident

1. Navigate to **Incidents** > **Report Incident**
2. Complete incident form:
   - **Incident Title:** Brief description
   - **Severity:** Low, Medium, High, Critical
   - **Incident Type:** Breach, Unauthorized Access, Loss, etc.
   - **Detection Date & Method**
   - **Affected Data Subjects:** Number and categories
   - **Data Categories Affected**
   - **Description:** Full incident details
   - **Immediate Actions Taken**
3. Click **Submit**

#### Data Breach Criteria

A reportable breach occurs when there is:
- Unauthorized access to personal data
- Loss or theft of data
- Accidental disclosure
- Ransomware/encryption by attackers
- Likely risk to rights and freedoms of individuals

#### Breach Notification Timeline

**Critical:** If breach meets reporting threshold:
- **72 hours:** Notify POTRAZ from breach discovery
- **Immediately:** Notify DPO (automatic in system)
- **Without undue delay:** Notify affected data subjects (if high risk)

#### Incident Investigation

1. **Containment:** Stop ongoing breach
2. **Assessment:** Determine scope and impact
3. **Root Cause Analysis:** Identify how breach occurred
4. **Remediation:** Implement fixes
5. **Notification:** If required, notify POTRAZ and subjects
6. **Documentation:** Record all actions in system
7. **Lessons Learned:** Update policies/training

---

### 5.6 Risk & Control Management

**Purpose:** Identify, assess, and mitigate data protection risks.

#### Risk Assessment Process

**1. Identify Risk**
- Navigate to **Risks** > **Add New Risk**
- Enter risk details:
  - Title and description
  - Risk category (Technical, Organizational, Legal, etc.)
  - Risk source
  - Department affected
  - Risk owner

**2. Assess Inherent Risk**
- **Likelihood:** Probability of occurrence (1-5)
  - 1 = Rare, 2 = Unlikely, 3 = Possible, 4 = Likely, 5 = Almost Certain
- **Impact:** Severity if it occurs (1-5)
  - 1 = Insignificant, 2 = Minor, 3 = Moderate, 4 = Major, 5 = Catastrophic
- **Risk Score = Likelihood × Impact**

**3. Risk Treatment Strategy**
- **Mitigate:** Reduce likelihood/impact
- **Accept:** Accept residual risk
- **Transfer:** Transfer to third party (insurance)
- **Avoid:** Eliminate the risk source

**4. Implement Controls**
- Link existing controls from Control Library
- Or create new controls
- Assign implementation responsibilities

**5. Assess Residual Risk**
- After controls applied
- Rate residual likelihood and impact
- Compare to risk tolerance threshold (score ≥6 requires action)

#### Risk Heat Map

Visual representation of all risks:
- **X-axis:** Likelihood (1-5)
- **Y-axis:** Impact (1-5)
- **Color Coding:**
  - Red: Extreme (20-25)
  - Orange: High (15-19)
  - Yellow: Medium (8-14)
  - Green: Low (4-7)
  - Blue: Minimal (1-3)

#### Control Library

Centralized repository of security controls:
- **Control Types:** Preventive, Detective, Corrective
- **Categories:** Access Control, Encryption, Training, Monitoring, etc.
- **Implementation Status:** Planned, Implemented, Verified
- **Link to Risks:** Track which controls mitigate which risks

---

### 5.7 Cross-Border Transfers

**Purpose:** Track and document transfers of personal data outside Zimbabwe.

#### Recording a Transfer

1. Go to **Cross-Border** > **Add New Transfer**
2. Enter transfer details:
   - **Transfer Purpose**
   - **Destination Country**
   - **Recipient Organization**
   - **Data Categories Transferred**
   - **Number of Data Subjects**
   - **Transfer Mechanism:** Adequacy decision, Standard clauses, BCRs, etc.
   - **Transfer Frequency:** One-time, Regular, Continuous
   - **Safeguards:** Security measures in place

#### High-Risk Country Alert

If transferring to a country without adequate protection:
- System automatically flags as high-risk
- DPO receives critical priority notification
- POTRAZ notification may be required
- Additional safeguards must be documented

#### Transfer Mechanisms

- **Adequacy Decision:** Country deemed adequate by POTRAZ
- **Standard Contractual Clauses:** POTRAZ-approved contracts
- **Binding Corporate Rules:** Internal group policies
- **Consent:** Data subject explicit consent
- **Necessary for Contract:** Required for contract performance

---

### 5.8 Policy & Document Management

**Purpose:** Centralize privacy policies, procedures, and compliance documentation.

#### Uploading Policies

1. Navigate to **Policies** > **Add Policy**
2. Fill in policy information:
   - **Policy Title**
   - **Policy Type:** Privacy Policy, Data Breach, Retention, etc.
   - **Version Number**
   - **Effective Date**
   - **Review Date**
   - **Upload Document:** PDF, DOCX (max 10MB)
3. Click **Publish**

#### Policy Features

- **Version Control:** Track policy revisions
- **Staff Acknowledgement:** Require users to read and acknowledge
- **Expiry Alerts:** Notification when review date approaches
- **Public Access:** Mark policies as public-facing
- **Audit Trail:** Track who accessed/acknowledged

#### Document Categories

- Privacy Policies
- Data Breach Response Plan
- Data Retention Schedule
- Data Sharing Agreements
- Processor Contracts
- Training Materials
- Audit Reports

---

### 5.9 Training & Awareness

**Purpose:** Deliver and track mandatory data protection training for staff.

#### Creating Training Courses

1. Go to **Training** > **Add Training**
2. Enter course details:
   - **Training Title**
   - **Description**
   - **Training Type:** Online, Classroom, Workshop, etc.
   - **Duration** (in hours)
   - **Training Content/Materials**
   - **Pass Mark** (if assessment included)

#### Assigning Training

1. Click **Assign Training** on any course
2. Select assignment method:
   - **By User:** Select individual users
   - **By Department:** Assign to entire department
   - **By Role:** Assign to all users with specific role
3. Set **Due Date**
4. Click **Assign**

Users receive notification of training assignment.

#### Tracking Completion

- **My Training:** Users view assigned training
- **Mark Complete:** Users confirm completion
- **Certificates:** Auto-generated upon completion
- **Reports:** Admin/DPO can view completion rates
- **Reminders:** Automatic notifications before due date

---

## 6. System Administration

### 6.1 User Management

**Admin Only**

#### Adding Users

1. Navigate to **Settings** > **Users** > **Add User**
2. Enter user information:
   - First Name, Last Name
   - Email (used as username)
   - Initial Password
   - Department
   - Role (Admin, DPO, Dept Owner, Staff, Auditor)
   - Status (Active/Inactive)
3. Click **Save**

User will receive login credentials (implement email notification).

#### Editing Users

- Change role assignments
- Update contact information
- Reset passwords
- Activate/deactivate accounts
- Cannot delete users (data integrity), only deactivate

#### Password Management

- **Minimum Length:** 8 characters
- **Password Reset:** Admin can reset user passwords
- **Session Timeout:** 30 minutes of inactivity
- **Lockout:** 5 failed attempts = 15 minute lockout

---

### 6.2 Department Management

**Admin/DPO**

#### Adding Departments

1. Go to **Settings** > **Departments** > **Add Department**
2. Enter:
   - Department Name
   - Department Code
   - Description
   - Department Head (optional)
3. Click **Save**

#### Department Features

- Assign users to departments
- Filter data by department
- Department-specific reports
- Department owners manage their department's compliance activities

---

### 6.3 Organization Settings

**Admin Only**

#### General Settings

- **Organization Name**
- **Contact Information**
- **DPO Details**
- **POTRAZ Registration Number**
- **Timezone:** Africa/Harare (default)

#### Compliance Configuration

- **DSR SLA Days:** Default 30 days
- **Breach Notification Hours:** 72 hours
- **Consent Expiry Alert:** 30 days before expiry
- **DPIA Threshold:** 10,000 subjects
- **Risk Acceptable Threshold:** Score of 6

#### Security Settings

- Session timeout duration
- Password requirements
- Login attempt limits
- Two-Factor Authentication (if enabled)
- File upload restrictions

---

## 7. Reports & Analytics

### 7.1 Available Reports

| Report Name | Description | Access |
|------------|-------------|--------|
| **ROPA Register** | Complete list of processing activities | All |
| **DPIA Summary** | Status of all DPIAs | DPO, Admin |
| **Consent Report** | Active/expired consents | DPO, Admin |
| **DSR Performance** | SLA compliance, request volumes | DPO, Admin |
| **Incident Log** | All incidents and breaches | DPO, Admin, Auditor |
| **Risk Register** | Heat map and risk scores | All |
| **Training Completion** | Staff training progress | Admin, DPO |
| **Audit Trail** | System activity log | Admin, Auditor |
| **Cross-Border Transfers** | International data flows | DPO, Admin |

### 7.2 Generating Reports

1. Navigate to **Reports** module
2. Select report type
3. Set filters:
   - Date range
   - Department
   - Status
   - User
4. Choose format: PDF, Excel, CSV
5. Click **Generate**

### 7.3 Scheduled Reports

Configure automatic report generation:
- Daily/Weekly/Monthly frequency
- Email distribution list
- Specific report parameters
- Auto-archive to document library

---

## 8. Notifications

### 8.1 Notification Types

The system sends real-time notifications for:

| Type | Trigger | Priority |
|------|---------|----------|
| Risk Assigned | You're assigned as risk owner | Medium/High |
| DSR Assigned | DSR request assigned to you | Medium |
| DSR SLA Warning | DSR due in 7 days | High |
| DPIA Pending | DPIA submitted for your approval | High |
| DPIA Decision | Your DPIA was approved/rejected | Medium |
| Data Breach | Critical incident reported | Critical |
| Consent Expiring | Consent expires in 30 days | Medium |
| Training Assigned | New training assigned to you | Medium |
| Policy Published | New policy requires acknowledgement | Medium |
| Cross-Border Risk | High-risk transfer recorded | High |

### 8.2 Notification Center

Access via **bell icon** in header.

**Features:**
- **Badge Count:** Unread notification count
- **Filter:** View Unread, Read, or All
- **Priority Indicators:** Color-coded by priority
- **Actions:**
  - Click notification title to view details
  - Mark as Read
  - Delete notification
  - Mark All Read (bulk action)

### 8.3 Notification Settings

Configure your preferences:
- Email notifications (if enabled)
- In-app notifications
- Priority filtering
- Notification frequency

---

## 9. FAQ & Troubleshooting

### 9.1 Common Questions

**Q: I forgot my password. How do I reset it?**
A: Contact your system administrator to reset your password.

**Q: How long is data retained in the system?**
A: Audit logs: 7 years, Operational data: As per ROPA retention schedules, Archives: Configurable by admin.

**Q: Can I delete a ROPA entry?**
A: Only if it has no linked DPIAs, incidents, or risks. Otherwise, mark as "Discontinued" instead of deleting.

**Q: What happens when a DSR request is overdue?**
A: Automatic escalation notification to DPO and Admin. Item appears in red on dashboard. Risk of regulatory non-compliance.

**Q: How do I know if a DPIA is required?**
A: The system automatically checks when you create a ROPA entry. You'll receive a notification if DPIA is triggered.

**Q: Can I export my data?**
A: Yes. Most list views have Export buttons (CSV/PDF). Reports module provides comprehensive export options.

**Q: Who can see audit logs?**
A: Admins and Auditors have full access. DPOs can view logs for compliance modules.

**Q: What file types can be uploaded?**
A: PDF, DOC, DOCX, XLS, XLSX, CSV, TXT, JPG, JPEG, PNG (max 10MB per file).

### 9.2 Troubleshooting

**Issue: Login fails with correct credentials**
- Solution: Check if account is locked (5 failed attempts). Wait 15 minutes or contact admin.

**Issue: Notification badge not updating**
- Solution: Refresh the page (F5). Clear browser cache if problem persists.

**Issue: File upload fails**
- Solution: Check file size (<10MB), file type (allowed formats), and internet connection.

**Issue: Page loads slowly**
- Solution: Check internet connection. Large datasets (1000+ rows) may take time to load. Use filters to reduce data volume.

**Issue: Cannot edit/delete an entry**
- Solution: Verify you have permission for that action. Some items can only be edited by creator or admin.

**Issue: Export/Report generation hangs**
- Solution: Reduce date range or apply filters to decrease data volume. Contact admin if problem persists.

### 9.3 Browser Compatibility

**Recommended Browsers:**
- Google Chrome 90+
- Mozilla Firefox 88+
- Microsoft Edge 90+
- Safari 14+

**Not Supported:**
- Internet Explorer (any version)
- Outdated browser versions

### 9.4 Getting Help

**Contact Support:**
- **System Admin:** [admin@yourorganization.com]
- **DPO:** [dpo@yourorganization.com]
- **POTRAZ Helpdesk:** https://www.potraz.gov.zw

**Documentation:**
- User Manual (this document)
- Quick Start Guide
- Video Tutorials (if available)
- Zimbabwe DPA Guidance: https://www.potraz.gov.zw

---

## Appendix A: Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| Alt + D | Go to Dashboard |
| Alt + N | Open Notifications |
| Alt + L | Logout |
| Ctrl + S | Save form (when in edit mode) |
| Esc | Close modal/cancel action |

---

## Appendix B: Data Protection Principles (Zimbabwe DPA)

1. **Lawfulness, Fairness, Transparency:** Process data legally with clear purpose
2. **Purpose Limitation:** Collect for specified, explicit purposes only
3. **Data Minimization:** Collect only what is necessary
4. **Accuracy:** Keep data accurate and up-to-date
5. **Storage Limitation:** Retain only as long as necessary
6. **Integrity & Confidentiality:** Ensure security and prevent unauthorized access
7. **Accountability:** Demonstrate compliance with all principles

---

## Appendix C: Glossary

- **Data Subject:** Individual whose personal data is processed
- **Data Controller:** Entity determining purposes and means of processing
- **Data Processor:** Entity processing data on behalf of controller
- **Personal Data:** Any information relating to an identified or identifiable person
- **Processing:** Any operation on personal data (collection, storage, use, etc.)
- **POTRAZ:** Postal and Telecommunications Regulatory Authority of Zimbabwe
- **DPO:** Data Protection Officer
- **ROPA:** Records of Processing Activities
- **DPIA:** Data Protection Impact Assessment
- **DSR:** Data Subject Rights request
- **SLA:** Service Level Agreement (response timeframe)

---

**Document Version:** 1.0
**Last Updated:** October 2025
**Next Review:** April 2026

For feedback or corrections to this manual, contact: [admin@yourorganization.com]
