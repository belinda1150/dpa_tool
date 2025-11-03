# CSS Organization - Zimbabwe DPA Tool

## Overview
All inline CSS has been extracted to external stylesheets in the `/assets/css/` folder for better organization and maintainability.

## CSS Files Structure

### 1. **login.css**
**Location:** `assets/css/login.css`
**Used by:** `index.php` (login page)
**Purpose:** Login page specific styles
- Login form styling
- Background image configuration
- Input field animations
- Button styles
- Error message styling

### 2. **dashboard-custom.css**
**Location:** `assets/css/dashboard-custom.css`
**Used by:** `dashboard.php`, all pages with header navigation
**Purpose:** Dashboard and header styles
- Navigation bar styling (`.navbar-cls-top`, `.navbar-user-info`)
- Compliance score panel styling
- KPI card colors
- Statistics display styles
- Role badge styling

**Key Classes:**
- `.navbar-user-info` - Header user information
- `.role-badge` - User role badge
- `.panel-compliance-header` - Compliance panel header
- `.compliance-score` - Large compliance score number
- `.compliance-score-excellent/good/poor` - Color variations
- `.stat-details` - Small statistical details
- `.icon-box-purple/green/blue/red` - Colored icon boxes

### 3. **module-styles.css**
**Location:** `assets/css/module-styles.css`
**Used by:** All module pages (ROPA, DPIA, DSR, Incidents, Risks, Consents)
**Purpose:** Utility classes to replace inline styles

**Utility Classes:**

#### Typography
- `.text-xs` - Font size 11px
- `.text-sm` - Font size 12px
- `.text-md` - Font size 14px
- `.text-lg` - Font size 16px

#### Margins
- `.m-0`, `.mt-0`, `.mb-0` - Zero margins
- `.mb-10`, `.mb-15`, `.mb-20` - Bottom margins
- `.mt-15`, `.mt-n22` - Top margins
- `.ml-5`, `.ml-10`, `.ml-15` - Left margins
- `.my-5`, `.my-10` - Vertical margins

#### Padding
- `.p-0`, `.p-10`, `.p-15` - Padding
- `.pl-30` - Left padding
- `.px-10-20` - Horizontal padding

#### Background Boxes
- `.bg-box-light` - Light gray background box with border
- `.bg-box-light-simple` - Simplified light box
- `.bg-box-warning` - Warning colored box
- `.bg-box-warning-simple` - Simple warning box
- `.bg-box-yellow` - Yellow highlighted box

#### Other Utilities
- `.activity-item` - Timeline/activity item styling
- `.btn-print`, `.btn-back` - Print and back buttons
- `.list-sm`, `.list-xs` - Small list styles
- `.alert-sm`, `.alert-mt` - Alert variations
- `.no-print` - Hide element when printing

### 4. **export-styles.css**
**Location:** `assets/css/export-styles.css`
**Used by:** All export pages (*_export.php)
**Purpose:** Print/PDF export formatting

**Features:**
- Print-optimized layouts
- Professional report headers
- Table formatting for reports
- Badge styles for status indicators
- Page break controls
- ROPA register specific styles
- Report footer formatting

**Key Classes:**
- `.header` - Report header section
- `.section` - Report section container
- `.section-title` - Section heading
- `.info-table` - Data tables
- `.badge-success/warning/danger/info` - Status badges
- `.header-info` - Report metadata box
- `.page-break` - Page break for printing
- `.list-compact` - Compact list styling

## Files Updated

### Fully Migrated:
1. ✅ `index.php` - Uses `login.css`
2. ✅ `includes/header.php` - Uses `dashboard-custom.css`
3. ✅ `dashboard.php` - Uses `dashboard-custom.css`
4. ✅ `consent_export.php` - Uses `export-styles.css` + `module-styles.css`
5. ✅ `ropa_export.php` - Uses `export-styles.css` + `module-styles.css`

### Remaining Export Files (Need Style Block Removal):
6. ⏳ `dpia_export.php` - Lines 170-321 contain `<style>` block
7. ⏳ `dsr_export.php` - Lines 138-240 contain `<style>` block
8. ⏳ `incident_export.php` - Lines 132-243 contain `<style>` block
9. ⏳ `risk_export.php` - Lines 170-306 contain `<style>` block

### View Files with Inline Styles:
10. ⏳ `consent_view.php`
11. ⏳ `dsr_view.php`
12. ⏳ `incident_view.php`
13. ⏳ `risk_view.php`
14. ⏳ `dpia_view.php`
15. ⏳ `ropa_view.php`

### Other Module Files (31 total with inline styles):
- All list, add, edit, process, and approve pages across all modules

## How to Complete Migration

### For Export Files:
```php
// BEFORE (in <head> section):
<style>
    body { font-family: Arial; margin: 20px; }
    .header { text-align: center; }
    /* ...more styles... */
</style>

// AFTER:
<link rel="stylesheet" href="assets/css/export-styles.css">
<link rel="stylesheet" href="assets/css/module-styles.css">
```

### For Inline Styles:
```php
// BEFORE:
<div style="margin-bottom: 20px; padding: 10px;">

// AFTER:
<div class="mb-20 p-10">
```

### For Module Pages:
Add to `<head>` section or after existing CSS links:
```php
<link rel="stylesheet" href="assets/css/module-styles.css">
```

## Benefits

1. **Decluttered Code** - No more CSS mixed with PHP/HTML
2. **Ease of Access** - All styles in one organized location
3. **Maintainability** - Change styles globally by editing one file
4. **Performance** - Browser caching of CSS files
5. **Consistency** - Reusable utility classes ensure consistent styling
6. **Print Optimization** - Dedicated export styles for professional PDFs

## Next Steps

1. Remove `<style>` blocks from remaining 4 export files
2. Add CSS links to all module view/list/add/edit pages
3. Replace remaining inline `style="..."` with utility classes
4. Test all pages to ensure styles load correctly

## CSS File Locations

```
C:\xampp\htdocs\data_protection\
└── assets/
    └── css/
        ├── login.css              (Login page styles)
        ├── dashboard-custom.css   (Dashboard & header styles)
        ├── module-styles.css      (Utility classes)
        ├── export-styles.css      (Export/print styles)
        ├── bootstrap.css          (Bootstrap framework)
        ├── font-awesome.css       (Icons)
        └── custom.css             (Existing custom styles)
```

---
**Last Updated:** October 28, 2025
**Status:** CSS organization in progress - core files complete, module files pending
