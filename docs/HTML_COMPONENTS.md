# HTML Component Documentation

## Overview

This document covers the reusable HTML components created as part of the frontend modernization. All components use Tailwind CSS for styling and support dark mode.

## Base Layout Template

**File:** `resources/templates/base.php`

The base layout provides the foundation for all pages, including:

- Responsive HTML structure
- Meta tags and SEO
- Tailwind CSS with custom theme
- Dark mode toggle
- Header and footer includes
- Script loading

### Usage Example

```php
<?php
// Set page variables
$pageTitle = "Dashboard";
$metaDescription = "User dashboard page";
$showHeader = true;
$showFooter = true;
$userRole = 'dealer';
$userName = 'John Dealer';

// Optional: Additional CSS files
$additionalCSS = [
    'resources/css/dashboard.css'
];

// Optional: Additional JS files
$additionalJS = [
    'resources/js/dashboard.js'
];

// Page content
ob_start();
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Your page content here -->
</div>

<?php
$content = ob_get_clean();

// Load base template
include 'resources/templates/base.php';
?>
```

## Available Components

### 1. Button Component

**File:** `resources/templates/components/button.php`

Displays a styled button or link button with multiple variants.

#### Parameters

```php
$buttonData = [
    'text' => 'Click Me',              // Button text (required)
    'href' => '/page',                 // Optional: Makes it a link
    'type' => 'submit',                // 'submit', 'button', 'reset' (ignored if href set)
    'variant' => 'primary',            // 'primary', 'secondary', 'danger', 'success', 'warning'
    'size' => 'md',                    // 'sm', 'md', 'lg'
    'disabled' => false,               // Disable button
    'class' => 'w-full',               // Additional Tailwind classes
    'attributes' => [
        'data-action' => 'submit',     // Custom HTML attributes
        'onclick' => 'handleClick()'   // JavaScript handlers
    ]
];

include 'resources/templates/components/button.php';
?>
```

#### Examples

```php
<!-- Primary Button -->
<?php $buttonData = ['text' => 'Submit', 'type' => 'submit', 'variant' => 'primary'];
include 'resources/templates/components/button.php'; ?>

<!-- Danger Link Button -->
<?php $buttonData = ['text' => 'Delete', 'href' => '/delete', 'variant' => 'danger'];
include 'resources/templates/components/button.php'; ?>

<!-- Disabled Button -->
<?php $buttonData = ['text' => 'Save', 'disabled' => true];
include 'resources/templates/components/button.php'; ?>
```

### 2. Card Component

**File:** `resources/templates/components/card.php`

Creates a flexible card container with optional header image, title, and footer.

#### Parameters

```php
$cardData = [
    'title' => 'Card Title',           // Card heading
    'subtitle' => 'Optional subtitle', // Secondary heading
    'content' => '<p>Content here</p>',// Main content (can include HTML)
    'footer' => '<p>Footer content</p>',// Optional footer section
    'image' => '/path/to/image.jpg',   // Optional header image
    'hoverable' => false,              // Add hover animation
    'class' => 'additional-classes'    // Extra Tailwind classes
];

include 'resources/templates/components/card.php';
?>
```

#### Examples

```php
<!-- Product Card with Image -->
<?php
$cardData = [
    'title' => 'Spare Part XYZ',
    'subtitle' => 'Category A',
    'image' => 'resources/image/spare/xyz.jpg',
    'content' => '<p>Price: $29.99</p>',
    'footer' => '<button onclick="addToCart()">Add to Cart</button>',
    'hoverable' => true
];
include 'resources/templates/components/card.php';
?>

<!-- Simple Card -->
<?php
$cardData = [
    'title' => 'Overview',
    'content' => '<p>Total Orders: 42</p>'
];
include 'resources/templates/components/card.php';
?>
```

### 3. Input Component

**File:** `resources/templates/components/input.php`

Renders a form input field with label, error messaging, and hints.

#### Parameters

```php
$inputData = [
    'name' => 'email',                 // Input name (required)
    'type' => 'email',                 // text, email, password, number, etc.
    'label' => 'Email Address',        // Input label
    'placeholder' => 'user@example.com',
    'value' => '',                     // Current value
    'required' => true,                // Mark as required
    'disabled' => false,               // Disable input
    'error' => 'Email is required',    // Show error state
    'hint' => 'We won\'t share your email',
    'class' => 'additional-classes',
    'attributes' => [
        'data-validate' => 'email'
    ]
];

include 'resources/templates/components/input.php';
?>
```

