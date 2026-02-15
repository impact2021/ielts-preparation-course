# Deployment Alternatives for Multi-Site WordPress Setups

## Important Clarification About the Current Fix

### What the Fix Actually Does ⚠️

You raised an excellent point: **Site B doesn't actually "know" that Site A should go first.**

The file-based locking in our fix **only works on a single server**. Here's the reality:

```
Site A (server1.example.com)
  └─ Lock file: /var/www/site-a/wp-content/ielts-cm-activation.lock

Site B (server2.example.com)  
  └─ Lock file: /var/www/site-b/wp-content/ielts-cm-activation.lock
```

**These are different files on different servers - they don't communicate!**

### What the Fix Actually Accomplishes

The fix helps **each individual site** handle its own deployment better:
- ✅ Prevents multiple processes on the **same server** from conflicting
- ✅ Defers expensive operations to reduce load
- ✅ Adds retry logic for resilience
- ❌ **Does NOT coordinate between different sites**
- ❌ **Does NOT prevent all 10 sites from deploying simultaneously**

### Why Sites Still Work Better

Even though sites don't coordinate, the fix still helps because:
1. **Deferred Operations**: Moving `flush_rewrite_rules()` to admin_init reduces peak load
2. **Better Error Handling**: Sites recover gracefully from conflicts
3. **Reduced Resource Usage**: Less CPU intensive operations during deployment
4. **Individual Resilience**: Each site handles its own load better

But you're right - this isn't a complete solution for **coordinated** multi-site deployments.

---

## Why WP Pusher Isn't Ideal for 10+ Sites

### Problems with WP Pusher

1. **No Built-in Coordination**
   - All sites receive webhook simultaneously
   - No way to stagger deployments
   - No central orchestration

2. **Limited Control**
   - Can't easily control deployment order
   - No rollback mechanism
   - Limited visibility into deployment status

3. **Scalability Issues**
   - As you add more sites, problem gets worse
   - No batching or queueing
   - All-or-nothing approach

4. **Debugging Challenges**
   - Hard to know which site failed
   - Limited logging
   - No centralized monitoring

### When WP Pusher IS Appropriate

WP Pusher works well for:
- ✅ 1-3 sites
- ✅ Low-traffic sites
- ✅ Sites that update infrequently
- ✅ Simple deployment needs

For 10+ sites? **You need a better solution.**

---

## Better Alternatives (Ranked by Recommendation)

### 🥇 Option 1: GitHub Actions with Orchestration (RECOMMENDED)

**Why This Is Best:**
- ✅ Complete control over deployment order
- ✅ Built-in coordination and sequencing
- ✅ Free for public repos (generous limits for private)
- ✅ Built-in logging and monitoring
- ✅ Easy rollback
- ✅ No additional server required

**How It Works:**

```yaml
# .github/workflows/deploy-to-production.yml
name: Orchestrated Multi-Site Deployment

on:
  push:
    branches: [main]

jobs:
  deploy-batch-1:
    name: Deploy to Sites 1-3
    runs-on: ubuntu-latest
    strategy:
      matrix:
        site: [site1, site2, site3]
      max-parallel: 1  # Deploy one at a time
    steps:
      - name: Deploy to ${{ matrix.site }}
        uses: appleboy/ssh-action@v0.1.7
        with:
          host: ${{ secrets[format('{0}_HOST', matrix.site)] }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/${{ matrix.site }}/wp-content/plugins/ielts-course-manager
            git pull origin main
            # Optional: clear cache, run migrations, etc.
      
      - name: Verify deployment
        run: |
          curl -f https://${{ matrix.site }}.example.com/wp-json/ielts-cm/v1/health || exit 1
      
      - name: Wait before next deployment
        run: sleep 30

  deploy-batch-2:
    name: Deploy to Sites 4-6
    runs-on: ubuntu-latest
    needs: deploy-batch-1  # Wait for batch 1 to complete
    strategy:
      matrix:
        site: [site4, site5, site6]
      max-parallel: 1
    steps:
      # Same as batch 1

  deploy-batch-3:
    name: Deploy to Sites 7-10
    runs-on: ubuntu-latest
    needs: deploy-batch-2  # Wait for batch 2 to complete
    strategy:
      matrix:
        site: [site7, site8, site9, site10]
      max-parallel: 1
    steps:
      # Same as batch 1

  notify-completion:
    name: Notify on Completion
    runs-on: ubuntu-latest
    needs: [deploy-batch-1, deploy-batch-2, deploy-batch-3]
    if: always()
    steps:
      - name: Send notification
        run: |
          # Send email, Slack message, etc.
          echo "All deployments complete!"
```

