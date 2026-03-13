# Setup Deploy

Generate deploy configuration and CI/CD workflow for the current project.

## Usage

```
/setup-deploy
```

## How to Execute

Run the setup script from the project root:

```bash
.claude/skills/setup-deploy/setup.sh
```

Or with all arguments (non-interactive):

```bash
.claude/skills/setup-deploy/setup.sh "Company Name" "github-repo-name" "server-slug"
```

## What It Does

1. Prompts for company name, GitHub repo name, and server slug (or accepts as args)
2. Generates `deploy-config.json` with server paths and build config
3. Generates `.github/workflows/deploy.yml` for CI/CD
4. Lists required GitHub Secrets to configure

## Prerequisites

- Git repo already set up (run /setup-repo first)
- Server access details for staging/production environments
