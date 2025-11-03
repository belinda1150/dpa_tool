# Mandatory Documents Upload Guide
**Zimbabwe DPA Compliance - Document Management**

---

## Overview

This guide explains how to upload and manage the **9 Mandatory Documents** required for Zimbabwe Data Protection Authority (POTRAZ) compliance using the system's Policy Management module.

---

## Mandatory Documents Checklist

Based on Zimbabwe DPA requirements, the following documents must be maintained:

| # | Document Name | Already in System? | How to Manage |
|---|--------------|-------------------|---------------|
| 1 | Record of Processing Activity (ROPA) | ✅ **YES** - ROPA Module | Auto-generated register |
| 2 | Data Breach Incident Register | ✅ **YES** - Incidents Module | Auto-generated register |
| 3 | Data Breach Response Plan | ❌ Upload Required | Upload to Policy Module |
| 4 | Business Continuity and Disaster Recovery Plan | ❌ Upload Required | Upload to Policy Module |
| 5 | Data Protection / Privacy Policies | ❌ Upload Required | Upload to Policy Module |
| 6 | Privacy Notice | ❌ Upload Required | Upload to Policy Module |
| 7 | Data Sharing Agreements | ❌ Upload Required | Upload to Policy Module |
| 8 | Data Subject Access Requests (Register) | ✅ **YES** - DSR Module | Auto-generated register |
| 9 | Privacy Risk Assessment Reports | ✅ **YES** - Risk/DPIA Modules | Auto-generated reports |

**Summary:** 4 out of 9 documents are automatically managed by the system. 5 document-based policies need to be uploaded.

---

## Part 1: System-Generated Documents (Already Available)

### Document 1: ROPA Register ✅

**What it is:** Complete register of all data processing activities

**How to access:**
1. Navigate to **ROPA** > **ROPA List**
2. Click **Export** button
3. Choose format: CSV or PDF
4. Save file with name: `ROPA_Register_[Date].pdf`

**For POTRAZ submission:**
- Export monthly or when requested
- Ensure all processing activities are documented
- Review for completeness before export

---

### Document 2: Data Breach Incident Register ✅

**What it is:** Log of all data protection incidents and breaches

**How to access:**
1. Navigate to **Incidents** > **Incident List**
2. Click **Export** button
3. Choose format: CSV or PDF
4. Save file with name: `Incident_Register_[Date].pdf`

**For POTRAZ submission:**
- Export when breach occurs (72-hour notification)
- Include in annual compliance reports
- Maintain detailed incident records in system

---

### Document 8: DSR Register ✅

**What it is:** Register of all Data Subject Rights requests and responses

**How to access:**
1. Navigate to **DSR** > **DSR Requests**
2. Click **Export** button
3. Choose format: CSV or PDF
4. Save file with name: `DSR_Register_[Date].pdf`

**For POTRAZ submission:**
- Shows 30-day SLA compliance
- Documents all request types (access, erasure, etc.)
- Includes resolution status

---

### Document 9: Privacy Risk Assessment Reports ✅

**What it is:** Risk assessments and DPIAs for high-risk processing

**How to access:**

**Risk Register:**
1. Navigate to **Risks** > **Risk Heatmap**
2. Click **Export** button
3. Save as: `Risk_Register_[Date].pdf`

**DPIA Reports:**
1. Navigate to **DPIA** > **DPIA List**
2. Click on individual DPIA to view
3. Export or print to PDF
4. Save as: `DPIA_[Project_Name]_[Date].pdf`

**For POTRAZ submission:**
- Include risk heat map
- Attach approved DPIAs for major projects
- Show risk mitigation progress

---

## Part 2: Documents to Upload (Policy Module)

### Step-by-Step Upload Process

#### Step 1: Access Policy Management

1. **Login** to the DPA Tool
2. Navigate to **Policy & Training** > **Policies** in the sidebar
3. Click **Add New Policy** button

#### Step 2: Complete Policy Upload Form