#### Examples

```php
<!-- Required Email Input with Error -->
<?php
$inputData = [
    'name' => 'email',
    'type' => 'email',
    'label' => 'Email Address',
    'required' => true,
    'error' => 'Please enter a valid email'
];
include 'resources/templates/components/input.php';
?>

<!-- Password with Hint -->
<?php
$inputData = [
    'name' => 'password',
    'type' => 'password',
    'label' => 'Password',
    'hint' => 'Minimum 8 characters',
    'required' => true
];
include 'resources/templates/components/input.php';
?>
```

### 4. Select Component

**File:** `resources/templates/components/select.php`

Creates a dropdown select input with optgroups support.

#### Parameters

```php
$selectData = [
    'name' => 'category',              // Select name (required)
    'label' => 'Select Category',
    'value' => 'selected-value',       // Current selection
    'required' => true,
    'disabled' => false,
    'error' => '',                     // Show error state
    'placeholder' => 'Choose...',
    'options' => [
        'value1' => 'Display Text 1',  // Simple options
        'Electronics' => [             // Optgroup
            'cpu' => 'CPU',
            'ram' => 'RAM'
        ]
    ],
    'class' => 'additional-classes'
];

include 'resources/templates/components/select.php';
?>
```

#### Examples

```php
<!-- Category Select with Optgroups -->
<?php
$selectData = [
    'name' => 'part_category',
    'label' => 'Part Category',
    'placeholder' => 'Choose category',
    'required' => true,
    'options' => [
        'Engine' => [
            'e001' => 'Oil Filter',
            'e002' => 'Air Filter'
        ],
        'Cooling' => [
            'c001' => 'Radiator',
            'c002' => 'Water Pump'
        ]
    ]
];
include 'resources/templates/components/select.php';
?>
```

### 5. Alert Component

**File:** `resources/templates/components/alert.php`

Displays alert/notification messages with different severity types.

#### Parameters

```php
$alertData = [
    'type' => 'success',               // success, error, warning, info
    'title' => 'Success!',             // Optional heading
    'message' => 'Operation completed',// Alert message (required)
    'dismissible' => true,             // Show close button
    'class' => 'additional-classes'
];

include 'resources/templates/components/alert.php';
?>
```

#### Examples

```php
<!-- Success Alert -->
<?php
$alertData = [
    'type' => 'success',
    'title' => 'Success!',
    'message' => 'Your order has been placed successfully'
];
include 'resources/templates/components/alert.php';
?>

<!-- Error Alert (not dismissible) -->
<?php
$alertData = [
    'type' => 'error',
    'message' => 'Failed to process payment',
    'dismissible' => false
];
include 'resources/templates/components/alert.php';
?>
```

### 6. Modal Component

**File:** `resources/templates/components/modal.php`

Creates a modal dialog with header, content, and footer buttons.

#### Parameters

```php
$modalData = [
    'id' => 'confirm-modal',           // Unique ID (required)
    'title' => 'Confirm Action',       // Modal title
    'content' => '<p>Are you sure?</p>',// Modal content
    'size' => 'md',                    // sm, md, lg, xl
    'showCloseButton' => true,
    'footer' => [
        [
            'text' => 'Cancel',
            'variant' => 'secondary',
            'dismissModal' => true     // Close modal on click
        ],
        [
            'text' => 'Confirm',
            'variant' => 'danger',
            'onclick' => 'handleConfirm()'  // Custom action
        ]
    ]
];

include 'resources/templates/components/modal.php';
?>

<!-- Open with JavaScript -->
<script>
    openModal('confirm-modal');        // Opens modal
    closeModal('confirm-modal');       // Closes modal
</script>
```

#### Examples

```php
<!-- Delete Confirmation Modal -->
<?php
$modalData = [
    'id' => 'delete-modal',
    'title' => 'Delete Item',
    'content' => '<p>This action cannot be undone.</p>',
    'size' => 'sm',
    'footer' => [
        ['text' => 'Cancel', 'variant' => 'secondary', 'dismissModal' => true],
        ['text' => 'Delete', 'variant' => 'danger', 'onclick' => 'deleteItem()']
    ]
];
include 'resources/templates/components/modal.php';
?>
```

