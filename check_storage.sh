#!/bin/bash

echo "========================================"
echo "Storage Setup Diagnostic Script"
echo "========================================"
echo ""

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in a Laravel project directory"
    echo "Please run this script from your Laravel project root"
    exit 1
fi

echo "✅ Laravel project detected"
echo ""

# Check storage directories
echo "📁 Checking directory structure..."
echo ""

if [ -d "storage/app/public" ]; then
    echo "✅ storage/app/public exists"
else
    echo "❌ storage/app/public does NOT exist"
    echo "   Creating it..."
    mkdir -p storage/app/public
fi

if [ -d "storage/app/public/slides" ]; then
    echo "✅ storage/app/public/slides exists"
else
    echo "⚠️  storage/app/public/slides does NOT exist"
    echo "   Creating it..."
    mkdir -p storage/app/public/slides
fi

if [ -d "public/storage" ]; then
    if [ -L "public/storage" ]; then
        echo "✅ public/storage symlink exists"
        echo "   Points to: $(readlink public/storage)"
        
        # Check if it points to the right place
        if [ "$(readlink public/storage)" = "../storage/app/public" ]; then
            echo "   ✅ Symlink target is CORRECT"
        else
            echo "   ⚠️  Symlink target might be wrong"
            echo "   Expected: ../storage/app/public"
        fi
    else
        echo "⚠️  public/storage exists but is NOT a symlink"
        echo "   This could cause issues"
    fi
else
    echo "❌ public/storage does NOT exist"
    echo "   Running php artisan storage:link..."
    php artisan storage:link
fi

echo ""
echo "📝 Checking file permissions..."
echo ""

# Check permissions
STORAGE_PERMS=$(stat -c %a storage/app/public 2>/dev/null || stat -f %A storage/app/public 2>/dev/null)
echo "storage/app/public permissions: $STORAGE_PERMS"

if [ -d "storage/app/public/slides" ]; then
    SLIDES_PERMS=$(stat -c %a storage/app/public/slides 2>/dev/null || stat -f %A storage/app/public/slides 2>/dev/null)
    echo "storage/app/public/slides permissions: $SLIDES_PERMS"
    
    # Check if writable
    if [ -w "storage/app/public/slides" ]; then
        echo "✅ slides directory is WRITABLE"
    else
        echo "❌ slides directory is NOT writable"
        echo "   Run: chmod -R 775 storage"
    fi
fi

echo ""
echo "📄 Checking for uploaded files..."
echo ""

if [ -d "storage/app/public/slides" ]; then
    FILE_COUNT=$(find storage/app/public/slides -type f | wc -l)
    echo "Files in slides directory: $FILE_COUNT"
    
    if [ $FILE_COUNT -gt 0 ]; then
        echo ""
        echo "Files found:"
        ls -lh storage/app/public/slides/ | grep -v ^d
    fi
fi

echo ""
echo "🔗 Testing symlink access..."
echo ""

# Create a test file
TEST_FILE="storage/app/public/test_$(date +%s).txt"
echo "test" > "$TEST_FILE"

if [ -f "$TEST_FILE" ]; then
    echo "✅ Created test file: $TEST_FILE"
    
    # Check if accessible via symlink
    PUBLIC_TEST="public/storage/$(basename $TEST_FILE)"
    if [ -f "$PUBLIC_TEST" ]; then
        echo "✅ Test file is accessible via public/storage/"
        echo "   Storage link is working correctly!"
    else
        echo "❌ Test file is NOT accessible via public/storage/"
        echo "   Storage link is broken or incorrect"
    fi
    
    # Clean up
    rm "$TEST_FILE"
else
    echo "❌ Could not create test file (permission issue?)"
fi

echo ""
echo "🌐 URL Configuration..."
echo ""

if [ -f ".env" ]; then
    APP_URL=$(grep "^APP_URL=" .env | cut -d '=' -f2)
    echo "APP_URL in .env: $APP_URL"
    
    if [ -z "$APP_URL" ]; then
        echo "⚠️  APP_URL is not set in .env"
    fi
else
    echo "⚠️  .env file not found"
fi

echo ""
echo "========================================"
echo "Summary"
echo "========================================"
echo ""

ISSUES=0

if [ ! -L "public/storage" ]; then
    echo "❌ Issue: public/storage symlink missing"
    ISSUES=$((ISSUES + 1))
fi

if [ ! -d "storage/app/public/slides" ]; then
    echo "❌ Issue: slides directory missing"
    ISSUES=$((ISSUES + 1))
fi

if [ ! -w "storage/app/public/slides" ]; then
    echo "❌ Issue: slides directory not writable"
    ISSUES=$((ISSUES + 1))
fi

if [ $ISSUES -eq 0 ]; then
    echo "✅ All checks passed!"
    echo ""
    echo "Next steps:"
    echo "1. Upload images through the web interface"
    echo "2. Check the Debug Info section on the results page"
    echo "3. Verify images appear in browser DevTools Network tab"
else
    echo "⚠️  Found $ISSUES issue(s)"
    echo ""
    echo "Suggested fixes:"
    echo "1. Run: php artisan storage:link"
    echo "2. Run: chmod -R 775 storage"
    echo "3. Run: chmod -R 775 public/storage"
fi

echo ""