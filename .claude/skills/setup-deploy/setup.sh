#!/bin/bash
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[⚠]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }
prompt() { echo -en "${CYAN}$1${NC}"; }

# =============================================================================
# GATHER INPUTS (interactive if not provided as args)
# =============================================================================
COMPANY_NAME="$1"
GH_REPO_NAME="$2"
SERVER_SLUG="$3"

PROJECT_DIR=$(basename "$(pwd)")

if [ -z "$COMPANY_NAME" ]; then
  echo ""
  echo "================================================="
  echo "  Setup Deploy Configuration"
  echo "================================================="
  echo ""
  
  prompt "Company/client name (e.g. Nature's Friends Landscaping): "
  read COMPANY_NAME
  [ -z "$COMPANY_NAME" ] && error "Company name is required"

  prompt "GitHub repo name [$PROJECT_DIR]: "
  read GH_REPO_NAME
  GH_REPO_NAME="${GH_REPO_NAME:-$PROJECT_DIR}"

  prompt "Server project slug [$PROJECT_DIR]: "
  read SERVER_SLUG
  SERVER_SLUG="${SERVER_SLUG:-$PROJECT_DIR}"
else
  GH_REPO_NAME="${GH_REPO_NAME:-$PROJECT_DIR}"
  SERVER_SLUG="${SERVER_SLUG:-$PROJECT_DIR}"
fi

echo ""

# =============================================================================
# GENERATE deploy-config.json
# =============================================================================
if [ -f "deploy-config.json" ]; then
  warn "deploy-config.json already exists — backing up to deploy-config.json.bak"
  cp deploy-config.json deploy-config.json.bak
fi

log "Creating deploy-config.json..."
cat > deploy-config.json << CONFIGEOF
{
  "repository": {
    "url": "git@github.com:btantlinger/${GH_REPO_NAME}.git",
    "branches": {
      "staging": "staging",
      "production": "production"
    }
  },
  "wordpress": {
    "themes": [
      {
        "name": "tailpress",
        "path": "wp-content/themes/tailpress",
        "hasComposer": true,
        "hasNpm": true,
        "buildCommand": "npm run build"
      }
    ],
    "plugins": []
  },
  "environments": {
    "staging": {
      "sitePath": "/home/admin/build/${SERVER_SLUG}/staging",
      "webRoot": "/var/www/${SERVER_SLUG}",
      "backupPath": "/home/admin/build/${SERVER_SLUG}/staging/backups",
      "wpEnv": "development",
      "buildMode": "",
      "cacheAgressive": false
    },
    "production": {
      "sitePath": "/home/admin/build/${SERVER_SLUG}/production",
      "webRoot": "/var/www/${SERVER_SLUG}",
      "backupPath": "/home/admin/build/${SERVER_SLUG}/production/backups",
      "wpEnv": "production",
      "buildMode": "--no-dev",
      "cacheAgressive": true
    }
  },
  "deployment": {
    "keepBackups": 5,
    "companyName": "${COMPANY_NAME}"
  }
}
CONFIGEOF

log "deploy-config.json created"

# =============================================================================
# COPY deploy.sh
# =============================================================================
if [ -f "deploy.sh" ]; then
  warn "deploy.sh already exists — skipping"
else
  SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
  if [ -f "$SCRIPT_DIR/../../deploy.sh.template" ]; then
    cp "$SCRIPT_DIR/../../deploy.sh.template" deploy.sh
    chmod +x deploy.sh
    log "deploy.sh copied from template"
  elif [ -f "$HOME/Projects/tailpress-base/deploy.sh.template" ]; then
    cp "$HOME/Projects/tailpress-base/deploy.sh.template" deploy.sh
    chmod +x deploy.sh
    log "deploy.sh copied from tailpress-base"
  else
    warn "No deploy.sh template found — copy it manually from an existing project"
  fi
fi

# =============================================================================
# GENERATE .github/workflows/deploy.yml
# =============================================================================
mkdir -p .github/workflows

if [ -f ".github/workflows/deploy.yml" ]; then
  warn ".github/workflows/deploy.yml already exists — backing up"
  cp .github/workflows/deploy.yml .github/workflows/deploy.yml.bak
fi

log "Creating .github/workflows/deploy.yml..."
cat > .github/workflows/deploy.yml << 'CIEOF'
name: Auto Deploy

