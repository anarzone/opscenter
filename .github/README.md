# GitHub Actions CI/CD Template for Laravel + Forge

This directory contains reusable GitHub Actions workflows for Laravel projects deployed via Laravel Forge.

## 📋 Table of Contents

- [Quick Start](#quick-start)
- [Workflows Overview](#workflows-overview)
- [Required Configuration](#required-configuration)
- [Optional Configuration](#optional-configuration)
- [Customization Guide](#customization-guide)

---

## 🚀 Quick Start

### 1. Copy workflows to your new project
```bash
# Copy the entire .github directory
cp -r .github /path/to/your/new/project/
```

### 2. Configure GitHub Secrets
Go to your repository: **Settings → Secrets and variables → Actions → Secrets**

Add these required secrets:

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `FORGE_API_TOKEN` | Your Laravel Forge API token | Get from forge.laravel.com/user/profile |
| `FORGE_SERVER_ID` | Your Forge server ID | `906819` |

### 3. Configure GitHub Variables
Go to: **Settings → Secrets and variables → Actions → Variables**

Add these optional variables:

| Variable Name | Description | Default Value |
|---------------|-------------|---------------|
| `FORGE_SITE_URL` | Your production domain | Falls back to hardcoded value |

### 4. Update Dependabot Configuration
Edit `.github/dependabot.yml` and replace `anarzone` with your GitHub username.

### 5. Customize PHP/Node Versions (Optional)
If your project uses different versions, update in the workflow files:
- PHP version: Search for `php-version: 8.4`
- Node version: Search for `node-version: '20'`

---

## 📚 Workflows Overview

### 1. **CI Pipeline** (`ci.yml`)
**Runs on:** Every push and pull request to `main` or `develop` branches

**What it does:**
- ✅ Code style checks (Laravel Pint)
- ✅ Security vulnerability scanning
- ✅ Run test suite (Pest/PHPUnit)
- ✅ Build frontend assets (Vite/Mix)

**Parallel execution:** Runs 3 jobs simultaneously for faster feedback

---

### 2. **Deployment** (`deploy.yml`)
**Runs on:** After CI passes on `main` branch, or manual trigger

**What it does:**
- ✅ Waits for CI to pass (quality gate)
- ✅ Deploys to Laravel Forge
- ✅ Optional health check after deployment
- ✅ Deployment notifications

**Safety features:**
- Only deploys if tests pass
- Can be manually triggered for hotfixes
- Includes deployment status checks

---

### 3. **Pull Request Checks** (`pull-request.yml`)
**Runs on:** Pull request opened/updated

**What it does:**
- ✅ Additional PR-specific validations
- ✅ Checks for merge conflicts
- ✅ Warns about debug statements (dd, dump)
- ✅ Analyzes PR size
- ✅ Auto-comments on success

**Purpose:** Catches common mistakes before code review

---

### 4. **Dependabot** (`dependabot.yml`)
**Runs on:** Weekly schedule (Mondays at 9 AM)

**What it does:**
- ✅ Checks for Composer package updates
- ✅ Checks for npm package updates
- ✅ Checks for GitHub Actions updates (monthly)
- ✅ Creates PRs automatically

**Benefits:** Keep dependencies secure and up-to-date

---

## 🔧 Required Configuration

### Step 1: Get Your Forge API Token
1. Go to [forge.laravel.com/user/profile](https://forge.laravel.com/user/profile)
2. Click "API" section
3. Generate new token
4. Copy token to `FORGE_API_TOKEN` secret

### Step 2: Get Your Forge Server ID
```bash
# Option 1: From Forge CLI
forge servers

# Option 2: From Forge URL
# If your server URL is: forge.laravel.com/servers/906819
# Then your server ID is: 906819
```

### Step 3: Add to GitHub Secrets
```
Repository Settings
  → Secrets and variables
    → Actions
      → Secrets
        → New repository secret
```

---

## ⚙️ Optional Configuration

### Enable Health Checks
1. Add a health check endpoint to your Laravel app:
```php
// routes/api.php
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
```

2. Uncomment health check section in `deploy.yml` (lines 70-75)

### Change Deployment Branch
By default, deploys from `main`. To change:

```yaml
# In deploy.yml, change:
branches: [ main ]
# To:
branches: [ production ]
```

### Add More PHP Versions for Testing
```yaml
# In ci.yml, under tests job:
strategy:
  matrix:
    php-version: [8.3, 8.4]  # Test multiple versions
```

### Change CI Trigger Branches
```yaml
# In ci.yml, change:
branches: [ main, develop ]
# To your branch names:
branches: [ main, staging, develop ]
```

---

## 🎨 Customization Guide

### For Different Deployment Platforms

#### **Deploying to Envoyer instead of Forge:**
Replace the deployment step in `deploy.yml`:
```yaml
- name: Deploy to Envoyer
  run: curl "${{ secrets.ENVOYER_HOOK_URL }}"
```

#### **Deploying to standard server (SSH):**
```yaml
- name: Deploy via SSH
  uses: appleboy/ssh-action@v1.0.0
  with:
    host: ${{ secrets.SSH_HOST }}
    username: ${{ secrets.SSH_USER }}
    key: ${{ secrets.SSH_KEY }}
    script: |
      cd /var/www/html
      git pull origin main
      composer install --no-dev
      php artisan migrate --force
```

### For Different Testing Frameworks

#### **Using PHPUnit instead of Pest:**
In `ci.yml`, change:
```yaml
- name: Run tests
  run: vendor/bin/phpunit
```

#### **Adding code coverage:**
```yaml
- name: Run tests with coverage
  run: vendor/bin/pest --coverage --min=80
```

### For Different Asset Build Tools

#### **Using Laravel Mix instead of Vite:**
In `ci.yml`, change:
```yaml
- name: Build assets
  run: npm run production
```

#### **Using Webpack:**
```yaml
- name: Build assets
  run: npm run build:prod
```

---

## 🔐 Security Best Practices

### ✅ Do's
- ✅ Always use secrets for sensitive data (API tokens, keys)
- ✅ Use variables for non-sensitive configuration (URLs, IDs)
- ✅ Enable Dependabot to catch security vulnerabilities
- ✅ Require CI to pass before deploying
- ✅ Use environment protection rules for production

### ❌ Don'ts
- ❌ Never commit `.env` files with real credentials
- ❌ Don't hardcode API tokens in workflow files
- ❌ Don't disable security checks to "make CI faster"
- ❌ Don't skip tests before deployment

---

## 📊 Workflow Execution Flow

```
┌─────────────────────────────────────────────────────────────┐
│  Push to main branch                                        │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────────┐
│  CI Pipeline Starts (3 parallel jobs)                       │
│  ├─ Code Quality (Pint, Security Audit)                     │
│  ├─ Tests (Pest with SQLite)                                │
│  └─ Build Assets (Vite)                                     │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ▼
        ┌─────────┴──────────┐
        │  All Jobs Pass?    │
        └─────────┬──────────┘
                  │
         ┌────────┴────────┐
         │ Yes             │ No
         ▼                 ▼
┌──────────────────┐  ┌──────────────────┐
│ Deploy Workflow  │  │ Stop - Fix Code  │
│ Triggers         │  │ Deployment       │
└────────┬─────────┘  │ Blocked          │
         │            └──────────────────┘
         ▼
┌──────────────────────────────────────────┐
│ Deploy to Forge                          │
│ ├─ Switch to correct server              │
│ ├─ Trigger deployment                    │
│ ├─ Wait for completion                   │
│ └─ Optional: Health check                │
└──────────────────────────────────────────┘
```

---

## 🆘 Troubleshooting

### CI Fails: "Pint not found"
**Solution:** Run `composer install` locally and commit `composer.lock`

### Deployment Fails: "Server not found"
**Solution:** Check `FORGE_SERVER_ID` secret is correct

### Health Check Fails
**Solution:**
1. Ensure health endpoint exists: `/api/health`
2. Check if site is actually deployed
3. Verify URL in `FORGE_SITE_URL` variable

### Dependabot PRs Not Appearing
**Solution:**
1. Check Dependabot settings in GitHub
2. Verify `dependabot.yml` syntax
3. Ensure you updated the GitHub username

### Tests Pass Locally But Fail in CI
**Solution:**
1. Check if you're using wrong database driver (use SQLite for CI)
2. Verify all environment variables are set in workflow
3. Check for missing PHP extensions

---

## 📖 Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Laravel Forge API Docs](https://forge.laravel.com/api-documentation)
- [Laravel Pint Documentation](https://laravel.com/docs/pint)
- [Pest Testing Framework](https://pestphp.com)
- [Dependabot Documentation](https://docs.github.com/en/code-security/dependabot)

---

## 📝 License

These workflows are provided as-is for use in your projects. Feel free to modify and distribute.

---

## 🤝 Contributing

Found an improvement? Feel free to:
1. Fork this template
2. Make your changes
3. Share with the community

---

**Happy Deploying! 🚀**
