# Zimbabwe DPA Tool - Quick Start Guide
**Get up and running in 10 minutes**

---

## 1. Login & Dashboard (2 minutes)

### First Login
1. Open your browser and navigate to the DPA Tool URL
2. Enter your **username** (email) and **password**
3. Click **Login**

### Dashboard Overview
- **Top Cards:** Key compliance metrics
- **Alerts:** Action items requiring attention
- **Recent Activity:** Latest system updates
- **Quick Actions:** Shortcuts to common tasks

---

## 2. Common Tasks by Role

### For Everyone: View Notifications
1. Click the **bell icon** in the top right
2. Review unread notifications (highlighted in blue)
3. Click **View Details** to see related items
4. Click **Mark as Read** to clear notifications

---

### For DPOs & Admins: Add a Processing Activity (ROPA)

**Why:** Legal requirement to maintain register of processing activities

**Steps:**
1. Click **ROPA** > **Add New** in sidebar
2. Fill in the form:
   - **Activity Name:** e.g., "Customer Database Management"
   - **Department:** Select from dropdown
   - **Purpose:** Why you're processing data
   - **Data Categories:** What data (names, emails, addresses, etc.)
   - **Data Subjects:** Whose data (customers, employees, etc.)
   - **Retention:** How long you keep it
3. Click **Save**

**Result:** Entry added to ROPA register. System checks if DPIA is needed.

---

### For DPOs: Approve a DPIA

**When:** You receive a "DPIA Pending Approval" notification

**Steps:**
1. Click notification or go to **DPIA** > **Pending Approvals**
2. Click **Review** on the DPIA
3. Read through all assessment sections
4. Check risk scores and mitigation measures
5. Choose action:
   - **Approve:** Risks acceptable, processing may proceed
   - **Reject:** Risks too high, processing cannot proceed
   - **Request Revision:** Need more information/better controls
6. Add comments and click **Submit Decision**

**Result:** Creator notified of decision. Approved DPIAs allow processing to begin.

---

### For Staff & Department Owners: Submit a DSR Request

**When:** Someone exercises their data subject rights (access, deletion, etc.)

**Steps:**
1. Click **DSR** > **Add New Request**
2. Fill in details:
   - **Request Type:** Access, Erasure, Rectification, etc.
   - **Subject Name:** Person making request
   - **Contact:** Email/phone
   - **Description:** What they're requesting
   - **Assigned To:** Who will handle it
3. Click **Submit**

**Result:** Request logged. Assigned user notified. 30-day countdown starts.

---

### For All: Report an Incident

**When:** Data breach, unauthorized access, or security incident occurs

**Steps:**
1. Click **Incidents** > **Report Incident** (or Quick Action on dashboard)
2. Fill in incident form:
   - **Title:** Brief description
   - **Severity:** Low/Medium/High/Critical
   - **Incident Type:** Breach, Loss, Unauthorized Access, etc.
   - **Detection Date**
   - **Affected Data Subjects:** How many people affected
   - **Description:** Full details
   - **Immediate Actions:** What you've done so far
3. Click **Submit**

**Result:** DPO automatically notified. If data breach, 72-hour POTRAZ notification clock starts.

---

### For DPOs & Department Owners: Conduct Risk Assessment

**Steps:**
1. Go to **Risks** > **Add New Risk**
2. Enter risk details:
   - **Title:** e.g., "Weak password policy"
   - **Category:** Technical/Organizational/Legal
   - **Description:** What could go wrong
3. Rate **Inherent Risk:**
   - **Likelihood:** 1 (rare) to 5 (almost certain)
   - **Impact:** 1 (insignificant) to 5 (catastrophic)
   - System calculates Risk Score = Likelihood × Impact
4. Choose **Treatment Strategy:** Mitigate/Accept/Transfer/Avoid
5. Link **Controls** from library or add new ones
6. Rate **Residual Risk** (after controls applied)
7. Assign **Risk Owner** and set **Review Date**
8. Click **Save**

