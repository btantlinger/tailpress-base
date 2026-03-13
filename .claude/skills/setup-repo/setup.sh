#!/bin/bash
set -e

REPO_NAME="${1:-$(basename "$(pwd)")}"
GH_ORG="btantlinger"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[⚠]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }

# Validate
command -v gh >/dev/null 2>&1 || error "gh CLI not installed"
[ -d ".git" ] || error "Not a git repository. Run git init first."

echo ""
echo "================================================="
echo "  Setup Repo: $GH_ORG/$REPO_NAME (private)"
echo "================================================="
echo ""

# Check if repo already exists
if gh repo view "$GH_ORG/$REPO_NAME" >/dev/null 2>&1; then
  error "Repository $GH_ORG/$REPO_NAME already exists on GitHub"
fi

# Create private repo
log "Creating private repository on GitHub..."
gh repo create "$GH_ORG/$REPO_NAME" --private --source=. --remote=origin --push

log "Pushed master branch"

# Create staging branch
log "Creating staging branch..."
git checkout -b staging
git push -u origin staging

# Create production branch
log "Creating production branch..."
git checkout -b production
git push -u origin production

# Back to master
git checkout master

echo ""
echo "================================================="
echo -e "${GREEN}  ✅ Repository created!${NC}"
echo "================================================="
echo ""
echo "  📦 Repo:       https://github.com/$GH_ORG/$REPO_NAME"
echo "  🔒 Visibility: Private"
echo "  🌿 Branches:   master, staging, production"
echo ""
echo "  Next steps:"
echo "    - Set up deploy secrets in GitHub repo settings"
echo "    - Run /setup-deploy to create deploy config + CI workflow"
echo ""
