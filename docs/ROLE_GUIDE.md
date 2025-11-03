# Zimbabwe DPA Tool - Role-Specific Guide
**Detailed instructions for each user role**

---

## Table of Contents

1. [Admin Role](#1-admin-role)
2. [Data Protection Officer (DPO) Role](#2-data-protection-officer-dpo-role)
3. [Department Owner Role](#3-department-owner-role)
4. [Staff Role](#4-staff-role)
5. [Auditor Role](#5-auditor-role)

---

## 1. Admin Role

### Overview
Administrators have full system access and are responsible for user management, system configuration, and overall system maintenance.

### Key Responsibilities

**System Administration:**
- Create and manage user accounts
- Configure organization settings
- Manage departments
- Monitor system health and performance
- Backup and data integrity

**Compliance Support:**
- Support DPO with compliance activities
- Generate compliance reports
- Manage audit logs
- Oversee all modules

### Daily Tasks

**Morning Routine:**
1. Check dashboard for system alerts
2. Review overnight notifications
3. Check for failed/overdue tasks
4. Monitor user access issues

**Weekly Tasks:**
1. Review user access rights
2. Check for inactive accounts
3. Generate compliance status report
4. Review audit logs for anomalies
5. Backup critical data

**Monthly Tasks:**
1. User access review and cleanup
2. System performance review
3. Policy review reminder distribution
4. Compliance metrics report to management

### Core Functions

#### User Management

**Create New User:**
```
Settings > Users > Add User
- Enter: Name, Email, Password, Department, Role
- Activate account
- Communicate credentials securely to user
```

**Modify User:**
```
Settings > Users > [Select User] > Edit
- Update role/department as needed
- Reset password if requested
- Deactivate accounts for leavers (DO NOT DELETE)
```

**Security Best Practices:**
- Never share admin credentials
- Use strong passwords
- Deactivate user accounts immediately when staff leave
- Review user activity logs quarterly
- Maintain least privilege principle

#### Department Management

**Create Department:**
```
Settings > Departments > Add Department
- Name: Finance, HR, IT, Marketing, etc.
- Code: FIN, HR, IT, MKT (short code)
- Description: Department purpose
- Head: Assign department head user
```

**Assign Department Owners:**
- Department Owners manage compliance for their department
- Assign one owner per department
- Ensure they complete DPA training

#### System Configuration

**Organization Settings:**
```
Settings > Organization
- Name, Address, Contact
- DPO Contact Information
- POTRAZ Registration Number
- Compliance Thresholds
```

**Compliance Parameters:**
- **DSR SLA:** 30 days (Zimbabwe DPA requirement)
- **Breach Notification:** 72 hours (POTRAZ requirement)
- **Consent Expiry Alert:** 30 days before expiry
- **DPIA Threshold:** 10,000 data subjects
- **Risk Threshold:** Score ≥ 6 requires mitigation

#### Reporting & Analytics

**Key Reports to Generate:**

1. **Monthly Compliance Dashboard**
   - ROPA completeness
   - DPIA status summary
   - DSR SLA performance
   - Incident trends
   - Training completion rates

2. **Quarterly Management Report**
   - Compliance score
   - Risk heat map
   - Audit findings
   - Remediation status

3. **Annual POTRAZ Submission**
   - Complete ROPA register
   - DPIA summaries
   - Breach notifications log
   - Cross-border transfers

### Access Rights

**Full Access To:**
- All modules (create, read, update, delete)
- User management
- System settings
- Audit logs
- All reports

**Restrictions:**
- Cannot delete audit log entries
- Cannot modify historical compliance records

### Troubleshooting Guide

**Issue: User Cannot Login**
- Check account status (Active/Inactive)
- Check for account lockout (5 failed attempts)
- Reset password if needed
- Verify email is correct

**Issue: Slow System Performance**
- Check database size
- Review active user sessions
- Clear old notifications (>90 days)
- Archive completed old records

**Issue: Report Generation Fails**
- Reduce date range
- Check disk space
- Verify database connection
- Check for corrupt data

---

## 2. Data Protection Officer (DPO) Role

### Overview
The DPO is responsible for monitoring compliance with Zimbabwe DPA, providing advice, and acting as point of contact with POTRAZ.

### Key Responsibilities

**Compliance Oversight:**
- Approve DPIAs
- Respond to data breaches (72-hour notification to POTRAZ)
- Handle escalated DSR requests
- Monitor risk register
- Liaise with POTRAZ

**Advisory:**
- Advise organization on data protection obligations
- Conduct training and awareness programs
- Review and approve policies
- Provide guidance on privacy by design

**Monitoring:**
- Regular compliance audits
- Review processing activities
- Monitor third-party processors
- Track consent management

### Daily Tasks

**Critical Daily Checks:**
1. **Breach Notifications** - Check for new incidents flagged as breaches
2. **Pending DPIAs** - Review and approve/reject assessments
3. **DSR Escalations** - Handle complex or overdue requests
4. **High-Risk Alerts** - Review critical notifications

**Daily Routine:**
```
1. Dashboard review (5 min)
2. Notifications check (10 min)
3. Pending approvals (30 min)
4. Risk/incident review (15 min)
Total: ~60 minutes/day
```

### Core Functions

#### DPIA Approval Process

**When DPIA Arrives:**
1. **Notification:** "DPIA Pending Approval"
2. **Access:** DPIA > Pending Approvals > Click Review

**Review Checklist:**
- [ ] Processing description complete and clear
- [ ] Legal basis documented
- [ ] Necessity and proportionality justified
- [ ] Risks identified comprehensively
- [ ] Risk ratings reasonable (likelihood × impact)
- [ ] Mitigation measures adequate
- [ ] Residual risk acceptable (< 6 ideal)
- [ ] Stakeholders consulted (if required)

**Decision Options:**

**Approve:**
- All risks adequately mitigated
- Residual risk acceptable
- Processing may proceed

**Reject:**
- Risks too high
- Inadequate safeguards
- Processing cannot proceed as designed

**Request Revision:**
- Need more information
- Mitigation measures insufficient
- Alternative approaches should be considered

**After Decision:**
- Add detailed comments explaining rationale
- Creator receives notification
- Record kept for audit

#### Data Breach Response

**When Breach Reported:**

**Immediate Actions (0-24 hours):**
1. Assess severity and scope
2. Contain the breach (coordinate with IT/Security)
3. Document everything
4. Determine if reportable to POTRAZ

**POTRAZ Notification Decision:**

**Must Notify If:**
- Risk to rights and freedoms of individuals
- Significant data volume affected
- Sensitive data compromised
- Likely harm to data subjects

**72-Hour Deadline:**
- From discovery (not occurrence) of breach
- Notification to POTRAZ required
- System tracks countdown automatically

**Breach Notification Content:**
- Nature of breach
- Categories and volume of data subjects affected
- Categories of data compromised
- Likely consequences
- Measures taken/proposed to mitigate
- DPO contact details

**Data Subject Notification:**
- Required if high risk to individuals
- Without undue delay
- Clear, plain language
- Advice on protective measures

**Post-Breach:**
- Root cause analysis
- Update security measures
- Update breach response plan
- Staff training/awareness
- Document lessons learned

#### Cross-Border Transfer Review

**High-Risk Transfer Alert:**
- Destination country lacks adequate protection
- Large volume of sensitive data
- POTRAZ notification may be required

**Review:**
1. Check transfer mechanism (adequacy, SCCs, BCRs, consent)
2. Verify additional safeguards
3. Assess necessity
4. Document approval/concerns
5. Consider POTRAZ consultation if very high risk

#### Policy Oversight

**Policy Review Responsibilities:**
- Privacy Policy
- Data Breach Response Plan
- Data Retention Schedule
- Data Sharing Agreements
- Processor Contracts
- Subject Access Request Procedure

**Annual Review:**
- Review all policies annually
- Update for regulatory changes
- Publish revised versions
- Ensure staff acknowledgement

### Weekly Tasks

**Monday:**
- Review weekend incidents/breaches
- Check pending DPIA approvals
- Review risk register updates

**Wednesday:**
- DSR SLA check (identify approaching deadlines)
- Consent expiry review
- Training compliance check

**Friday:**
- Weekly compliance summary
- Update management on critical items
- Plan next week's priorities

### Monthly Tasks

1. **Compliance Dashboard** - Generate and review
2. **ROPA Completeness** - Verify all processing activities documented
3. **Risk Heat Map Review** - Identify trends
4. **Training Effectiveness** - Check completion rates
5. **Third-Party Audits** - Review processor compliance
6. **Management Report** - Brief leadership on status

### Access Rights

**Full Access To:**
- All compliance modules
- DPIA approvals
- Incident management
- Risk register
- Reports and analytics
- Audit logs (read-only)

**Cannot:**
- Create/modify users (Admin only)
- Delete audit logs
- Modify system settings

### DPO Independence

**Important:** As DPO, you should:
- Report to highest management level
- Act independently (not receive instructions)
- Raise concerns directly to board
- No conflict of interest (cannot determine purposes/means of processing)

---

## 3. Department Owner Role

### Overview
Department Owners manage data protection compliance for their specific department, acting as the primary point of contact between their department and the DPO.

### Key Responsibilities

**Department Compliance:**
- Document all processing activities (ROPA) for your department
- Conduct DPIAs for department projects
- Manage department-specific risks
- Handle DSR requests related to department data
- Report incidents promptly

**Coordination:**
- Liaise with DPO on compliance matters
- Ensure department staff trained
- Implement privacy by design in projects
- Review and update department procedures

### Getting Started

**First Steps as Department Owner:**

1. **Audit Current Processing:**
   - List all systems/databases containing personal data
   - Identify what data you collect and why
   - Document data flows

2. **Create ROPA Entries:**
   - One entry per processing activity
   - Be specific and comprehensive
   - Update regularly

3. **Assess Risks:**
   - Identify privacy/security risks
   - Document in risk register
   - Implement controls

4. **Train Your Team:**
   - Ensure all staff complete mandatory training
   - Department-specific training on procedures
   - Regular refreshers

### Core Functions

#### ROPA Management

**Document Processing Activities:**

**Examples:**
- Employee payroll processing
- Customer database management
- CCTV surveillance
- Email marketing campaigns
- Supplier contract management

**For Each Activity:**
```
ROPA > Add New
- Activity Name: Clear, descriptive
- Purpose: Why processing occurs
- Legal Basis: Consent, Contract, Legal Obligation, etc.
- Data Categories: Exactly what data
- Data Subjects: Employees, customers, suppliers, etc.
- Sources: Where data comes from
- Recipients: Who accesses/receives data
- Retention: How long kept (be specific: 7 years, duration of employment, etc.)
- Security: Measures in place (encryption, access controls, etc.)
- Cross-Border: Any international transfers?
```

**ROPA Best Practices:**
- Review quarterly
- Update when processes change
- Be specific, not vague
- Document actual practice (not aspirational)

#### DPIA Execution

**When Required:**
- System automatically flags when creating ROPA
- Or manually initiate for new projects

**DPIA Wizard Process:**

**Step 1: Basics**
- Link to ROPA entry
- Project name and timeline
- Department and responsible person

**Step 2: Description**
- What are you doing with the data?
- Why is it necessary?
- What are the data flows?

**Step 3: Necessity & Proportionality**
- Is there a less intrusive way?
- Is the data collection proportionate?
- What safeguards are in place?

**Step 4: Risk Identification**
- What could go wrong?
- Rate: Likelihood (1-5) × Impact (1-5)
- Consider: Confidentiality, Integrity, Availability

**Step 5: Mitigation**
- What controls will reduce risk?
- Technical: Encryption, access controls, monitoring
- Organizational: Policies, training, procedures
- Re-rate residual risk

**Step 6: Submit**
- Review all sections
- Submit to DPO for approval
- Track status in DPIA module

**After Submission:**
- DPO will approve, reject, or request revisions
- If approved: Proceed with processing
- If rejected: Redesign or abandon project
- If revision needed: Make changes and resubmit

#### DSR Handling

**Types You May Handle:**
1. **Access Requests:** Provide copy of data
2. **Rectification:** Correct inaccurate data
3. **Erasure:** Delete data (right to be forgotten)
4. **Restriction:** Stop processing temporarily
5. **Portability:** Provide data in machine-readable format
6. **Objection:** Stop processing for specific purpose

**Process:**

**Day 1: Receipt**
```
DSR > Add New Request
- Log request immediately
- Verify identity of requestor (important!)
- Assign to team member
- 30-day clock starts
```

**Days 2-20: Processing**
- Search all department systems
- Identify all relevant data
- Compile response or perform action
- Review for completeness
- Check for exemptions (if any apply)

**Day 21-28: Response Prep**
- Prepare formal response
- Redact third-party data (if applicable)
- Package data securely
- Get management approval if needed

**Day 29: Respond**
- Send to data subject
- Update status to "Completed"
- Document in system

**Day 30: Deadline**
- Must respond by this date
- Can extend 2 months if complex (notify subject)
- System alerts at Day 23 if not completed

**Important:**
- Free of charge (first request)
- Provide in accessible format
- Include right to complain to POTRAZ
- Document refusals with legal justification

#### Incident Reporting

**When to Report:**
- Data breach (unauthorized access, loss, theft)
- Privacy violation
- Security incident involving personal data
- Policy non-compliance

**Immediate Actions:**
1. **Contain:** Stop ongoing breach
2. **Assess:** How serious is it?
3. **Report:** Log in system immediately
4. **Notify:** DPO auto-notified for breaches

**Incident Form:**
```
Incidents > Report Incident
- Title: Clear description
- Severity: Be realistic (Low/Medium/High/Critical)
- Type: Breach, Unauthorized Access, Loss, etc.
- Detection Date: When discovered
- Affected Subjects: Number of people
- Data Categories: What data compromised
- Description: Full details
- Actions Taken: What you've done
```

**Follow-Up:**
- Cooperate with investigation
- Implement corrective actions
- Update staff/procedures
- Document lessons learned

### Weekly Routine

**Monday:**
- Check notifications for new assignments
- Review department dashboard
- Plan week's compliance activities

**Mid-Week:**
- Process DSRs (keep within SLA)
- Update ROPA entries as needed
- Review risk mitigation progress

**Friday:**
- Complete pending tasks
- Update statuses
- Report issues to DPO if needed

### Monthly Tasks

1. **ROPA Review:** Check all entries still accurate
2. **Risk Review:** Check mitigation progress
3. **Training Check:** Ensure department staff compliant
4. **Incident Review:** Any trends or recurring issues?
5. **Policy Compliance:** Verify department following procedures

### Access Rights

**Can Access:**
- ROPA (full access for your department)
- DPIA (create and submit)
- DSR (handle requests)
- Incidents (report and manage)
- Risks (create and manage for department)
- Reports (department-specific)

**Cannot:**
- Approve DPIAs (DPO only)
- Access other departments' data (unless shared)
- Manage users
- Change system settings

### Tips for Success

**Documentation:**
- Keep detailed records
- "If it's not documented, it didn't happen"
- Update in real-time, not retrospectively

**Proactive:**
- Don't wait for DPO to find issues
- Report problems early
- Ask questions if unsure

**Collaboration:**
- Work closely with DPO
- Coordinate with other departments
- Share best practices

**Privacy by Design:**
- Involve compliance from project start
- Don't retrofit privacy
- Challenge unnecessary data collection

---

## 4. Staff Role

### Overview
Staff have limited access, primarily to view information, report incidents, complete training, and exercise data subject rights.

### Key Responsibilities

**Individual Compliance:**
- Complete assigned training
- Acknowledge policies
- Report incidents if witnessed
- Handle personal data responsibly
- Follow data protection procedures

### Core Functions

#### My Training

**Access Assigned Training:**
```
Training > My Training
- View all assigned courses
- See due dates
- Track completion status
```

**Complete Training:**
1. Click on training course
2. Review materials/watch videos
3. Complete assessment (if required)
4. Mark as complete
5. Download certificate (if available)

**Important:**
- Complete by due date
- Mandatory training affects compliance
- Contact manager if unclear

#### Report Incidents

**When to Report:**
- You receive suspicious email (phishing)
- You see unauthorized person accessing data
- Data accidentally sent to wrong person
- Lost/stolen laptop/device with data
- Any potential privacy violation

**How to Report:**
```
Incidents > Report Incident
- Describe what happened
- When it occurred
- What data affected (if known)
- Any actions you took
```

**Don't worry about false alarms:**
- Better to report and be safe
- No penalty for good faith reports
- Helps organization improve security

#### View Policies

**Access Policies:**
```
Policies > View Policies
- Read published policies
- Acknowledge when required
- Download for reference
```

**Acknowledgement:**
- Required for: Privacy Policy, Data Handling, Security Policy
- Simple checkbox: "I have read and understood"
- Tracked for compliance

#### Notifications

**Check Regularly:**
- Training assignments
- Policy acknowledgements
- System announcements

**How to Check:**
```
Click bell icon (top right)
- Read unread notifications (blue highlight)
- Click "Mark as Read" when done
```

### Your Data Rights

**As an Employee:**
You have the same data subject rights:

1. **Right to Access:** Request copy of your personal data held
2. **Right to Rectification:** Correct inaccurate data
3. **Right to Erasure:** Request deletion (limited for employee data)
4. **Right to Restriction:** Limit processing in certain circumstances
5. **Right to Portability:** Get data in portable format
6. **Right to Object:** Object to processing

**To Exercise Rights:**
- Contact your manager or DPO
- Formal request logged in DSR module
- Response within 30 days

### Best Practices for Staff

**DO:**
- Complete training on time
- Read and follow policies
- Report incidents immediately
- Ask if unsure about data handling
- Use strong passwords
- Lock screen when away from desk
- Only access data you need for your job

**DON'T:**
- Share login credentials
- Email personal data to personal accounts
- Take data home without authorization
- Discuss data in public places
- Leave documents on desk overnight
- Use unauthorized cloud storage
- Bypass security measures

### Access Rights

**Can:**
- View dashboard
- Report incidents
- View assigned training
- View published policies
- Access notifications
- View own profile

**Cannot:**
- Create ROPA, DPIA, DSR, Risks
- Access other users' data
- Generate reports
- Modify settings
- Access audit logs

### Getting Help

**Questions About:**
- **Training:** Contact your manager
- **Policies:** Contact DPO
- **Technical Issues:** Contact IT support
- **Login Problems:** Contact administrator

---

## 5. Auditor Role

### Overview
Auditors have read-only access to review compliance, monitor activities, and generate reports for audit purposes.

### Key Responsibilities

**Audit & Review:**
- Monitor compliance status
- Review audit logs
- Verify procedure adherence
- Identify gaps and weaknesses
- Generate audit reports

**Reporting:**
- Compliance dashboards
- Audit findings
- Recommendations for improvement
- Trend analysis

### Core Functions

#### Audit Log Review

**Access Audit Trail:**
```
Reports > Audit Log
- Filter by: Date, User, Action, Module
- Export for external audit
```

**What to Review:**
- User access patterns (unusual activity?)
- ROPA modifications (proper approvals?)
- DPIA decisions (DPO authorization?)
- DSR handling (SLA compliance?)
- Incident response (timely action?)
- Policy acknowledgements (100% completion?)

**Red Flags:**
- Excessive failed login attempts
- After-hours data access (unauthorized)
- Bulk data exports
- Deleted records without justification
- Policy violations

#### Compliance Monitoring

**Key Metrics to Track:**

**ROPA:**
- Completeness (all activities documented?)
- Accuracy (regular updates?)
- DPIA linkage (high-risk activities assessed?)

**DSR:**
- SLA performance (% within 30 days)
- Request volumes (trends)
- Rejection rate (with justification?)

**Incidents:**
- Incident frequency
- Breach notification compliance (72 hours)
- Repeat incidents (control failures?)

**Training:**
- Completion rates by department
- Overdue assignments
- Assessment scores

**Risks:**
- High-risk items (>15 score)
- Mitigation progress
- Overdue reviews

#### Generate Audit Reports

**Standard Audit Reports:**

**1. Compliance Status Report**
```
Reports > Compliance Dashboard
- Select date range
- Choose format (PDF/Excel)
- Include: All modules summary
```

**2. User Activity Report**
```
Reports > Audit Log
- Filter: Specific user or all users
- Date range: Last month/quarter
- Actions: All or specific (creates, deletes, exports)
```

**3. Module-Specific Reports**
```
Each module (ROPA, DPIA, DSR, etc.) > Export
- Current status of all items
- Filter by department, status, date
```

**4. Risk Analysis**
```
Risks > Risk Heatmap > Export
- Visual heat map
- Risk register details
- Mitigation status
```

### Audit Checklist

**Quarterly Compliance Audit:**

**ROPA Module:**
- [ ] All departments have documented processing activities
- [ ] Entries reviewed within last 6 months
- [ ] DPIA triggers identified and actioned
- [ ] Retention periods specified
- [ ] Legal basis documented

**DPIA Module:**
- [ ] All high-risk processing has approved DPIA
- [ ] DPO approval documented
- [ ] Residual risks acceptable
- [ ] Consultations completed (if required)

**Consent Module:**
- [ ] Consents properly documented
- [ ] Expiry dates monitored
- [ ] Withdrawal process available
- [ ] Consent freely given (not bundled/forced)

**DSR Module:**
- [ ] 100% of requests responded within SLA
- [ ] Identity verification performed
- [ ] Refusals properly justified
- [ ] Data subjects informed of POTRAZ complaint rights

**Incident Module:**
- [ ] All breaches reported to DPO immediately
- [ ] POTRAZ notifications within 72 hours (if required)
- [ ] Root cause analysis completed
- [ ] Corrective actions implemented

**Risk Module:**
- [ ] Risk register up to date
- [ ] High risks (≥15) have active mitigation
- [ ] Controls implemented and verified
- [ ] Regular reviews conducted

**Training Module:**
- [ ] 100% staff completed mandatory training
- [ ] New joiners trained within 30 days
- [ ] Annual refresher training completed
- [ ] Training effectiveness measured

**Policies Module:**
- [ ] All policies current (within review date)
- [ ] 100% staff acknowledged required policies
- [ ] Policies aligned with current law
- [ ] Version control maintained

### Reporting Findings

**Audit Report Structure:**

**1. Executive Summary**
- Overall compliance score
- Key findings
- Critical recommendations

**2. Detailed Findings**
- Module-by-module analysis
- Gaps identified
- Evidence/examples

**3. Recommendations**
- Prioritized by risk (High/Medium/Low)
- Specific, actionable
- Responsible party assigned

**4. Management Response**
- Actions planned
- Timeline for remediation
- Resource requirements

### Monthly Audit Tasks

**Week 1:**
- Generate prior month's reports
- Review audit logs for anomalies
- Check SLA compliance (DSR, breach notifications)

**Week 2:**
- Module-by-module compliance check
- Interview DPO and Department Owners
- Document findings

**Week 3:**
- Risk register review
- Control effectiveness testing
- Sample transaction testing

**Week 4:**
- Prepare audit report
- Present findings to management
- Follow up on prior audit remediation

### Access Rights

**Can:**
- View all modules (read-only)
- Access audit logs
- Generate all reports
- Export data for analysis

**Cannot:**
- Create, modify, or delete any records
- Approve DPIAs
- Handle DSRs
- Manage users
- Change settings

**Independence:**
- Maintain objectivity
- Report to audit committee/board
- No operational responsibilities

### Auditor Best Practices

**Independence:**
- Don't participate in operations you audit
- Avoid conflicts of interest
- Report findings objectively

**Evidence-Based:**
- Test samples, don't just review summaries
- Verify controls actually work
- Document all findings with evidence

**Risk-Based:**
- Focus on high-risk areas
- Allocate time based on risk
- Prioritize recommendations

**Continuous Improvement:**
- Track remediation of prior findings
- Recognize improvements
- Share best practices across departments

---

## Appendix: Role Comparison Matrix

| Capability | Admin | DPO | Dept Owner | Staff | Auditor |
|-----------|-------|-----|------------|-------|---------|
| **Users** |
| Create/Edit Users | ✓ | - | - | - | - |
| View Users | ✓ | ✓ | Dept Only | Own Only | ✓ |
| **ROPA** |
| Create ROPA | ✓ | ✓ | ✓ (Dept) | - | - |
| View ROPA | ✓ | ✓ | ✓ (Dept) | - | ✓ |
| Edit ROPA | ✓ | ✓ | ✓ (Own) | - | - |
| Delete ROPA | ✓ | ✓ | ✓ (Own) | - | - |
| **DPIA** |
| Create DPIA | ✓ | ✓ | ✓ | - | - |
| Approve DPIA | ✓ | ✓ | - | - | - |
| View DPIA | ✓ | ✓ | ✓ (Own) | - | ✓ |
| **DSR** |
| Create DSR | ✓ | ✓ | ✓ | - | - |
| Handle DSR | ✓ | ✓ | ✓ (Assigned) | - | - |
| View DSR | ✓ | ✓ | ✓ (Dept) | - | ✓ |
| **Incidents** |
| Report Incident | ✓ | ✓ | ✓ | ✓ | - |
| Investigate | ✓ | ✓ | ✓ (Dept) | - | - |
| View Incidents | ✓ | ✓ | ✓ (Dept) | Own Only | ✓ |
| **Risks** |
| Create Risk | ✓ | ✓ | ✓ (Dept) | - | - |
| Manage Controls | ✓ | ✓ | ✓ (Assigned) | - | - |
| View Risks | ✓ | ✓ | ✓ (Dept) | - | ✓ |
| **Policies** |
| Create Policy | ✓ | ✓ | - | - | - |
| Publish Policy | ✓ | ✓ | - | - | - |
| View Policy | ✓ | ✓ | ✓ | ✓ | ✓ |
| Acknowledge | ✓ | ✓ | ✓ | ✓ | - |
| **Training** |
| Create Training | ✓ | ✓ | - | - | - |
| Assign Training | ✓ | ✓ | - | - | - |
| Complete Training | ✓ | ✓ | ✓ | ✓ | - |
| View Completion | ✓ | ✓ | ✓ (Dept) | Own Only | ✓ |
| **Reports** |
| Generate Reports | ✓ | ✓ | ✓ (Dept) | - | ✓ |
| Audit Logs | ✓ | ✓ (Limited) | - | - | ✓ |
| Export Data | ✓ | ✓ | ✓ (Dept) | - | ✓ |
| **Settings** |
| System Settings | ✓ | - | - | - | - |
| Departments | ✓ | - | - | - | - |
| Email Config | ✓ | - | - | - | - |

---

**Document Version:** 1.0
**Last Updated:** October 2025

**For more information, see:**
- USER_MANUAL.md (Complete system guide)
- QUICK_START_GUIDE.md (Get started in 10 minutes)