on:
  push:
    branches: [staging, production]
  workflow_dispatch:
    inputs:
      environment:
        description: 'Environment to deploy to'
        required: true
        type: choice
        options:
          - staging
          - production
      commit_hash:
        description: 'Commit hash to deploy (leave empty for latest)'
        required: false
        type: string
      config_file:
        description: 'Config file path (optional)'
        required: false
        type: string
        default: 'deploy-config.json'

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Determine environment and server
        id: config
        run: |
          if [ "${{ github.event_name }}" == "workflow_dispatch" ]; then
            ENV="${{ github.event.inputs.environment }}"
            COMMIT="${{ github.event.inputs.commit_hash || github.sha }}"
            CONFIG_FILE="${{ github.event.inputs.config_file || 'deploy-config.json' }}"
          else
            if [ "${{ github.ref_name }}" == "staging" ]; then
              ENV="staging"
            elif [ "${{ github.ref_name }}" == "production" ]; then
              ENV="production"
            else
              echo "❌ Unsupported branch: ${{ github.ref_name }}"
              exit 1
            fi
            COMMIT="${{ github.sha }}"
            CONFIG_FILE="deploy-config.json"
          fi
          
          echo "environment=$ENV" >> $GITHUB_OUTPUT
          echo "commit=$COMMIT" >> $GITHUB_OUTPUT
          echo "config_file=$CONFIG_FILE" >> $GITHUB_OUTPUT
          
          if [ "$ENV" == "staging" ]; then
            echo "server_host=${{ secrets.STAGING_SERVER_HOST }}" >> $GITHUB_OUTPUT
            echo "server_user=${{ secrets.STAGING_SERVER_USER }}" >> $GITHUB_OUTPUT
            echo "server_key=STAGING_SERVER_SSH_KEY" >> $GITHUB_OUTPUT
            echo "server_port=${{ secrets.STAGING_SERVER_PORT || 22 }}" >> $GITHUB_OUTPUT
            echo "config_path=${{ secrets.STAGING_CONFIG_PATH }}" >> $GITHUB_OUTPUT
          else
            echo "server_host=${{ secrets.PRODUCTION_SERVER_HOST }}" >> $GITHUB_OUTPUT
            echo "server_user=${{ secrets.PRODUCTION_SERVER_USER }}" >> $GITHUB_OUTPUT
            echo "server_key=PRODUCTION_SERVER_SSH_KEY" >> $GITHUB_OUTPUT
            echo "server_port=${{ secrets.PRODUCTION_SERVER_PORT || 22 }}" >> $GITHUB_OUTPUT
            echo "config_path=${{ secrets.PRODUCTION_CONFIG_PATH }}" >> $GITHUB_OUTPUT
          fi

      - name: Production Deployment Warning
        if: steps.config.outputs.environment == 'production'
        run: |
          echo "🚨🚨🚨 PRODUCTION DEPLOYMENT WARNING 🚨🚨🚨"
          echo "========================================="
          echo "About to deploy to PRODUCTION environment!"
          echo "Commit: ${{ steps.config.outputs.commit }}"
          echo "Branch: ${{ github.ref_name }}"
          echo "========================================="

      - name: Deploy to Server
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ steps.config.outputs.server_host }}
          username: ${{ steps.config.outputs.server_user }}
          key: ${{ secrets[steps.config.outputs.server_key] }}
          port: ${{ steps.config.outputs.server_port }}
          command_timeout: 30m
          script_stop: false
          sync: true
          debug: true
          script: |
            set -e

            ENV="${{ steps.config.outputs.environment }}"
            COMMIT="${{ steps.config.outputs.commit }}"
            CONFIG_FILE="${{ steps.config.outputs.config_file }}"
            DEPLOY_DIR="${{ steps.config.outputs.config_path }}"

            echo "Starting GitHub Actions deployment..."
            echo "Environment: $ENV"
            echo "Commit: $COMMIT"
            echo "Branch: ${{ github.ref_name }}"
            echo "Server: $(hostname)"
            echo ""

            cd "$DEPLOY_DIR"

            if [ ! -f "deploy.sh" ]; then
              echo "deploy.sh not found in $DEPLOY_DIR"
              exit 1
            fi

            if [ ! -f "$CONFIG_FILE" ]; then
              echo "Config file not found: $CONFIG_FILE in $DEPLOY_DIR"
              exit 1
            fi

            chmod +x deploy.sh
            export GITHUB_ACTIONS=1

            if ./deploy.sh "$COMMIT" "$ENV" "$CONFIG_FILE" 2>&1; then
              echo ""
              echo "Deployment completed successfully!"
            else
              echo ""
              echo "Deployment failed!"
              exit 1
            fi

      - name: Deployment Success Notification
        if: success()
        run: |
          echo "🎉 SUCCESS: Deployed to ${{ steps.config.outputs.environment }}!"
          echo "📋 Commit: ${{ steps.config.outputs.commit }}"
          echo "🌐 Environment: ${{ steps.config.outputs.environment }}"

      - name: Deployment Failure Notification
        if: failure()
        run: |
          echo "💥 FAILED: Deployment to ${{ steps.config.outputs.environment }} failed!"
          echo "📋 Commit: ${{ steps.config.outputs.commit }}"
          echo "🌐 Environment: ${{ steps.config.outputs.environment }}"
          exit 1
CIEOF

log "deploy.yml created"

# =============================================================================
# DONE
# =============================================================================
echo ""
echo "================================================="
echo -e "${GREEN}  ✅ Deploy configuration created!${NC}"
echo "================================================="
echo ""
echo "  📄 deploy-config.json — server paths & build config"
echo "  🔄 .github/workflows/deploy.yml — CI/CD pipeline"
echo ""
echo "  ⚠️  Required GitHub Secrets (set in repo settings):"
echo "    STAGING_SERVER_HOST"
echo "    STAGING_SERVER_USER"
echo "    STAGING_SERVER_SSH_KEY"
echo "    STAGING_SERVER_PORT (optional, default 22)"
echo "    STAGING_CONFIG_PATH"
echo "    PRODUCTION_SERVER_HOST"
echo "    PRODUCTION_SERVER_USER"
echo "    PRODUCTION_SERVER_SSH_KEY"
echo "    PRODUCTION_SERVER_PORT (optional, default 22)"
echo "    PRODUCTION_CONFIG_PATH"
echo ""
echo "  ⚠️  Don't forget: copy deploy.sh to the server at:"
echo "    /home/admin/build/${SERVER_SLUG}/staging/"
echo "    /home/admin/build/${SERVER_SLUG}/production/"
echo ""