For each mandatory document, fill in the form as follows:

**Required Fields:**
- **Policy Title:** Enter exact document name (see mapping below)
- **Policy Type:** Select appropriate type from dropdown
- **Version:** Start with "1.0"
- **Description:** Brief summary of the document
- **Policy Document:** Upload your PDF/DOC file (max 10MB)
- **Status:** Select "Published" (for active compliance)
- **Review Due Date:** Set to 12 months from today (annual review)

#### Step 3: Upload Document File

1. Click **Choose File** under "Policy Document"
2. Select your document (PDF recommended)
3. Supported formats: PDF, DOC, DOCX, TXT
4. Maximum file size: 10MB

#### Step 4: Publish Policy

1. Set **Status** to "Published"
2. Click **Create Policy** button
3. System will notify DPO and staff (if required)
4. Document is now accessible to authorized users

---

## Document Upload Mapping

### Document 3: Data Breach Response Plan

**Form Details:**
```
Policy Title: Data Breach Response Plan
Policy Type: Breach Response
Version: 1.0
Description: Step-by-step procedures for responding to data breaches, including 72-hour POTRAZ notification timeline and data subject communication protocols.
Document: [Upload your Data_Breach_Response_Plan.pdf]
Status: Published
Review Due Date: [Today + 12 months]
```

**What to include in the document:**
- Breach detection procedures
- Incident response team contacts
- 72-hour POTRAZ notification process
- Data subject notification templates
- Containment and recovery steps
- Post-incident review procedures

**Template available:** See Appendix A below

---

### Document 4: Business Continuity and Disaster Recovery Plan

**Form Details:**
```
Policy Title: Business Continuity and Disaster Recovery Plan
Policy Type: Security
Version: 1.0
Description: Comprehensive plan for maintaining data protection compliance during business disruptions, including backup procedures, recovery time objectives (RTO), and recovery point objectives (RPO).
Document: [Upload your BC_DR_Plan.pdf]
Status: Published
Review Due Date: [Today + 12 months]
```

**What to include in the document:**
- Critical systems inventory
- Data backup procedures and schedules
- Recovery time objectives (RTO: <24 hours recommended)
- Recovery point objectives (RPO: <1 hour for critical data)
- Alternative processing sites
- Emergency contact list
- Testing and maintenance schedule

---

### Document 5: Data Protection / Privacy Policies

**Form Details:**
```
Policy Title: Data Protection Policy
Policy Type: Data Protection
Version: 1.0
Description: Organization-wide policy governing the collection, processing, storage, and protection of personal data in compliance with Zimbabwe's Data Protection Act and POTRAZ regulations.
Document: [Upload your Data_Protection_Policy.pdf]
Status: Published
Review Due Date: [Today + 12 months]
```

**What to include in the document:**
- Data protection principles
- Lawful basis for processing
- Data subject rights procedures
- Data retention schedules
- Security measures
- Third-party data sharing rules
- International data transfers policy
- Breach notification procedures
- Staff responsibilities
- DPO contact information

**Note:** You may need to upload multiple policies:
- General Data Protection Policy
- Employee Privacy Policy
- Customer Privacy Policy
- Information Security Policy

Create separate entries for each.

---

### Document 6: Privacy Notice

**Form Details:**
```
Policy Title: Privacy Notice
Policy Type: Privacy
Version: 1.0
Description: Public-facing privacy notice informing data subjects about how their personal data is collected, used, stored, and protected. Compliant with Zimbabwe DPA transparency requirements.
Document: [Upload your Privacy_Notice.pdf]
Status: Published
Review Due Date: [Today + 12 months]
```

**What to include in the document:**
- Organization identity and contact details
- DPO contact information
- Types of personal data collected
- Purposes of processing
- Legal basis for processing
- Data retention periods
- Data subject rights (access, erasure, etc.)
- Right to complain to POTRAZ
- How to exercise rights
- Whether data is shared with third parties
- International data transfers (if any)
- Cookies and tracking (if website)

