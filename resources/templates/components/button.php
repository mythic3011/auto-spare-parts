<?php
/**
 * Button Component
 * 
 * Usage:
 * $buttonData = [
 *     'text' => 'Click Me',
 *     'href' => '/page',              // Optional: for link button
 *     'type' => 'submit',              // Optional: button type (submit, button, reset)
 *     'variant' => 'primary',          // primary, secondary, danger, success, warning
 *     'size' => 'md',                  // sm, md, lg
 *     'disabled' => false,
 *     'class' => 'additional-classes',
 *     'attributes' => [...]            // Additional HTML attributes
 * ];
 */

$text = isset($buttonData['text']) ? htmlspecialchars($buttonData['text']) : 'Button';
$type = isset($buttonData['type']) ? $buttonData['type'] : 'button';
$variant = isset($buttonData['variant']) ? $buttonData['variant'] : 'primary';
$size = isset($buttonData['size']) ? $buttonData['size'] : 'md';
$disabled = isset($buttonData['disabled']) ? $buttonData['disabled'] : false;
$href = isset($buttonData['href']) ? htmlspecialchars($buttonData['href']) : null;
$additionalClass = isset($buttonData['class']) ? htmlspecialchars($buttonData['class']) : '';
$attributes = isset($buttonData['attributes']) ? $buttonData['attributes'] : [];

// Base classes
$baseClasses = 'font-semibold rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900';

// Size classes
$sizeClasses = match($size) {
    'sm' => 'px-3 py-1.5 text-sm',
    'lg' => 'px-6 py-3 text-lg',
    default => 'px-4 py-2 text-base',
};

// Variant classes
$variantClasses = match($variant) {
    'secondary' => 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 focus:ring-gray-500',
    'danger' => 'bg-danger-600 text-white hover:bg-danger-700 focus:ring-danger-500',
    'success' => 'bg-success-600 text-white hover:bg-success-700 focus:ring-success-500',
    'warning' => 'bg-warning-600 text-white hover:bg-warning-700 focus:ring-warning-500',
    default => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
};

// Disabled classes
$disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : '';

// Combine all classes
$allClasses = "{$baseClasses} {$sizeClasses} {$variantClasses} {$disabledClasses} {$additionalClass}";

// Build attributes string
$attributeString = '';
foreach ($attributes as $key => $value) {
    if ($value === true) {
        $attributeString .= " {$key}";
    } elseif ($value !== false && $value !== null) {
        $attributeString .= " {$key}=\"" . htmlspecialchars($value) . "\"";
    }
}

if ($disabled) {
    $attributeString .= ' disabled';
}
?>

<?php if ($href): ?>
    <a href="<?php echo $href; ?>" class="<?php echo $allClasses; ?>"<?php echo $attributeString; ?>>
        <?php echo $text; ?>
    </a>
<?php else: ?>
    <button type="<?php echo htmlspecialchars($type); ?>" class="<?php echo $allClasses; ?>"<?php echo $attributeString; ?>>
        <?php echo $text; ?>
    </button>
<?php endif; ?>