### 7. Table Component

**File:** `resources/templates/components/table.php`

Displays tabular data with sorting, pagination, and formatting options.

#### Parameters

```php
$tableData = [
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'status', 'label' => 'Status', 'formatter' => 'statusBadge'],
        ['key' => 'price', 'label' => 'Price', 'formatter' => 'currency']
    ],
    'rows' => [
        ['id' => 1, 'name' => 'Part A', 'status' => 'active', 'price' => 29.99],
        ['id' => 2, 'name' => 'Part B', 'status' => 'inactive', 'price' => 49.99]
    ],
    'striped' => true,                 // Alternate row colors
    'hoverable' => true,               // Highlight on hover
    'sortable' => false,               // Allow column sorting
    'paginate' => false,               // Add pagination
    'rowsPerPage' => 10,
    'class' => 'additional-classes'
];

include 'resources/templates/components/table.php';
?>
```

#### Formatters

- `statusBadge`: Displays status as colored badge
- `currency`: Formats as currency (USD)
- `date`: Formats as local date
- Custom function: Pass callable function

#### Examples

```php
<!-- Orders Table -->
<?php
$tableData = [
    'columns' => [
        ['key' => 'id', 'label' => 'Order ID', 'width' => '100px'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'total', 'label' => 'Total', 'formatter' => 'currency'],
        ['key' => 'status', 'label' => 'Status', 'formatter' => 'statusBadge'],
        ['key' => 'date', 'label' => 'Date', 'formatter' => 'date']
    ],
    'rows' => $orders,  // From database
    'striped' => true,
    'hoverable' => true,
    'paginate' => true,
    'rowsPerPage' => 15
];
include 'resources/templates/components/table.php';
?>
```

## Dark Mode Support

All components automatically support dark mode. The dark mode toggle button is included in the base layout and saves preference to localStorage.

### Dark Mode Classes

Components use Tailwind's `dark:` prefix:

```html
<!-- Light mode: bg-white, Dark mode: dark:bg-gray-800 -->
<div class="bg-white dark:bg-gray-800">
  <!-- Content -->
</div>
```

## Theme Customization

The base layout includes extended Tailwind colors:

- **Primary**: Blue shades (50-950)
- **Success**: Green shades
- **Danger**: Red shades
- **Warning**: Yellow shades

These can be used with any component:

```html
class="bg-primary-600 text-success-600 border-danger-500"
```

## Best Practices

1. **Validation**: Always validate on both client and server side
2. **Accessibility**: Use proper `label` associations and ARIA attributes
3. **Performance**: Load JS/CSS only when needed using `$additionalCSS` and `$additionalJS`
4. **Security**: Always escape output with `htmlspecialchars()`
5. **Responsiveness**: Use Tailwind's responsive prefixes (md:, lg:, etc.)

## Component Library Updates

To add new components:

1. Create file in `resources/templates/components/`
2. Follow naming convention: `component-name.php`
3. Add parameter documentation
4. Include examples
5. Update this documentation

## Migration from Old HTML

When refactoring old HTML pages:

1. Replace inline Tailwind config with base layout
2. Convert form inputs to input component
3. Convert buttons to button component
4. Convert tables to table component
5. Move inline scripts to separate JS files

Example:

```php
// Old: index.php (700 lines with embedded config)
// New: index.php (50 lines using components)

<?php
$pageTitle = "Login";
$showHeader = false;
$showFooter = false;

ob_start();
?>

<div class="container mx-auto max-w-md mt-16">
    <h1 class="text-3xl font-bold mb-8">Login</h1>

    <form method="POST" action="resources/php/login.php">
        <?php
        $inputData = ['name' => 'username', 'label' => 'Username', 'required' => true];
        include 'resources/templates/components/input.php';
        ?>

        <?php
        $inputData = ['name' => 'password', 'type' => 'password', 'label' => 'Password', 'required' => true];
        include 'resources/templates/components/input.php';
        ?>

        <?php
        $buttonData = ['text' => 'Login', 'type' => 'submit', 'class' => 'w-full'];
        include 'resources/templates/components/button.php';
        ?>
    </form>
</div>

<?php
$content = ob_get_clean();
include 'resources/templates/base.php';
?>
```