**Display requirements:**
- Must be publicly accessible
- Clear, plain language
- Easy to find on website/forms
- Available before data collection

---

### Document 7: Data Sharing Agreements

**Form Details:**
```
Policy Title: Data Sharing Agreement Template
Policy Type: Other
Version: 1.0
Description: Standard contractual template for sharing personal data with third parties, including data processors and joint controllers. Includes data protection clauses required by Zimbabwe DPA.
Document: [Upload your Data_Sharing_Agreement_Template.pdf]
Status: Published
Review Due Date: [Today + 12 months]
```

**What to include in the document:**
- Parties to the agreement
- Purpose of data sharing
- Categories of data shared
- Security obligations
- Confidentiality requirements
- Data subject rights obligations
- Sub-processing restrictions
- Audit rights
- Breach notification obligations
- Term and termination
- Liability and indemnification
- POTRAZ compliance clauses

**Note:** You can upload:
- Master template (as shown above)
- Individual executed agreements (create separate entries for each third party)

---

## Quick Upload Workflow

### For Users with Documents Already Prepared

**Time to complete: 5 minutes per document**

1. **Login** → Policy & Training → Policies → Add New Policy
2. **Enter title** from mapping above
3. **Select type** from mapping above
4. **Set version** to "1.0"
5. **Add description** from mapping above (or customize)
6. **Upload file** (your PDF document)
7. **Set status** to "Published"
8. **Set review date** to 12 months from today
9. **Click Create Policy**
10. **Repeat** for next document

**Total time for all 5 documents: ~25 minutes**

---

### For Users Without Documents Yet

**Option 1: Use Templates**
- See Appendix A for basic templates
- Customize for your organization
- Have legal/DPO review
- Upload to system

**Option 2: Professional Development**
- Engage data protection consultant
- Engage legal counsel
- Use POTRAZ guidance documents
- Develop comprehensive policies

**Recommended Priority:**
1. **Privacy Notice** (High - required for data collection)
2. **Data Breach Response Plan** (High - required for incidents)
3. **Data Protection Policy** (High - foundation document)
4. **BC/DR Plan** (Medium - disaster preparedness)
5. **Data Sharing Agreements** (Medium - as needed for third parties)

---

## Managing Uploaded Documents

### View Documents

**Navigate to:** Policy & Training > Policies

**Features:**
- Search/filter policies
- View acknowledgement status (% of staff who have acknowledged)
- Check review due dates
- Download documents

### Edit Documents

1. Click **Edit** button (pencil icon) on policy row
2. Update details as needed
3. Upload new version of document (optional)
4. Increment version number (e.g., 1.0 → 2.0)
5. Click **Update Policy**

**Note:** Old version is replaced. Keep archived copies externally if needed.

### Track Staff Acknowledgement

**For Published Policies:**
- System automatically requires staff acknowledgement
- View acknowledgement % on policy list
- Staff receive notification when policy published
- Staff must click "Acknowledge" after reading

**To view who has acknowledged:**
1. Click policy title to view details
2. See acknowledgement list
3. Send reminders to non-acknowledgers

### Set Review Reminders

**Best Practice:** Set review dates for all policies

1. Edit policy
2. Set "Review Due Date" to 12 months from publication
3. System will alert when review is due
4. Review and update policy annually
5. Increment version number if changes made

---

## Compliance Verification Checklist

Before POTRAZ audit or compliance review:

### Documents Uploaded ✓

- [ ] Data Breach Response Plan uploaded and published
- [ ] BC/DR Plan uploaded and published
- [ ] Data Protection Policy uploaded and published
- [ ] Privacy Notice uploaded and published
- [ ] Data Sharing Agreement template uploaded and published

### System Registers Current ✓

- [ ] ROPA register is complete and up-to-date
- [ ] All incidents logged in Incident Register
- [ ] All DSRs logged in DSR Register
- [ ] Risk assessments completed and current
- [ ] DPIAs completed for high-risk processing

