# HTML Refactoring Migration Guide

## Overview

This guide explains how to convert existing HTML pages to use the new component system and base layout template. The refactoring improves code maintainability, eliminates duplication, and provides consistent styling across all pages.

## Before & After Example

### Before (Old HTML)

```php
<?php
session_start();
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Page</title>
    <link rel="icon" href="resources/image/SLMC_Icon_login.jpg">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            "50": "#eff6ff",
                            // ... 50+ lines of config
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <nav class="bg-white dark:bg-gray-800">
        <!-- Navigation markup -->
    </nav>

    <main class="container mx-auto">
        <!-- Page content -->
    </main>

    <footer>
        <!-- Footer -->
    </footer>

    <button id="darkModeToggle"><!-- Dark mode toggle --></button>

    <script>
        // Dark mode logic (duplicated in every file)
        // Navigation logic
        // Other page-specific logic
    </script>
</body>
</html>
```

### After (New Component System)

```php
<?php
error_reporting(E_ALL);
session_start();

// Page configuration
$pageTitle = 'My Page';
$metaDescription = 'Page description';
$showHeader = true;
$showFooter = true;
$userRole = 'dealer';
$userName = 'John Doe';

// Start output buffering
ob_start();
?>

<!-- Page-specific content using components -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php
    $cardData = [
        'title' => 'Card Title',
        'content' => '<p>Content here</p>'
    ];
    include 'resources/templates/components/card.php';
    ?>
</div>

<?php
$content = ob_get_clean();

// Optional: Additional JS/CSS
$additionalJS = ['resources/js/my-page.js'];
$additionalCSS = ['resources/css/my-page.css'];

// Load base template - handles header, footer, dark mode, meta tags, etc.
include 'resources/templates/base.php';
?>
```

## Step-by-Step Migration Process

### 1. Convert File Type (Optional)

- Old: `.html` files
- New: `.php` files (allows template inclusion)

Rename files:

```bash
mv page/Dealer/viewOrderRecord.html page/Dealer/viewOrderRecord.php
```

### 2. Replace HTML Structure with Base Template

**Delete this:**

```html
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Page Title</title>
    <!-- Meta tags -->
    <!-- Favicon -->
    <!-- Tailwind config (50+ lines) -->
  </head>
  <body class="bg-gray-100 dark:bg-gray-900">
    <!-- Navigation (repeated code) -->
    <main>
      <!-- Your content here -->
    </main>
    <!-- Footer (repeated code) -->
    <!-- Dark mode toggle (repeated code) -->
  </body>
</html>
```

**Replace with:**

```php
<?php
// Page configuration (top of file)
$pageTitle = 'Page Title';
$metaDescription = 'Page description';
$showHeader = true;
$showFooter = true;

// Start buffering page content
ob_start();
?>

<!-- Your page content here (no HTML wrapper) -->

<?php
$content = ob_get_clean();

// Optional additional assets
$additionalJS = [];
$additionalCSS = [];

// Load base template (handles everything else)
include 'resources/templates/base.php';
?>
```

### 3. Extract and Replace Tailwind Config

**Before:**

```html
<script>
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          primary: {
            /* 50+ lines */
          },
        },
      },
    },
  };
</script>
```

**After:**

- Config is now in `resources/templates/base.php`
- No need to include it on every page
- All pages automatically use consistent theme

### 4. Replace Navigation Component

**Before:**

```html
<nav class="bg-white dark:bg-gray-800 shadow">
  <div class="container mx-auto px-4 py-4">
    <div class="flex justify-between items-center">
      <div class="flex gap-6">
        <a href="#">Home</a>
        <a href="#">Orders</a>
      </div>
      <div>
        <span>User Name</span>
        <a href="logout.html">Logout</a>
      </div>
    </div>
  </div>
</nav>
```

**After:**

```php
<?php
// In base.php - automatically included when $showHeader = true
// Customize by setting page variables:
$userRole = 'dealer';
$userName = 'John Doe';
?>
```

