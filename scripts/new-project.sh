#!/bin/bash
set -e

# =============================================================================
# New TailPress Project Setup Script
# Usage: ./setup.sh <project-name> [site-title]
# =============================================================================

PROJECT_NAME="$1"
SITE_TITLE="${2:-$(echo "$PROJECT_NAME" | sed 's/-/ /g' | sed 's/\b\(.\)/\u\1/g')}"
PROJECTS_DIR="$HOME/Projects"
PLUGIN_DIR="$HOME/Dropbox/wp-common-plugins"
LICENSE_FILE="$PLUGIN_DIR/licenses.txt"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[⚠]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }

# =============================================================================
# VALIDATE
# =============================================================================
[ -z "$PROJECT_NAME" ] && error "Usage: ./setup.sh <project-name> [site-title]"
[[ ! "$PROJECT_NAME" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]] && error "Project name must be kebab-case (e.g. bobs-bakery)"
[ -d "$PROJECTS_DIR/$PROJECT_NAME" ] && error "Directory already exists: $PROJECTS_DIR/$PROJECT_NAME"

echo ""
echo "================================================="
echo "  New TailPress Project: $PROJECT_NAME"
echo "  Site Title: $SITE_TITLE"
echo "================================================="
echo ""

# =============================================================================
# CREATE PROJECT & DDEV
# =============================================================================
mkdir -p "$PROJECTS_DIR/$PROJECT_NAME"
cd "$PROJECTS_DIR/$PROJECT_NAME"

if [ ! -d ".ddev" ]; then
  log "Configuring DDEV..."
  ddev config --project-type=wordpress --project-name="$PROJECT_NAME"
  
  # Add Vite exposed ports
  cat >> .ddev/config.yaml << DDEVEOF
web_extra_exposed_ports:
  - name: vite
    container_port: 5173
    http_port: 5172
    https_port: 5173
  - name: $PROJECT_NAME
    container_port: 3000
    http_port: 3001
    https_port: 3000
DDEVEOF
  log "DDEV configured with Vite ports"
else
  warn ".ddev already exists — skipping ddev config"
fi

# =============================================================================
# START DDEV & INSTALL WORDPRESS
# =============================================================================
log "Starting DDEV..."
ddev start

log "Downloading WordPress..."
ddev wp core download

log "Installing WordPress..."
ddev wp core install --url='$DDEV_PRIMARY_URL' --title="$SITE_TITLE" --admin_user=admin --admin_password=Green1172 --admin_email=admin@example.com

# =============================================================================
# CLONE TAILPRESS THEME
# =============================================================================
log "Cloning TailPress base theme..."
git clone git@github.com:btantlinger/tailpress-base.git wp-content/themes/tailpress
rm -rf wp-content/themes/tailpress/.git
log "TailPress theme installed"

# =============================================================================
# INSTALL THEME DEPENDENCIES
# =============================================================================
log "Installing theme dependencies..."
ddev exec -d /var/www/html/wp-content/themes/tailpress composer install
ddev exec -d /var/www/html/wp-content/themes/tailpress npm install
ddev exec -d /var/www/html/wp-content/themes/tailpress npm run build
log "Theme built successfully"

# =============================================================================
# INSTALL PLUGINS
# =============================================================================
log "Installing Yoast SEO..."
ddev wp plugin install wordpress-seo --activate

if [ -d "$PLUGIN_DIR" ]; then
  for zip in "$PLUGIN_DIR"/*.zip; do
    if [ -f "$zip" ]; then
      BASENAME=$(basename "$zip")
      log "Installing $BASENAME..."
      cp "$zip" "$PROJECTS_DIR/$PROJECT_NAME/"
      ddev wp plugin install "$BASENAME" --activate || warn "Failed to install $BASENAME"
      rm -f "$BASENAME"
    fi
  done
else
  warn "Plugin directory not found: $PLUGIN_DIR"
  warn "Create it and add ACF Pro + WP Migrate DB Pro zips"
fi

# =============================================================================
# ACTIVATE LICENSES
# =============================================================================
if [ -f "$LICENSE_FILE" ]; then
  log "Activating pro licenses..."

  # ACF Pro
  ACF_KEY=$(grep '^acf-pro=' "$LICENSE_FILE" | cut -d'=' -f2)
  if [ -n "$ACF_KEY" ]; then
    ddev wp eval "acf_pro_activate_license('$ACF_KEY', true);" 2>/dev/null && log "ACF Pro license activated" || warn "ACF Pro license activation failed"
  else
    warn "ACF Pro key not found in licenses.txt"
  fi

  # WP Migrate DB Pro
  WPMDB_KEY=$(grep '^wp-migrate-db-pro=' "$LICENSE_FILE" | cut -d'=' -f2)
  if [ -n "$WPMDB_KEY" ]; then
    ddev wp migratedb setting update license "$WPMDB_KEY" --user=1 2>/dev/null && log "WP Migrate DB Pro license activated" || warn "WP Migrate DB Pro license activation failed"
  else
    warn "WP Migrate DB Pro key not found in licenses.txt"
  fi
else
  warn "License file not found: $LICENSE_FILE"
fi

# =============================================================================
# ACTIVATE THEME & CLEAN UP
# =============================================================================
log "Activating TailPress theme..."
ddev wp theme activate tailpress
ddev wp theme delete twentytwentyfive twentytwentyfour twentytwentythree 2>/dev/null || true
ddev wp plugin delete hello akismet 2>/dev/null || true

# =============================================================================
# =============================================================================
# COPY PROJECT-LEVEL FILES FROM THEME
# =============================================================================
THEME_DIR="wp-content/themes/tailpress"

if [ -f "$THEME_DIR/project.gitignore" ]; then
  cp "$THEME_DIR/project.gitignore" .gitignore
  log "Copied .gitignore"
fi

if [ -d "$THEME_DIR/.claude" ]; then
  cp -r "$THEME_DIR/.claude" .
  log "Copied .claude/ skills"
fi

# INITIALIZE GIT
# =============================================================================
log "Initializing git repository..."
cd "$PROJECTS_DIR/$PROJECT_NAME"
git init
git add -A
git commit -m "Initial commit: TailPress project setup"

# =============================================================================
# DONE
# =============================================================================
echo ""
echo "================================================="
echo -e "${GREEN}  ✅ Project created successfully!${NC}"
echo "================================================="
echo ""
echo "  📁 Path:    ~/Projects/$PROJECT_NAME"
echo "  🌐 URL:     https://$PROJECT_NAME.ddev.site"
echo "  👤 Admin:   admin / Green1172"
echo "  🎨 Theme:   tailpress"
echo "  📦 Plugins: ACF Pro, WP Migrate DB Pro, Yoast SEO"
echo ""
echo "  Next steps:"
echo "    cd ~/Projects/$PROJECT_NAME"
echo "    Edit wp-content/themes/tailpress/resources/css/theme.css"
echo "    Add fonts to wp-content/themes/tailpress/resources/fonts/"
echo "    Run: ddev npm --prefix wp-content/themes/tailpress run dev"
echo ""