### Staff Compliance ✓

- [ ] 100% of staff have acknowledged Data Protection Policy
- [ ] 100% of staff have acknowledged Privacy Notice (if internal)
- [ ] All staff completed data protection training

### Annual Review ✓

- [ ] All policies reviewed within last 12 months
- [ ] Review due dates set for upcoming year
- [ ] Version numbers updated if policies changed
- [ ] Old versions archived externally

---

## Export for POTRAZ Submission

### Complete Document Package

When POTRAZ requests documentation:

**Step 1: Export System Registers**
```
1. ROPA → Export to PDF
2. Incidents → Export to PDF
3. DSR → Export to PDF
4. Risks → Export Risk Heatmap to PDF
5. DPIA → Export individual DPIAs to PDF
```

**Step 2: Download Policy Documents**
```
1. Go to Policy List
2. Click on each mandatory document
3. Download attached PDF
4. Save to folder: "POTRAZ_Submission_[Date]"
```

**Step 3: Create Cover Letter**
```
Include:
- Organization details
- POTRAZ registration number
- Submission date
- Document checklist
- DPO signature
```

**Step 4: Submit Package**
```
- Combine all documents
- Create single PDF or ZIP file
- Submit via POTRAZ portal or email
- Keep copy for records
```

---

## Appendix A: Basic Document Templates

### Template 1: Data Breach Response Plan (Outline)

```markdown
# DATA BREACH RESPONSE PLAN

**Organization:** [Your Organization Name]
**Version:** 1.0
**Effective Date:** [Date]
**Review Date:** [Date + 12 months]

## 1. PURPOSE
This plan establishes procedures for responding to data protection incidents and breaches to ensure compliance with Zimbabwe's Data Protection Act and POTRAZ's 72-hour notification requirement.

## 2. DEFINITIONS
- **Personal Data Breach:** Breach of security leading to accidental or unlawful destruction, loss, alteration, unauthorized disclosure of, or access to, personal data
- **Data Protection Officer (DPO):** [Name, Contact]
- **Incident Response Team:** [List members]

## 3. BREACH DETECTION
- Staff reporting procedures
- System monitoring alerts
- Third-party notifications
- Regular security audits

## 4. IMMEDIATE RESPONSE (0-24 hours)
1. **Contain the breach**
   - Isolate affected systems
   - Stop unauthorized access
   - Secure compromised accounts

2. **Notify DPO immediately**
   - DPO Contact: [Email/Phone]
   - Available 24/7: [Emergency Contact]

3. **Assess severity**
   - How many data subjects affected?
   - What data categories compromised?
   - Likelihood of harm to individuals?

4. **Document everything**
   - Log in Incident Register (system)
   - Keep detailed notes of all actions
   - Preserve evidence

## 5. INVESTIGATION (24-48 hours)
1. **Root cause analysis**
   - How did breach occur?
   - Which systems affected?
   - Duration of breach

2. **Scope assessment**
   - Full extent of data compromised
   - All affected data subjects identified
   - Potential consequences assessed

## 6. NOTIFICATION (Within 72 hours)
### POTRAZ Notification
**Required if:** Breach likely to result in risk to rights and freedoms

**Deadline:** 72 hours from discovery

**Method:** POTRAZ online portal / email: [contact@potraz.gov.zw]

**Content:**
- Nature of breach
- Categories and approximate number of data subjects affected
- Categories of personal data compromised
- Contact point (DPO details)
- Likely consequences
- Measures taken or proposed to address breach
- Measures to mitigate adverse effects

### Data Subject Notification
**Required if:** High risk to rights and freedoms

**Deadline:** Without undue delay

**Method:** Email, letter, or public notice (if large scale)

**Content:**
- Clear, plain language description
- DPO contact details
- Likely consequences
- Measures taken to mitigate
- Recommended protective actions for data subjects

## 7. REMEDIATION
- Implement security improvements
- Update policies/procedures
- Staff training
- Third-party security reviews
- System upgrades

## 8. POST-INCIDENT REVIEW
- Lessons learned session
- Update breach response plan
- Document improvements
- Share insights (anonymized) with staff

## 9. CONTACTS
**DPO:** [Name, Email, Phone]
**IT Security:** [Name, Email, Phone]
**Legal Counsel:** [Name, Email, Phone]
**POTRAZ Helpdesk:** [POTRAZ Contact]
**External Forensics:** [Company, Contact]

## 10. APPENDICES
- Breach Notification Form Template
- Data Subject Notification Template
- Breach Assessment Checklist
- POTRAZ Submission Template

---
**Approval:**
DPO Signature: _________________ Date: _______
Management: _________________ Date: _______
```

