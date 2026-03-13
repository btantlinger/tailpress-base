# Setup Repo

Create a private GitHub repository for the current project and configure branches.

## Usage

```
/setup-repo [--name repo-name]
```

- `--name` — Optional. Defaults to the project directory name.

## How to Execute

Run the setup script:

```bash
.claude/skills/setup-repo/setup.sh [repo-name]
```

If no repo name is provided, it uses the current directory name.

## What It Does

1. Creates a **private** repository on GitHub under `btantlinger/`
2. Sets `origin` remote to `https://github.com/btantlinger/{repo-name}.git`
3. Pushes `master` branch
4. Creates and pushes `staging` branch from `master`
5. Creates and pushes `production` branch from `master`

## Prerequisites

- `gh` CLI authenticated with access to `btantlinger` account
- Git repo already initialized in the project (the new-tailpress-project script does this)

## Important Notes

- Repo is ALWAYS private — never public
- Branch scheme: master, staging, production (required by deploy CI)
