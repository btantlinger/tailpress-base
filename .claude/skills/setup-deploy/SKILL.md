# Setup Deploy

Configure deployment pipeline for the current TailPress project.

## Usage

```
/setup-deploy
```

You will need:
- The SSH alias for the target server (from ~/.ssh/config)
- The company/client name

## Steps

### 1. Gather Info

- **Repo name:** Read from `.git/config` (`git remote get-url origin`)
- **Company name:** Ask the user
- **Server SSH alias:** Ask the user (e.g. `natures-friends-server`)
- **Server slug:** Ask the user, default to project directory name. Used in paths like `/var/www/{slug}`, `/home/admin/build/{slug}/`

### 2. Generate deploy-config.json

Create `deploy-config.json` in the project root using this template:

```json
{
  "repository": {
    "url": "{repo-url-from-git}",
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
      "sitePath": "/home/admin/build/{server-slug}/staging",
      "webRoot": "/var/www/{server-slug}",
      "backupPath": "/home/admin/build/{server-slug}/staging/backups",
      "wpEnv": "development",
      "buildMode": "",
      "cacheAgressive": false
    },
    "production": {
      "sitePath": "/home/admin/build/{server-slug}/production",
      "webRoot": "/var/www/{server-slug}",
      "backupPath": "/home/admin/build/{server-slug}/production/backups",
      "wpEnv": "production",
      "buildMode": "--no-dev",
      "cacheAgressive": true
    }
  },
  "deployment": {
    "keepBackups": 5,
    "companyName": "{company-name}"
  }
}
```

**Note:** The paths above assume a standard EC2/VPS setup. If the server is cPanel or shared hosting, adjust `sitePath`, `webRoot`, and `backupPath` accordingly based on what you find when you SSH in.

### 3. Generate .github/workflows/deploy.yml

Create `.github/workflows/deploy.yml`. The workflow is the same for every project — copy it from `deploy.sh.template`'s companion workflow. The canonical version is in the natures-friends or webmoves project. Key points:

- Triggers on push to `staging` or `production` branches
- Supports `workflow_dispatch` for manual deploys
- Uses `appleboy/ssh-action@v1.0.3` to SSH into the server
- Reads environment-specific secrets (STAGING_* and PRODUCTION_*)
- Runs `deploy.sh` on the server

### 4. Copy deploy.sh to the Project

Copy `deploy.sh.template` from the tailpress-base repo (it's in the project root since it was cloned from tailpress-base) to `deploy.sh`:

```bash
cp wp-content/themes/tailpress/deploy.sh.template deploy.sh
chmod +x deploy.sh
```

### 5. SSH Into the Server & Set Up

SSH into the server using the alias the user provided.

**Figure out the server environment:**
- Is it cPanel, Plesk, or bare metal?
- Where is the web root? (`/var/www/`, `/home/user/public_html/`, etc.)
- What user should own the files?
- Is `git`, `composer`, `npm`, `node`, `jq`, and `wp-cli` installed?

**Create build directories:**
```bash
mkdir -p /home/admin/build/{server-slug}/staging/backups
mkdir -p /home/admin/build/{server-slug}/production/backups
```

**Copy deploy files to the server:**
- `deploy.sh` → both staging and production build dirs
- `deploy-config.json` → both staging and production build dirs

### 6. Generate Deploy SSH Key

On the server, generate an SSH deploy key for GitHub:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/deploy_key -N "" -C "deploy@{server-slug}"
```

- Add the **public key** as a deploy key on the GitHub repo (read-only is fine):
  ```bash
  gh repo deploy-key add ~/.ssh/deploy_key.pub --repo btantlinger/{repo-name} --title "{server-slug} deploy"
  ```
  (Or do it manually in GitHub repo settings → Deploy keys)

- Configure SSH to use this key for GitHub by adding to `~/.ssh/config` on the server:
  ```
  Host github.com
    IdentityFile ~/.ssh/deploy_key
    StrictHostKeyChecking no
  ```

### 7. Set GitHub Secrets

Use `gh secret set` to configure all required secrets on the repo:

```bash
gh secret set STAGING_SERVER_HOST --repo btantlinger/{repo-name} --body "{staging-server-ip}"
gh secret set STAGING_SERVER_USER --repo btantlinger/{repo-name} --body "admin"
gh secret set STAGING_SERVER_SSH_KEY --repo btantlinger/{repo-name} < /path/to/ssh/private/key
gh secret set STAGING_SERVER_PORT --repo btantlinger/{repo-name} --body "22"
gh secret set STAGING_CONFIG_PATH --repo btantlinger/{repo-name} --body "/home/admin/build/{server-slug}/staging"

gh secret set PRODUCTION_SERVER_HOST --repo btantlinger/{repo-name} --body "{production-server-ip}"
gh secret set PRODUCTION_SERVER_USER --repo btantlinger/{repo-name} --body "admin"
gh secret set PRODUCTION_SERVER_SSH_KEY --repo btantlinger/{repo-name} < /path/to/ssh/private/key
gh secret set PRODUCTION_SERVER_PORT --repo btantlinger/{repo-name} --body "22"
gh secret set PRODUCTION_CONFIG_PATH --repo btantlinger/{repo-name} --body "/home/admin/build/{server-slug}/production"
```

**Note:** For the SSH key secret, you need to read the **private** key that GitHub Actions will use to SSH into the server. This is typically Bob's key or a dedicated deploy key — ask the user which key to use.

### 8. Commit & Push

```bash
git add deploy-config.json deploy.sh .github/
git commit -m "Add deploy configuration and CI/CD workflow"
git push
```

### 9. Summary

Print what was done and any remaining manual steps:
- Confirm deploy-config.json, deploy.yml, and deploy.sh are committed
- Confirm GitHub secrets are set
- Confirm server build directories exist
- Confirm deploy key is set up
- Note: first deploy will happen when code is pushed to staging or production branch

## Important Notes

- Theme name in deploy-config.json is ALWAYS `tailpress`
- Branch scheme is ALWAYS master/staging/production
- deploy.sh is generic — same script for every project, config drives the differences
- If staging and production are on the same server, the secrets will have the same host/user/key values but different CONFIG_PATH values
- The deploy.yml workflow is identical across all projects — do not customize per project
