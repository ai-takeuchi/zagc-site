# ZAGC

ZAGC is your static site's best companion:

<ul style="list-style: none; padding-left: 1em;">
  <li>🚀 Blazing-fast builds with Zola</li>
  <li>🤖 Smooth automation via GitHub Actions</li>
  <li>🧠 Flexible headless content management with Cockpit CMS</li>
</ul>

Build fast, update easily, and deploy ✨

🧪 **Note**: ZAGC is in an early testing phase. It’s under development and currently being trialed in real-world scenarios.

---

## Overview

**ZAGC** is a lightweight and flexible template stack designed for building and managing static websites.
It integrates multiple tools to automate everything from content management to build and deployment:

* [Zola](https://www.getzola.org/) – A Rust-based static site generator
* [Cockpit CMS](https://getcockpit.com/) – A headless CMS
* GitHub Actions – For automated builds and deployments

---

## How It Works

1. Fetch content and assets from Cockpit
2. Convert to Markdown (compatible with Zola)
3. Convert and resize uploaded images to `.webp`
4. Build the static site using Zola
5. Upload to the public server
6. Archive the current website on GitHub (for version history)

---

## Optimized for Migration from Other Environments

ZAGC is intended as a practical alternative for users facing challenges with traditional dynamic CMS systems.

Examples of systems you can migrate from:

* Headless CMS that provide content via REST API
* Cloud services allowing posting and image upload via admin UI
* Traditional CMS using themes and templates for visual design

ZAGC focuses on the following to ensure smooth and natural operation post-migration:

### ✅ Structure-Aware Content Conversion

* Converts standard structures like posts, pages, tags, and categories into Zola-compatible Markdown
* Supports flexible schema designs including custom fields

### ✅ Automated Asset Processing

* Downloads images along with content and converts them to `.webp` by use case
* Automatically generates thumbnails, medium, and large sizes
* Removes original images to reduce storage footprint

### ✅ Optimized for Static Site Operation

* Automates build and publish via GitHub Actions
* Enables fast updates and minimizes deployment errors
* Reduces infrastructure dependency so you can focus on content

With this setup, you can break free from the limitations and performance concerns of dynamic CMSs, and run a website focused on **maintainability, speed, and freedom**.

---

## Project Structure

```
github-project-repo/
├── .env.example.php        # Example environment configuration file (template for env vars)
├── .env.php                # Actual environment configuration file (should be gitignored)
├── .gitignore              # Files and directories ignored by Git
├── .gitattributes          # Git settings for handling files (e.g., line endings)
├── .github/
│   └── workflows/
│       └── build.yml      # GitHub Actions workflow definition for CI/CD
├── .bin/
│   └── zola               # Fixed version of the Zola binary (static site generator)
├── cockpit/                # API extensions or custom modules for Cockpit CMS
├── scripts/                # Go / Shell scripts for content and build management
│   ├── image_process.go     # Image conversion and resizing (.webp, thumbnails, etc.)
│   ├── convert_to_md.go     # Convert Cockpit JSON to Markdown and clean up missing content
│   ├── fetch_assets.go      # Download new assets from Cockpit and remove deleted ones
│   ├── load_env_php.sh      # Parse PHP-style config (define('KEY','VAL')) and export as env vars
│   └── run_build.sh         # Fetch data, convert content, build Zola site, and deploy via FTP
├── data/                   # Temporary JSON files fetched from Cockpit API
│   └── items.json
├── tmp/                    # Temporary files (e.g., Zola installation workspace)
├── zola/
│   ├── shortcodes/        # Zola shortcodes (reusable mini-templates)
│   ├── content/           # Markdown content (converted from Cockpit)
│   │   ├── blog/
│   │   └── info/
│   ├── templates/         # Zola HTML templates
│   │   └── partials/       # Template fragments (e.g., header, footer)
│   ├── sass/              # SCSS (Sass) files for styling
│   ├── static/            # Static assets served as-is (not processed by Zola)
│   │   ├── api/            # REST API endpoints or related scripts
│   │   ├── img/            # Images used in the site
│   │   ├── js/             # JavaScript files
│   │   └── uploads/        # Uploaded files from CMS or elsewhere
│   ├── themes/            # (Optional) Zola themes (if used)
│   ├── config.toml        # Zola configuration file (site settings, language, base URL, etc.)
│   ├── Cargo.toml         # Rust project config (for custom shortcodes if any)
│   └── public/            # Static site output generated by `zola build`
├── history/                # Archive directory for build outputs (committed to build-history branch)
├── README.md               # Project overview and usage instructions
└── LICENSE
```

---

## GitHub Secrets (Environment Variables)

```env
# Cockpit CMS
COCKPIT_URL=http://localhost/cockpit
COCKPIT_TOKEN=xxxxxxxx
COCKPIT_SPACE=your-space-name (optional)
COCKPIT_ITEMS_API_PATH=api/content/items
COCKPIT_ITEMS=info,blog
COCKPIT_ASSETS_API_PATH=api/public/getAssets

# If deploying to a website that includes a path, be sure to include the path as well, e.g., https://example.com/path
DEPLOY_URL=/

# FTP upload settings (optional)
FTP_HOST=ftp.example.com
FTP_PORT=21
FTP_HOST_PATH=/
FTP_USER=username
FTP_PASSWORD=password
FTP_REMOTE_DIR=/htdocs/
```

---

## ✅ How to Obtain a GitHub Token (`GITHUB_TOKEN`)

### 1. Log in to GitHub

Go to [https://github.com](https://github.com) and log in to your account.

---

### 2. Navigate to the Token Creation Page

- Click on your profile icon at the top right corner and select `Settings`.
- In the left sidebar, select `Developer settings` → `Personal access tokens` → `Tokens (classic)`.
- Click on **"Generate new token (classic)"**.

---

### 3. Configure the Token

- **Note**: Provide a label or note for the token (e.g., `Deploy Trigger Token`).
- **Expiration**: Set an expiration date (recommended: `90 days` or `No expiration`).
- **Scopes (permissions)**: Check at least the following scopes:
  - ✅ `repo` (to access your repository)
  - ✅ `workflow` (to manage GitHub Actions workflows)

---

### 4. Save the Token

The token will only be displayed once. Make sure to copy it and store it securely.

Add the following to your `.env.php` file.

---

## Notes and Design Principles

* **Fixed version of Zola** to avoid future compatibility issues
* `.bin/` and `zola/public/` are excluded from Git version control via `.gitignore`
* Efficient syncing: **delta downloads** and asset cleanup from Cockpit to minimize traffic
* Go scripts use **direct CLI tool calls** (e.g. ImageMagick) without Go modules
* GitHub Actions installs required tools like `imagemagick` and `webp` using:

```yaml
- name: Install ImageMagick and WebP tools
  run: sudo apt-get update && sudo apt-get install -y imagemagick webp
```

---

## Roadmap & Future Improvements

* Better error handling and logging
* Generalization of templates and configuration
* Further optimization for migration scenarios

---

## Zola Template Features

* **Categories**: Organize and group your content with category support.
* **Breadcrumbs**: Easily navigate your site’s hierarchy with breadcrumbs.
* **Code Highlighting**: Syntax highlighting for code blocks for better readability.
* **Prev/Next Links**: Seamlessly move to the previous or next post in the section.
* **Sidebar**: A handy sidebar for additional navigation or widgets.
* **Hamburger Menu**: A responsive hamburger menu for mobile-friendly navigation.
* **Inquiry Send Mail**: Contact form functionality to send inquiries via email.

---

## License

MIT License

---

## Credits

* [Zola](https://www.getzola.org/) – Static Site Generator
* [Cockpit CMS](https://getcockpit.com/) – Headless CMS
* GitHub Actions – CI/CD automation

---