**Setup Steps:**

1. **Create GitHub Secrets:**
   ```
   Settings → Secrets and variables → Actions → New repository secret
   
   Add:
   - SSH_USER
   - SSH_KEY
   - SITE1_HOST, SITE2_HOST, etc.
   ```

2. **Set up SSH keys on each server:**
   ```bash
   # On your local machine
   ssh-keygen -t ed25519 -C "github-actions"
   
   # Add public key to each server
   ssh-copy-id user@site1.example.com
   ssh-copy-id user@site2.example.com
   # etc.
   ```

3. **Test the workflow:**
   ```bash
   # Push a change to main branch
   git push origin main
   
   # Watch in GitHub Actions tab
   ```

**Cost:** FREE for public repos, $0.008/minute for private repos (very cheap)

---

### 🥈 Option 2: Deployer PHP Tool

**Why It's Good:**
- ✅ Built specifically for PHP deployments
- ✅ Parallel or sequential deployments
- ✅ Atomic deployments (symlinks)
- ✅ Automatic rollback on failure
- ✅ Task hooks for custom logic

**Installation:**

```bash
composer require deployer/deployer --dev
```

**Configuration:**

```php
// deploy.php
<?php
namespace Deployer;

require 'recipe/wordpress.php';

// Configure hosts
host('site1.example.com')
    ->set('deploy_path', '/var/www/site1')
    ->set('branch', 'main');

host('site2.example.com')
    ->set('deploy_path', '/var/www/site2')
    ->set('branch', 'main');

// Add all 10 hosts...

// Deployment strategy
set('default_stage', 'production');
set('keep_releases', 3);

// Sequential deployment with delays
task('deploy:orchestrated', function() {
    $hosts = Deployer::get()->hosts;
    foreach ($hosts as $host) {
        invoke('deploy', $host);
        writeln("Waiting 30 seconds before next deployment...");
        sleep(30);
    }
})->desc('Deploy to all sites sequentially');

// After deploy tasks
after('deploy:symlink', 'deploy:flush_rewrite_rules');

task('deploy:flush_rewrite_rules', function() {
    run('cd {{deploy_path}}/current && wp rewrite flush');
});

// Rollback on failure
fail('deploy', 'deploy:unlock');
```

**Usage:**

```bash
# Deploy to all sites
dep deploy:orchestrated

# Deploy to specific site
dep deploy site1.example.com

# Rollback
dep rollback site1.example.com
```

**Cost:** FREE (open source)

---

### 🥉 Option 3: Ansible Automation

**Why It's Good:**
- ✅ Infrastructure as code
- ✅ Idempotent deployments
- ✅ Built-in coordination
- ✅ Powerful templating
- ✅ Industry standard

**Installation:**

```bash
pip install ansible
```

**Configuration:**

```yaml
# inventory.yml
all:
  children:
    wordpress_sites:
      hosts:
        site1:
          ansible_host: site1.example.com
        site2:
          ansible_host: site2.example.com
        # ... all 10 sites
      vars:
        ansible_user: deployer
        ansible_ssh_private_key_file: ~/.ssh/id_rsa
        plugin_path: /var/www/html/wp-content/plugins/ielts-course-manager
```

