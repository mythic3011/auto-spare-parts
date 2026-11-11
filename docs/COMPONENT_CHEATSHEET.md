# Component Quick Reference Cheat Sheet

## Base Layout Template

```php
<?php
// Page config
$pageTitle = 'My Page';
$showHeader = true;
$showFooter = true;

ob_start();
?>
<!-- content here -->
<?php
$content = ob_get_clean();
include 'resources/templates/base.php';
?>
```

## Button

```php
<?php
$buttonData = [
    'text' => 'Click Me',
    'type' => 'submit',              // button, submit, reset
    'variant' => 'primary',          // primary, secondary, danger, success, warning
    'size' => 'md',                  // sm, md, lg
    'href' => '/link',               // Makes it an <a> tag
    'disabled' => false
];
include 'resources/templates/components/button.php';
?>
```

## Input

```php
<?php
$inputData = [
    'name' => 'email',
    'type' => 'email',               // text, email, password, number, etc.
    'label' => 'Email',
    'placeholder' => 'you@example.com',
    'required' => true,
    'error' => 'Email required',     // Show error
    'hint' => 'Helper text'
];
include 'resources/templates/components/input.php';
?>
```

## Select

```php
<?php
$selectData = [
    'name' => 'category',
    'label' => 'Category',
    'placeholder' => 'Choose...',
    'required' => true,
    'options' => [
        'val1' => 'Option 1',
        'Group' => [
            'val2' => 'Option 2'
        ]
    ]
];
include 'resources/templates/components/select.php';
?>
```

## Card

```php
<?php
$cardData = [
    'title' => 'Card Title',
    'subtitle' => 'Subtitle',
    'image' => 'path/to/image.jpg',
    'content' => '<p>Content here</p>',
    'footer' => '<button>Action</button>',
    'hoverable' => true
];
include 'resources/templates/components/card.php';
?>
```

## Alert

```php
<?php
$alertData = [
    'type' => 'success',             // success, error, warning, info
    'title' => 'Success!',
    'message' => 'Operation completed',
    'dismissible' => true
];
include 'resources/templates/components/alert.php';
?>
```

## Modal

```php
<?php
$modalData = [
    'id' => 'my-modal',
    'title' => 'Confirm',
    'content' => '<p>Are you sure?</p>',
    'size' => 'md',                  // sm, md, lg, xl
    'footer' => [
        ['text' => 'Cancel', 'variant' => 'secondary', 'dismissModal' => true],
        ['text' => 'Confirm', 'variant' => 'danger', 'onclick' => 'handleConfirm()']
    ]
];
include 'resources/templates/components/modal.php';
?>

<!-- Open modal with JS -->
<script>openModal('my-modal');</script>
```

## Table

```php
<?php
$tableData = [
    'columns' => [
        ['key' => 'id', 'label' => 'ID'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'status', 'label' => 'Status', 'formatter' => 'statusBadge']
    ],
    'rows' => [
        ['id' => 1, 'name' => 'Item A', 'status' => 'active']
    ],
    'striped' => true,
    'hoverable' => true,
    'paginate' => false
];
include 'resources/templates/components/table.php';
?>
```

## Common Page Variables

```php
$pageTitle = 'Page Title';           // Required
$metaDescription = 'Description';    // SEO
$showHeader = true;                  // Show nav
$showFooter = true;                  // Show footer
$userRole = 'dealer';                // dealer, manager, guest
$userName = 'John Doe';              // User display name
$basePath = '/';                     // Base URL path
$additionalCSS = ['css/file.css'];   // Extra CSS
$additionalJS = ['js/file.js'];      // Extra JS
$inlineScript = '<script>...</script>'; // Inline JS
```

## Tailwind Color Classes

### Primary (Blue)

- `text-primary-600` `bg-primary-600` `border-primary-600`

### Success (Green)

- `text-success-600` `bg-success-600` `border-success-600`

### Danger (Red)

- `text-danger-600` `bg-danger-600` `border-danger-600`

### Warning (Yellow)

- `text-warning-600` `bg-warning-600` `border-warning-600`

### Dark Mode

- Add `dark:` prefix: `dark:bg-gray-800` `dark:text-white`

## Common Patterns

### Login Form

```php
ob_start();
?>
<form method="POST" action="login.php">
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
<?php
$content = ob_get_clean();
```

### Data Grid with Cards

```php
ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php foreach ($items as $item): ?>
        <?php
        $cardData = [
            'title' => $item['name'],
            'content' => '<p>$' . $item['price'] . '</p>',
            'hoverable' => true
        ];
        include 'resources/templates/components/card.php';
        ?>
    <?php endforeach; ?>
</div>
<?php
$content = ob_get_clean();
```

### Dashboard Stats

```php
ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <?php
    $stats = [
        ['label' => 'Orders', 'value' => 42],
        ['label' => 'Revenue', 'value' => '$12,345']
    ];

    foreach ($stats as $stat):
        $cardData = [
            'title' => $stat['label'],
            'content' => '<p class="text-3xl font-bold text-primary-600">' . $stat['value'] . '</p>'
        ];
        include 'resources/templates/components/card.php';
    endforeach;
    ?>
</div>
<?php
$content = ob_get_clean();
```

## JavaScript Helpers

```javascript
// Open modal
openModal("modal-id");

// Close modal
closeModal("modal-id");

// Dark mode
document.documentElement.classList.toggle("dark");
localStorage.theme = "dark"; // or 'light'

// Fetch API
fetch("api.php", {
  method: "POST",
  body: formData,
})
  .then((response) => response.json())
  .then((data) => console.log(data));
```

## Common Layouts

### Centered Container

```php
<div class="container mx-auto max-w-4xl px-4 py-8">
    <!-- content -->
</div>
```

### Two Column

```php
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div><!-- left --></div>
    <div><!-- right --></div>
</div>
```

### Sidebar Layout

```php
<div class="flex gap-6">
    <aside class="w-64"><!-- sidebar --></aside>
    <main class="flex-1"><!-- content --></main>
</div>
```

## Responsive Breakpoints

- `sm:` - 640px and up (mobile landscape)
- `md:` - 768px and up (tablet)
- `lg:` - 1024px and up (desktop)
- `xl:` - 1280px and up (large desktop)

Example: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3`

## Tips

1. Always escape output: `<?php echo htmlspecialchars($var); ?>`
2. Use `ob_start()` / `ob_get_clean()` for content buffering
3. Set page variables before `ob_start()`
4. Include `base.php` last
5. Use components instead of raw HTML
6. Test on mobile, tablet, desktop
7. Test light and dark modes

## File Locations

- Base: `resources/templates/base.php`
- Components: `resources/templates/components/`
- Header: `resources/templates/header.php`
- Footer: `resources/templates/footer.php`

## Full Documentation

- `HTML_COMPONENTS.md` - Detailed component docs
- `HTML_MIGRATION_GUIDE.md` - Migration guide
- `HTML_REFACTORING_SUMMARY.md` - Overview