The header is now handled by `resources/templates/header.php` with navigation generated based on user role.

### 5. Replace Form Inputs with Components

**Before:**

```html
<div>
  <label for="email" class="block text-sm font-medium">Email</label>
  <input
    type="email"
    id="email"
    name="email"
    required
    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                  focus:border-blue-300 focus:ring focus:ring-blue-200 
                  dark:bg-gray-700 dark:border-gray-600"
  />
</div>
```

**After:**

```php
<?php
$inputData = [
    'name' => 'email',
    'type' => 'email',
    'label' => 'Email',
    'required' => true
];
include 'resources/templates/components/input.php';
?>
```

### 6. Replace Buttons with Component

**Before:**

```html
<button
  type="submit"
  class="px-4 py-2 bg-blue-600 text-white rounded-lg 
                            hover:bg-blue-700 transition focus:outline-none 
                            focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
>
  Submit
</button>
```

**After:**

```php
<?php
$buttonData = [
    'text' => 'Submit',
    'type' => 'submit',
    'variant' => 'primary'
];
include 'resources/templates/components/button.php';
?>
```

### 7. Replace Cards with Component

**Before:**

```html
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
  <h3 class="text-xl font-bold mb-2">Card Title</h3>
  <p class="text-gray-700 dark:text-gray-300">Content</p>
</div>
```

**After:**

```php
<?php
$cardData = [
    'title' => 'Card Title',
    'content' => '<p>Content</p>'
];
include 'resources/templates/components/card.php';
?>
```

### 8. Replace Tables with Component

**Before:**

```html
<table class="w-full text-left">
  <thead class="bg-gray-100 dark:bg-gray-800">
    <tr>
      <th class="px-6 py-3">Name</th>
      <th class="px-6 py-3">Email</th>
    </tr>
  </thead>
  <tbody>
    <tr class="border-b">
      <td class="px-6 py-4">John</td>
      <td class="px-6 py-4">john@example.com</td>
    </tr>
  </tbody>
</table>
```

**After:**

```php
<?php
$tableData = [
    'columns' => [
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'email', 'label' => 'Email']
    ],
    'rows' => [
        ['name' => 'John', 'email' => 'john@example.com']
    ]
];
include 'resources/templates/components/table.php';
?>
```

## Migration Checklist

- [ ] Change file extension from `.html` to `.php`
- [ ] Set up page variables at top:
  - `$pageTitle`
  - `$metaDescription`
  - `$showHeader`
  - `$showFooter`
- [ ] Extract page content into `ob_start()` / `ob_get_clean()`
- [ ] Remove HTML boilerplate (DOCTYPE, head, body tags)
- [ ] Remove duplicate Tailwind config
- [ ] Remove duplicate dark mode toggle code
- [ ] Replace navigation with `$showHeader = true`
- [ ] Replace all form inputs with `input.php` component
- [ ] Replace all buttons with `button.php` component
- [ ] Replace all cards with `card.php` component
- [ ] Replace all tables with `table.php` component
- [ ] Replace modals with `modal.php` component
- [ ] Replace alerts with `alert.php` component
- [ ] Extract inline scripts to `resources/js/page-name.js`
- [ ] Set `$additionalJS` and `$additionalCSS` if needed
- [ ] Include `base.php` at the end
- [ ] Test page rendering and styling
- [ ] Test dark mode toggle
- [ ] Test responsive design (mobile/tablet/desktop)

## Page Variable Reference