```yaml
# deploy.yml
---
- name: Deploy IELTS Course Manager Plugin
  hosts: wordpress_sites
  serial: 3  # Deploy to 3 sites at a time
  tasks:
    - name: Pull latest code
      git:
        repo: https://github.com/impact2021/ielts-preparation-course.git
        dest: "{{ plugin_path }}"
        version: main
        force: yes
      register: git_result
      
    - name: Flush rewrite rules if code changed
      command: wp rewrite flush
      args:
        chdir: /var/www/html
      when: git_result.changed
      
    - name: Verify plugin active
      command: wp plugin list --field=name --status=active
      args:
        chdir: /var/www/html
      register: active_plugins
      failed_when: "'ielts-course-manager' not in active_plugins.stdout"
    
    - name: Wait between batches
      pause:
        seconds: 30
      when: inventory_hostname != groups['wordpress_sites'][-1]
```

**Usage:**

```bash
# Deploy to all sites
ansible-playbook -i inventory.yml deploy.yml

# Deploy to specific sites
ansible-playbook -i inventory.yml deploy.yml --limit site1,site2

# Dry run
ansible-playbook -i inventory.yml deploy.yml --check
```

**Cost:** FREE (open source)

---

### Option 4: WP CLI + Custom Script

**Simple and Effective:**

```bash
#!/bin/bash
# deploy-all-sites.sh

SITES=(
  "user@site1.example.com:/var/www/site1"
  "user@site2.example.com:/var/www/site2"
  "user@site3.example.com:/var/www/site3"
  # ... all 10 sites
)

PLUGIN_PATH="wp-content/plugins/ielts-course-manager"
BATCH_SIZE=3
DELAY_SECONDS=30

echo "Starting orchestrated deployment to ${#SITES[@]} sites..."

for ((i=0; i<${#SITES[@]}; i+=BATCH_SIZE)); do
  BATCH=("${SITES[@]:i:BATCH_SIZE}")
  
  echo ""
  echo "=== Deploying Batch $((i/BATCH_SIZE + 1)) ==="
  
  for SITE in "${BATCH[@]}"; do
    IFS=':' read -r SSH_HOST SITE_PATH <<< "$SITE"
    
    echo "Deploying to $SSH_HOST..."
    
    ssh "$SSH_HOST" << EOF
      cd "$SITE_PATH/$PLUGIN_PATH"
      
      # Pull latest code
      git pull origin main
      
      # Flush rewrite rules
      cd "$SITE_PATH"
      wp rewrite flush
      
      # Verify plugin is active
      wp plugin is-active ielts-course-manager || exit 1
      
      echo "✓ Deployment successful for $SSH_HOST"
EOF
    
    if [ $? -ne 0 ]; then
      echo "✗ Deployment FAILED for $SSH_HOST"
      exit 1
    fi
  done
  
  # Wait before next batch (except for last batch)
  if [ $((i + BATCH_SIZE)) -lt ${#SITES[@]} ]; then
    echo "Waiting $DELAY_SECONDS seconds before next batch..."
    sleep $DELAY_SECONDS
  fi
done

echo ""
echo "=== All deployments completed successfully! ==="
```

**Usage:**

```bash
chmod +x deploy-all-sites.sh
./deploy-all-sites.sh
```

**Cost:** FREE

---

### Option 5: Managed Deployment Services

**Commercial Solutions:**

1. **Buddy.works**
   - Visual pipeline builder
   - WordPress-specific integrations
   - Cost: $35/month for 5 projects

2. **DeployBot**
   - Simple deployment automation
   - Good for multiple sites
   - Cost: $15/month for 10 servers

3. **Deploy** (by ServerPilot)
   - WordPress-focused
   - Built-in server management
   - Cost: $10/server/month

4. **SpinupWP**
   - Managed WordPress hosting
   - Built-in Git deployment
   - Cost: $12/month + server costs

---

## Comparison Table