**Result:** Risk added to register. If score ≥6, appears as action item. Owner notified.

---

### For Admins: Add a New User

**Steps:**
1. Go to **Settings** > **Users** > **Add User**
2. Enter user information:
   - Name, Email
   - **Password:** Temporary password (user should change on first login)
   - **Department**
   - **Role:** Admin/DPO/Dept Owner/Staff/Auditor
   - **Status:** Active
3. Click **Save**

**Result:** User can now log in. Send them their credentials securely.

---

## 3. Essential Features

### Search & Filter
- Most list views have **search box** at top right
- Use **column filters** to narrow results
- Click **column headers** to sort

### Export Data
- Click **Export** button on list views
- Choose format: **CSV** (Excel) or **PDF**
- Downloaded file includes current filters

### Audit Trail
- Every action is logged automatically
- View: **Reports** > **Audit Log**
- Filter by user, action type, date range

### Keyboard Shortcuts
- **Alt + D:** Dashboard
- **Alt + N:** Notifications
- **Alt + L:** Logout

---

## 4. Critical SLAs & Deadlines

| Item | Deadline | Consequence |
|------|----------|-------------|
| DSR Requests | 30 days | Regulatory penalty |
| Data Breach Notification | 72 hours | POTRAZ penalty |
| Consent Renewal | Before expiry | Must stop processing |
| DPIA Approval | Before processing starts | Cannot begin processing |
| Training Completion | Assigned due date | Non-compliance risk |

**Alerts:** System sends notifications 7 days before deadlines.

---

## 5. Quick Reference: Role Capabilities

| I want to... | Who can do it? |
|-------------|----------------|
| Add ROPA entry | Admin, DPO, Dept Owner |
| Approve DPIA | Admin, DPO |
| Handle DSR | Admin, DPO, Dept Owner |
| Report incident | Everyone |
| Manage users | Admin only |
| View audit logs | Admin, Auditor |
| Add risk | Admin, DPO, Dept Owner |
| Create policy | Admin, DPO |
| Assign training | Admin, DPO |
| Generate reports | Admin, DPO, Dept Owner, Auditor |

---

## 6. Getting Help

**In-App Help:**
- Hover over **?** icons for field explanations
- Check **USER_MANUAL.md** for detailed instructions

**System Issues:**
- Contact your system administrator
- Email: [admin@yourorganization.com]

**Compliance Questions:**
- Contact your DPO
- POTRAZ Guidance: https://www.potraz.gov.zw

**Browser Issues:**
- Use Chrome, Firefox, Edge, or Safari (latest versions)
- Clear cache: Ctrl+Shift+Delete

---

## 7. Best Practices

✓ **Check notifications daily** - Don't miss critical alerts

✓ **Document everything** - Compliance requires evidence

✓ **Meet SLAs** - DSR and breach deadlines are legal requirements

✓ **Regular reviews** - Check dashboard weekly for overdue items

✓ **Keep data minimal** - Only collect what's necessary

✓ **Update statuses** - Mark tasks complete as you finish them

✓ **Use filters** - Find information quickly instead of scrolling

✓ **Export regularly** - Backup critical compliance data

✓ **Train staff** - Complete assigned training promptly

✓ **Secure logout** - Always log out on shared computers

---

## 8. Next Steps

After completing this quick start:

1. **Explore your role-specific functions** - Try creating sample entries
2. **Review the dashboard** - Understand your current compliance status
3. **Complete assigned training** - Check **Training** > **My Training**
4. **Read full User Manual** - Detailed guide for all features
5. **Customize notifications** - Set your preferences
6. **Contact DPO** - Discuss department-specific compliance needs

---

**Need More Details?**
See the complete **USER_MANUAL.md** for:
- Detailed module instructions
- Screenshot guides
- Troubleshooting
- FAQ
- Compliance requirements

---

**Document Version:** 1.0
**Last Updated:** October 2025

**Ready to start!** Log in and explore the dashboard.