```php
<?php
// Essential variables
$pageTitle = 'Page Title';                    // Page title and <title> tag
$metaDescription = 'Page description';        // Meta description
$showHeader = true;                           // Show/hide header navigation
$showFooter = true;                           // Show/hide footer
$basePath = '/';                              // Base path for links

// Navigation customization
$userRole = 'dealer';                         // 'dealer', 'manager', 'guest'
$userName = 'John Doe';                       // Current user name

// Optional assets
$additionalCSS = [                            // Additional CSS files
    'resources/css/page-specific.css'
];
$additionalJS = [                             // Additional JS files
    'resources/js/page-specific.js'
];

// Content (populated from ob_get_clean())
$content = '<div>...</div>';

// Inline script (optional)
$inlineScript = '<script>...</script>';

// Dark mode (auto-detected, optional override)
$darkMode = false;                            // Set to force light/dark mode

// Container customization
$containerClass = 'container mx-auto px-4 py-8';  // Custom container classes
?>
```

## Common Patterns

### Form Page

```php
<?php
$pageTitle = 'Create Order';
$showHeader = true;
$showFooter = true;

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Create New Order</h1>

    <form method="POST" action="resources/php/order-api.php" class="space-y-4">
        <?php
        $inputData = ['name' => 'customer_id', 'label' => 'Customer ID', 'required' => true];
        include 'resources/templates/components/input.php';
        ?>

        <?php
        $selectData = [
            'name' => 'status',
            'label' => 'Status',
            'options' => ['pending' => 'Pending', 'approved' => 'Approved']
        ];
        include 'resources/templates/components/select.php';
        ?>

        <?php
        $buttonData = ['text' => 'Create Order', 'type' => 'submit'];
        include 'resources/templates/components/button.php';
        ?>
    </form>
</div>

<?php
$content = ob_get_clean();
include 'resources/templates/base.php';
?>
```

### Dashboard Page

```php
<?php
$pageTitle = 'Dashboard';
$showHeader = true;
$showFooter = true;

ob_start();
?>

<div class="space-y-6">
    <h1 class="text-3xl font-bold">Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php
        $cardData = [
            'title' => 'Total Orders',
            'content' => '<p class="text-3xl font-bold text-primary-600">42</p>'
        ];
        include 'resources/templates/components/card.php';
        ?>

        <!-- More cards -->
    </div>

    <!-- Table of orders -->
</div>

<?php
$content = ob_get_clean();
$additionalJS = ['resources/js/dashboard.js'];
include 'resources/templates/base.php';
?>
```

## Testing After Migration

1. **Visual Testing**

   - [ ] Desktop view (1024px+)
   - [ ] Tablet view (768px)
   - [ ] Mobile view (375px)

2. **Dark Mode Testing**

   - [ ] Toggle dark mode button
   - [ ] Check all components in dark mode
   - [ ] Verify localStorage persistence

3. **Functionality Testing**

   - [ ] Form submission works
   - [ ] Navigation links work
   - [ ] Dynamic content loads correctly
   - [ ] API calls function properly

4. **Browser Testing**
   - [ ] Chrome/Edge
   - [ ] Firefox
   - [ ] Safari (if applicable)

## Troubleshooting

### Problem: Styles not applying

**Solution:** Ensure `base.php` is included at the end, after content buffer is captured

### Problem: Navigation not showing

**Solution:** Set `$showHeader = true` in page configuration

### Problem: Dark mode not working

**Solution:** Check that dark mode toggle script is loaded from `base.php`

### Problem: Components not rendering

**Solution:** Verify component file paths and that component `.php` files exist in `resources/templates/components/`

### Problem: Variables not accessible in components

**Solution:** All page variables set before `ob_start()` are available to included components

## Performance Improvements

The new component system provides several performance benefits:

1. **Reduced Duplication**

   - Tailwind config: saved 50+ lines per file
   - Dark mode: saved 30+ lines per file
   - Navigation: saved 20+ lines per file
   - **Total: ~100 lines of duplicate code eliminated per page**

2. **Faster Page Load**

   - Smaller HTML output
   - Optimized component rendering
   - Shared asset caching

3. **Easier Maintenance**
   - Single source of truth for each component
   - Global theme changes propagate automatically
   - Bug fixes apply to all pages using that component

## Questions & Support

For questions about the component system, refer to `HTML_COMPONENTS.md` or examine component examples in existing refactored pages.
