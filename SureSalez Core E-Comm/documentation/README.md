# SureSalez Enterprise Commerce Suite — Quick-Start Guide

Welcome to the **SureSalez Enterprise Commerce Suite** (Commercial Release v1.9.0).

This package contains the decoupled e-commerce engine **SureSalez Core** and its companion design-token Full Site Editing (FSE) block theme **SureSalez Theme**.

---

## Package Directory Contents

```
SureSalez Core E-Comm/
├── installable-archives/
│   ├── suresalez-theme.zip          # Standalone installable WordPress FSE theme
│   └── suresalez-core.zip           # Companion e-commerce engine plugin
├── documentation/
│   ├── index.html                   # Professional, responsive offline documentation
│   ├── README.md                    # Quick-start markdown documentation (this file)
│   └── assets/
│       ├── docs.css                 # Documentation stylesheet
│       └── docs.js                  # Search & navigation scripts
├── licensing/
│   ├── LICENSE.txt                  # GNU General Public License v2 (GPL-2.0-or-later)
│   └── ENVATO-LICENSE-TERMS.txt     # Commercial Regular & Extended License terms
├── sample-data/
│   ├── demo-products.json           # 50 sample jewelry SKUs across 4 categories
│   ├── demo-theme-settings.json     # Pre-configured theme presets & color palette
│   └── demo-suresalez-modules.json  # Pre-configured active module toggle state
└── README_FIRST.txt                 # Step-by-step buyer orientation guide
```

---

## 5-Minute Quick Start

### Step 1: Install SureSalez Theme
1. Open your WordPress admin panel (`/wp-admin`).
2. Go to **Appearance → Themes → Add New Theme → Upload Theme**.
3. Choose `installable-archives/suresalez-theme.zip`.
4. Click **Install Now**, then click **Activate**.

### Step 2: Install SureSalez Core Plugin
1. Go to **Plugins → Add New Plugin → Upload Plugin**.
2. Choose `installable-archives/suresalez-core.zip`.
3. Click **Install Now**, then click **Activate Plugin**.
4. Database tables (46 tables, schema v1.9.0) will be automatically created.

### Step 3: Configure Active Modules
1. Go to **SureSalez → Modules** in the WordPress sidebar.
2. Toggle features on/off based on your store's requirements (e.g. B2B Wholesale, In-Store POS, AI Commerce).
3. Click **Save Changes**.

---

## Offline Documentation

For comprehensive guides on Multi-Currency, Indian GST splits, 1-Click Upsells, B2B RFQ, and REST APIs, open `documentation/index.html` in any web browser.

---

## Support & Assistance

- **Documentation**: Open `documentation/index.html`
- **Email Support**: `support@suresalez.io`
- **License**: Dual GPL v2.0 & Commercial Envato Marketplace Terms
