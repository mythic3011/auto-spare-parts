<?php
/**
 * Alert Component
 * 
 * Usage:
 * $alertData = [
 *     'type' => 'success',        // success, error, warning, info
 *     'title' => 'Success!',      // Optional
 *     'message' => 'Operation completed',
 *     'dismissible' => true,
 *     'class' => 'additional-classes'
 * ];
 */

$type = isset($alertData['type']) ? $alertData['type'] : 'info';
$title = isset($alertData['title']) ? htmlspecialchars($alertData['title']) : '';
$message = isset($alertData['message']) ? htmlspecialchars($alertData['message']) : '';
$dismissible = isset($alertData['dismissible']) ? $alertData['dismissible'] : true;
$additionalClass = isset($alertData['class']) ? htmlspecialchars($alertData['class']) : '';

// Type-based styling
$typeClasses = match($type) {
    'error', 'danger' => [
        'bg' => 'bg-danger-50 dark:bg-danger-900/20',
        'border' => 'border-danger-200 dark:border-danger-800',
        'text' => 'text-danger-800 dark:text-danger-200',
        'title' => 'text-danger-900 dark:text-danger-100',
        'icon' => '❌'
    ],
    'warning' => [
        'bg' => 'bg-warning-50 dark:bg-warning-900/20',
        'border' => 'border-warning-200 dark:border-warning-800',
        'text' => 'text-warning-800 dark:text-warning-200',
        'title' => 'text-warning-900 dark:text-warning-100',
        'icon' => '⚠️'
    ],
    'success' => [
        'bg' => 'bg-success-50 dark:bg-success-900/20',
        'border' => 'border-success-200 dark:border-success-800',
        'text' => 'text-success-800 dark:text-success-200',
        'title' => 'text-success-900 dark:text-success-100',
        'icon' => '✅'
    ],
    default => [
        'bg' => 'bg-blue-50 dark:bg-blue-900/20',
        'border' => 'border-blue-200 dark:border-blue-800',
        'text' => 'text-blue-800 dark:text-blue-200',
        'title' => 'text-blue-900 dark:text-blue-100',
        'icon' => 'ℹ️'
    ]
};
?>

<div class="<?php echo $typeClasses['bg']; ?> border <?php echo $typeClasses['border']; ?> rounded-lg p-4 <?php echo $additionalClass; ?> relative" 
     role="alert">
    <div class="flex items-start">
        <!-- Icon -->
        <span class="mr-3 flex-shrink-0 text-xl">
            <?php echo $typeClasses['icon']; ?>
        </span>
        
        <!-- Content -->
        <div class="flex-1">
            <?php if ($title): ?>
                <h3 class="font-bold <?php echo $typeClasses['title']; ?> mb-1">
                    <?php echo $title; ?>
                </h3>
            <?php endif; ?>
            
            <p class="<?php echo $typeClasses['text']; ?>">
                <?php echo $message; ?>
            </p>
        </div>
        
        <!-- Close Button -->
        <?php if ($dismissible): ?>
            <button type="button" 
                    class="ml-auto flex-shrink-0 <?php echo $typeClasses['text']; ?> hover:opacity-70 transition"
                    aria-label="Close alert"
                    onclick="this.parentElement.parentElement.style.display='none';">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        <?php endif; ?>
    </div>
</div>
