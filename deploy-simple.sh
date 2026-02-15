#!/bin/bash
#
# Simple Multi-Site Deployment Script
# Deploys IELTS Course Manager to multiple WordPress sites sequentially
#
# Usage: ./deploy-simple.sh
#

set -e  # Exit on error

# Configuration
SITES=(
  "user@site1.example.com:/var/www/site1"
  "user@site2.example.com:/var/www/site2"
  "user@site3.example.com:/var/www/site3"
  "user@site4.example.com:/var/www/site4"
  "user@site5.example.com:/var/www/site5"
  "user@site6.example.com:/var/www/site6"
  "user@site7.example.com:/var/www/site7"
  "user@site8.example.com:/var/www/site8"
  "user@site9.example.com:/var/www/site9"
  "user@site10.example.com:/var/www/site10"
)

PLUGIN_PATH="wp-content/plugins/ielts-course-manager"
BATCH_SIZE=3
DELAY_SECONDS=30

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "========================================="
echo "IELTS Course Manager Deployment"
echo "========================================="
echo ""
echo "Total sites: ${#SITES[@]}"
echo "Batch size: $BATCH_SIZE"
echo "Delay between batches: ${DELAY_SECONDS}s"
echo ""

FAILED_SITES=()
SUCCESSFUL_SITES=()

for ((i=0; i<${#SITES[@]}; i+=BATCH_SIZE)); do
  BATCH=("${SITES[@]:i:BATCH_SIZE}")
  BATCH_NUM=$((i/BATCH_SIZE + 1))
  
  echo ""
  echo "=== Deploying Batch $BATCH_NUM ==="
  echo ""
  
  for SITE in "${BATCH[@]}"; do
    IFS=':' read -r SSH_HOST SITE_PATH <<< "$SITE"
    
    echo -n "Deploying to $SSH_HOST... "
    
    # Deploy via SSH
    if ssh "$SSH_HOST" << EOF
      set -e
      cd "$SITE_PATH/$PLUGIN_PATH"
      
      # Stash any local changes (just in case)
      git stash --quiet 2>/dev/null || true
      
      # Pull latest code
      git pull --quiet origin main
      
      # Flush rewrite rules
      cd "$SITE_PATH"
      wp rewrite flush --quiet 2>/dev/null || true
      
      # Verify plugin is active
      wp plugin is-active ielts-course-manager --quiet
      
      # Get version
      VERSION=\$(wp eval 'echo IELTS_CM_VERSION;' 2>/dev/null || echo "unknown")
      echo "version:\$VERSION"
EOF
    then
      VERSION=$(ssh "$SSH_HOST" "cd $SITE_PATH && wp eval 'echo IELTS_CM_VERSION;' 2>/dev/null" || echo "unknown")
      echo -e "${GREEN}✓ Success${NC} (v$VERSION)"
      SUCCESSFUL_SITES+=("$SSH_HOST")
    else
      echo -e "${RED}✗ FAILED${NC}"
      FAILED_SITES+=("$SSH_HOST")
    fi
  done
  
  # Wait before next batch (except for last batch)
  if [ $((i + BATCH_SIZE)) -lt ${#SITES[@]} ]; then
    echo ""
    echo -e "${YELLOW}Waiting $DELAY_SECONDS seconds before next batch...${NC}"
    sleep $DELAY_SECONDS
  fi
done

echo ""
echo "========================================="
echo "Deployment Summary"
echo "========================================="
echo ""
echo -e "${GREEN}Successful: ${#SUCCESSFUL_SITES[@]}${NC}"
for site in "${SUCCESSFUL_SITES[@]}"; do
  echo "  ✓ $site"
done

if [ ${#FAILED_SITES[@]} -gt 0 ]; then
  echo ""
  echo -e "${RED}Failed: ${#FAILED_SITES[@]}${NC}"
  for site in "${FAILED_SITES[@]}"; do
    echo "  ✗ $site"
  done
  echo ""
  echo "Please check the failed sites manually."
  exit 1
else
  echo ""
  echo -e "${GREEN}All deployments completed successfully!${NC}"
  exit 0
fi