| Solution | Setup Time | Control | Cost | Coordination | Best For |
|----------|-----------|---------|------|--------------|----------|
| **GitHub Actions** | 2-4 hours | ⭐⭐⭐⭐⭐ | FREE/$cheap | ⭐⭐⭐⭐⭐ | Most users |
| **Deployer** | 3-5 hours | ⭐⭐⭐⭐ | FREE | ⭐⭐⭐⭐ | PHP developers |
| **Ansible** | 4-6 hours | ⭐⭐⭐⭐⭐ | FREE | ⭐⭐⭐⭐⭐ | DevOps teams |
| **WP CLI Script** | 1-2 hours | ⭐⭐⭐ | FREE | ⭐⭐⭐ | Simple needs |
| **Managed Services** | 1 hour | ⭐⭐⭐ | $$$ | ⭐⭐⭐⭐ | Non-technical |
| **WP Pusher** | 30 min | ⭐⭐ | $$ | ⭐ | 1-3 sites only |

---

## Recommended Approach for Your 10 Sites

### Phase 1: Immediate Fix (This Week)

**Use the simple bash script** while you set up something better:

```bash
# Quick deployment script
for site in site{1..10}.example.com; do
  echo "Deploying to $site..."
  ssh user@$site "cd /var/www/html/wp-content/plugins/ielts-course-manager && git pull"
  sleep 30  # Wait 30 seconds between sites
done
```

**Why:** Gets you deploying safely TODAY while you plan the long-term solution.

### Phase 2: Long-term Solution (Next 2 Weeks)

**Set up GitHub Actions** (Option 1):

1. Week 1: Set up SSH keys and test on 2-3 sites
2. Week 2: Create GitHub Actions workflow for all 10 sites
3. Test thoroughly before relying on it

**Why:** Best balance of control, cost, and maintainability.

### Phase 3: Optimization (Ongoing)

Add to your GitHub Actions workflow:
- Health checks after each deployment
- Slack/email notifications
- Automatic rollback on failure
- Deployment metrics and logging

---

## Migration Plan from WP Pusher

### Step 1: Document Current Setup
```bash
# For each site, note:
# - Server hostname
# - WordPress path
# - SSH credentials
# - Current WP Pusher webhook URL
```

### Step 2: Set Up New Deployment Method
- Choose from options above
- Test on 1-2 sites first
- Verify it works before proceeding

### Step 3: Disable WP Pusher
```bash
# Remove webhooks from GitHub
# Settings → Webhooks → Delete

# Or keep WP Pusher as backup
# Just don't use it for production deployments
```

### Step 4: Monitor First Few Deployments
- Check logs
- Verify all sites updated
- Confirm no conflicts

---

## Health Check Endpoint (Recommended Addition)

Add this to your plugin to verify deployments:

```php
// In includes/class-ielts-course-manager.php

public function register_health_check() {
    register_rest_route('ielts-cm/v1', '/health', array(
        'methods' => 'GET',
        'callback' => array($this, 'health_check'),
        'permission_callback' => '__return_true'
    ));
}

public function health_check($request) {
    return rest_ensure_response(array(
        'status' => 'ok',
        'version' => IELTS_CM_VERSION,
        'timestamp' => current_time('mysql'),
        'site_url' => get_site_url()
    ));
}
```

Then your deployment script can verify:

```bash
# Check if deployment succeeded
curl https://site1.example.com/wp-json/ielts-cm/v1/health | jq .version
# Should show: "15.52"
```

---

## Summary

### Your Question: "Is WP Pusher the best way?"

**Answer: No, not for 10+ sites.**

### What You Should Do

1. **Immediately**: Use a simple bash script with delays
2. **Soon** (2 weeks): Migrate to GitHub Actions
3. **Later**: Add health checks, monitoring, and rollback capabilities

### Why Current Fix Still Helps

Even though it doesn't coordinate between sites, it:
- Makes each site more resilient
- Reduces resource usage
- Prevents site-level conflicts

But you're absolutely right that you need **orchestration** at the deployment level, not just resilience at the code level.

---

## Questions?

- **"Which option is easiest?"** → Bash script (Option 4)
- **"Which is most reliable?"** → GitHub Actions (Option 1) or Ansible (Option 3)
- **"Which is cheapest?"** → All except managed services are FREE
- **"Which should I choose?"** → GitHub Actions for most cases

The current fix makes each site better at handling deployments, but you need a deployment tool that coordinates across sites. GitHub Actions is your best bet.
