<?php
/**
 * Input Component
 * 
 * Usage:
 * $inputData = [
 *     'name' => 'email',
 *     'type' => 'email',           // text, email, password, number, etc.
 *     'label' => 'Email Address',
 *     'placeholder' => 'user@example.com',
 *     'value' => '',
 *     'required' => true,
 *     'disabled' => false,
 *     'error' => 'Email is required',  // Show error state
 *     'hint' => 'Helper text',
 *     'icon' => 'email',               // Optional icon class
 *     'class' => 'additional-classes',
 *     'attributes' => [...]
 * ];
 */

$name = isset($inputData['name']) ? htmlspecialchars($inputData['name']) : '';
$type = isset($inputData['type']) ? htmlspecialchars($inputData['type']) : 'text';
$label = isset($inputData['label']) ? htmlspecialchars($inputData['label']) : '';
$placeholder = isset($inputData['placeholder']) ? htmlspecialchars($inputData['placeholder']) : '';
$value = isset($inputData['value']) ? htmlspecialchars($inputData['value']) : '';
$required = isset($inputData['required']) ? $inputData['required'] : false;
$disabled = isset($inputData['disabled']) ? $inputData['disabled'] : false;
$error = isset($inputData['error']) ? htmlspecialchars($inputData['error']) : '';
$hint = isset($inputData['hint']) ? htmlspecialchars($inputData['hint']) : '';
$icon = isset($inputData['icon']) ? htmlspecialchars($inputData['icon']) : '';
$additionalClass = isset($inputData['class']) ? htmlspecialchars($inputData['class']) : '';
$attributes = isset($inputData['attributes']) ? $inputData['attributes'] : [];

// Build attributes string
$attributeString = '';
foreach ($attributes as $key => $val) {
    if ($val === true) {
        $attributeString .= " {$key}";
    } elseif ($val !== false && $val !== null) {
        $attributeString .= " {$key}=\"" . htmlspecialchars($val) . "\"";
    }
}

$errorClass = $error ? 'border-danger-500' : 'border-gray-300 dark:border-gray-600 focus:border-primary-500';
$disabledClass = $disabled ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-50' : '';
?>

<div class="mb-4">
    <!-- Label -->
    <?php if ($label): ?>
        <label for="<?php echo $name; ?>" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            <?php echo $label; ?>
            <?php if ($required): ?>
                <span class="text-danger-500">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>
    
    <!-- Input Wrapper (for icon support) -->
    <div class="relative">
        <input 
            type="<?php echo $type; ?>"
            id="<?php echo $name; ?>"
            name="<?php echo $name; ?>"
            class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition focus:outline-none focus:ring-2 focus:ring-primary-500 <?php echo $errorClass; ?> <?php echo $disabledClass; ?> <?php echo $additionalClass; ?>"
            placeholder="<?php echo $placeholder; ?>"
            value="<?php echo $value; ?>"
            <?php if ($required): ?>required<?php endif; ?>
            <?php if ($disabled): ?>disabled<?php endif; ?>
            <?php echo $attributeString; ?>
        >
    </div>
    
    <!-- Error Message -->
    <?php if ($error): ?>
        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">
            <span class="inline-block mr-1">⚠</span><?php echo $error; ?>
        </p>
    <?php endif; ?>
    
    <!-- Hint Text -->
    <?php if ($hint && !$error): ?>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            <?php echo $hint; ?>
        </p>
    <?php endif; ?>
</div>
