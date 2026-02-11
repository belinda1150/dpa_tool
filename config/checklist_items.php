<?php
/**
 * CDPA Compliance Gap Checklist - Item Definitions
 * Zimbabwe Cyber and Data Protection Act (CDPA)
 *
 * Each category maps to a CDPA section and contains checklist items.
 * Items are keyed by a unique item_key stored in compliance_responses table.
 */

$CHECKLIST_ITEMS = [
    'lawful_basis' => [
        'label' => 'Lawful Processing Basis',
        'cdpa_section' => 's.8',
        'items' => [
            'lawful_basis_1' => 'All processing activities have a documented lawful basis',
            'lawful_basis_2' => 'Consent is freely given, specific, informed and unambiguous where relied upon',
            'lawful_basis_3' => 'Legitimate interest assessments documented where applicable',
            'lawful_basis_4' => 'Special category data has explicit consent or legal basis',
        ]
    ],
    'data_subject_rights' => [
        'label' => 'Data Subject Rights',
        'cdpa_section' => 's.15-22',
        'items' => [
            'dsr_1' => 'Procedures exist for handling access requests within statutory timeframes',
            'dsr_2' => 'Mechanisms in place for data portability requests',
            'dsr_3' => 'Process for handling erasure/deletion requests documented',
            'dsr_4' => 'Rectification requests can be fulfilled promptly',
            'dsr_5' => 'Right to object to processing is facilitated and documented',
            'dsr_6' => 'Automated decision-making and profiling safeguards are in place',
        ]
    ],
    'ropa' => [
        'label' => 'Record of Processing Activities (ROPA)',
        'cdpa_section' => 's.12',
        'items' => [
            'ropa_1' => 'A comprehensive record of processing activities is maintained',
            'ropa_2' => 'ROPA includes all required fields (purposes, categories, recipients, retention)',
            'ropa_3' => 'ROPA is reviewed and updated at least annually',
        ]
    ],
    'dpia' => [
        'label' => 'Data Protection Impact Assessment',
        'cdpa_section' => 's.13',
        'items' => [
            'dpia_1' => 'DPIAs conducted for high-risk processing activities',
            'dpia_2' => 'DPIA process includes consultation with stakeholders and DPO',
            'dpia_3' => 'Mitigation measures identified and tracked for each DPIA',
            'dpia_4' => 'POTRAZ consulted where residual risk remains high after mitigation',
        ]
    ],
    'dpo' => [
        'label' => 'Data Protection Officer',
        'cdpa_section' => 's.11',
        'items' => [
            'dpo_1' => 'A Data Protection Officer has been formally appointed',
            'dpo_2' => 'DPO has adequate resources and independence to perform duties',
            'dpo_3' => 'DPO contact details communicated to data subjects and POTRAZ',
            'dpo_4' => 'DPO is involved in all data protection matters and decisions',
        ]
    ],
    'security' => [
        'label' => 'Security Measures',
        'cdpa_section' => 's.24-25',
        'items' => [
            'security_1' => 'Appropriate technical measures (encryption, access controls) are implemented',
            'security_2' => 'Organizational security measures (policies, training) are in place',
            'security_3' => 'Regular security assessments and penetration testing conducted',
            'security_4' => 'Access to personal data is restricted on a need-to-know basis',
            'security_5' => 'Data backup and disaster recovery procedures are documented and tested',
        ]
    ],
    'breach' => [
        'label' => 'Breach Notification',
        'cdpa_section' => 's.26',
        'items' => [
            'breach_1' => 'Incident response plan is documented and communicated',
            'breach_2' => 'Breach detection and escalation procedures are in place',
            'breach_3' => 'POTRAZ notification process within 72 hours is established',
            'breach_4' => 'Data subject notification procedures exist for high-risk breaches',
            'breach_5' => 'Breach register is maintained with root cause analysis',
        ]
    ],
    'crossborder' => [
        'label' => 'Cross-Border Transfers',
        'cdpa_section' => 's.28',
        'items' => [
            'crossborder_1' => 'All cross-border data transfers are identified and documented',
            'crossborder_2' => 'Adequate safeguards (contracts, adequacy) are in place for each transfer',
            'crossborder_3' => 'POTRAZ approval obtained where required for transfers',
            'crossborder_4' => 'Data subjects informed about cross-border transfers',
        ]
    ],
    'vendor' => [
        'label' => 'Third-Party / Vendor Management',
        'cdpa_section' => 's.10',
        'items' => [
            'vendor_1' => 'All data processors are identified and documented',
            'vendor_2' => 'Data processing agreements are in place with all processors',
            'vendor_3' => 'Processor compliance is monitored and audited regularly',
            'vendor_4' => 'Sub-processor arrangements are documented and authorized',
        ]
    ],
    'governance' => [
        'label' => 'Governance & Accountability',
        'cdpa_section' => 's.9',
        'items' => [
            'governance_1' => 'Data protection policy is documented, approved and communicated',
            'governance_2' => 'Privacy notices are provided to data subjects at point of collection',
            'governance_3' => 'Staff data protection training is conducted regularly',
            'governance_4' => 'Data retention schedule is defined and enforced',
            'governance_5' => 'Registration with POTRAZ as a data controller is current',
            'governance_6' => 'Regular compliance audits and reviews are conducted',
        ]
    ],
];