### Template 2: Privacy Notice (Basic Structure)

```markdown
# PRIVACY NOTICE

**Organization:** [Your Organization Name]
**Effective Date:** [Date]
**Last Updated:** [Date]

## Who We Are
[Organization Name] is a [type of business] registered in Zimbabwe.
Our POTRAZ registration number is: [Number]

**Contact Details:**
Address: [Full Address]
Email: [Contact Email]
Phone: [Phone Number]

**Data Protection Officer:**
Name: [DPO Name]
Email: [DPO Email]
Phone: [DPO Phone]

## What Personal Data We Collect
We collect and process the following categories of personal data:
- Names and contact details
- Identification numbers (National ID, Passport)
- [Add other categories specific to your business]

## How We Collect Your Data
We collect data:
- Directly from you (forms, applications, communications)
- From third parties (with your consent)
- Through our website (cookies, analytics)

## Why We Process Your Data (Legal Basis)
We process your personal data for the following purposes:
1. **Contract Performance:** To provide services you requested
2. **Legal Obligation:** To comply with Zimbabwe laws
3. **Legitimate Interests:** [Specify interests]
4. **Consent:** Where you have given explicit consent

## Who We Share Your Data With
We may share your data with:
- Service providers (data processors)
- Regulatory authorities (POTRAZ, tax authorities)
- Legal advisors
- [Other categories]

We do NOT sell your personal data to third parties.

## International Data Transfers
[If applicable:]
We transfer your data to [Country] for [Purpose].
Safeguards in place: [Standard Contractual Clauses / Adequacy Decision]

[If not applicable:]
We do not transfer your personal data outside Zimbabwe.

## How Long We Keep Your Data
- Customer data: [X years] after relationship ends
- Employee data: [X years] after employment ends
- [Other retention periods]

## Your Rights
You have the right to:
1. **Access:** Request a copy of your personal data
2. **Rectification:** Correct inaccurate data
3. **Erasure:** Request deletion of your data
4. **Restriction:** Limit how we use your data
5. **Portability:** Receive data in machine-readable format
6. **Object:** Object to processing for specific purposes

**To exercise your rights:** Contact our DPO at [email]

## Right to Complain
If you are unhappy with how we handle your data, you have the right to complain to:

**Postal and Telecommunications Regulatory Authority of Zimbabwe (POTRAZ)**
Address: [POTRAZ Address]
Email: [POTRAZ Email]
Phone: [POTRAZ Phone]

## Security
We protect your data using:
- Encryption
- Access controls
- Regular security audits
- Staff training

## Changes to This Notice
We may update this privacy notice from time to time. Check this page for updates.

**Last updated:** [Date]

---
For questions about this privacy notice, contact our DPO at [email].
```

---

## Support & Questions

**Need Help Uploading Documents?**
- Contact your System Administrator
- Email: admin@yourorganization.com

**Policy Content Questions?**
- Contact your DPO
- Email: dpo@yourorganization.com

**Technical Issues?**
- See USER_MANUAL.md Section 5.8 (Policies Module)
- See ADMIN_SETUP_GUIDE.md for system setup

---

**Document Version:** 1.0
**Last Updated:** October 2025
**Next Review:** April 2026

**Start uploading your mandatory documents today to ensure POTRAZ compliance!**
