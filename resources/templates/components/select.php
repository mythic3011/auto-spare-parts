<?php
/**
 * Select Component
 * 
 * Usage:
 * $selectData = [
 *     'name' => 'category',
 *     'label' => 'Select Category',
 *     'value' => 'selected-value',
 *     'required' => true,
 *     'disabled' => false,
 *     'error' => '',
 *     'placeholder' => 'Choose...',
 *     'options' => [
 *         'value1' => 'Display Text 1',
 *         'value2' => 'Display Text 2',
 *         'group-name' => [
 *             'subval1' => 'Sub Option 1',
 *             'subval2' => 'Sub Option 2'
 *         ]
 *     ],
 *     'class' => 'additional-classes'
 * ];
 */

$name = isset($selectData['name']) ? htmlspecialchars($selectData['name']) : '';
$label = isset($selectData['label']) ? htmlspecialchars($selectData['label']) : '';
$value = isset($selectData['value']) ? htmlspecialchars($selectData['value']) : '';
$required = isset($selectData['required']) ? $selectData['required'] : false;
$disabled = isset($selectData['disabled']) ? $selectData['disabled'] : false;
$error = isset($selectData['error']) ? htmlspecialchars($selectData['error']) : '';
$placeholder = isset($selectData['placeholder']) ? htmlspecialchars($selectData['placeholder']) : 'Choose an option...';
$options = isset($selectData['options']) ? $selectData['options'] : [];
$additionalClass = isset($selectData['class']) ? htmlspecialchars($selectData['class']) : '';

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
    
    <!-- Select -->
    <select 
        id="<?php echo $name; ?>"
        name="<?php echo $name; ?>"
        class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition focus:outline-none focus:ring-2 focus:ring-primary-500 <?php echo $errorClass; ?> <?php echo $disabledClass; ?> <?php echo $additionalClass; ?>"
        <?php if ($required): ?>required<?php endif; ?>
        <?php if ($disabled): ?>disabled<?php endif; ?>
    >
        <!-- Placeholder Option -->
        <?php if ($placeholder): ?>
            <option value="">-- <?php echo $placeholder; ?> --</option>
        <?php endif; ?>
        
        <!-- Options -->
        <?php foreach ($options as $optKey => $optValue): ?>
            <?php if (is_array($optValue)): ?>
                <!-- Optgroup -->
                <optgroup label="<?php echo htmlspecialchars($optKey); ?>">
                    <?php foreach ($optValue as $subKey => $subValue): ?>
                        <option value="<?php echo htmlspecialchars($subKey); ?>" 
                                <?php if ($value === (string)$subKey): ?>selected<?php endif; ?>>
                            <?php echo htmlspecialchars($subValue); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php else: ?>
                <!-- Regular Option -->
                <option value="<?php echo htmlspecialchars($optKey); ?>" 
                        <?php if ($value === (string)$optKey): ?>selected<?php endif; ?>>
                    <?php echo htmlspecialchars($optValue); ?>
                </option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>
    
    <!-- Error Message -->
    <?php if ($error): ?>
        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">
            <span class="inline-block mr-1">⚠</span><?php echo $error; ?>
        </p>
    <?php endif; ?>
</div>
