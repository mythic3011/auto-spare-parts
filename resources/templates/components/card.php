<?php
/**
 * Card Component
 * 
 * Usage:
 * $cardData = [
 *     'title' => 'Card Title',
 *     'subtitle' => 'Optional subtitle',
 *     'content' => '<p>Card content here</p>',  // or use slot
 *     'footer' => '<p>Footer content</p>',      // optional
 *     'image' => '/path/to/image.jpg',          // optional header image
 *     'hoverable' => false,
 *     'class' => 'additional-classes'
 * ];
 */

$title = isset($cardData['title']) ? htmlspecialchars($cardData['title']) : '';
$subtitle = isset($cardData['subtitle']) ? htmlspecialchars($cardData['subtitle']) : '';
$content = isset($cardData['content']) ? $cardData['content'] : '';
$footer = isset($cardData['footer']) ? $cardData['footer'] : '';
$image = isset($cardData['image']) ? htmlspecialchars($cardData['image']) : '';
$hoverable = isset($cardData['hoverable']) ? $cardData['hoverable'] : false;
$additionalClass = isset($cardData['class']) ? htmlspecialchars($cardData['class']) : '';

$hoverClasses = $hoverable ? 'hover:shadow-lg hover:scale-105 transition-transform duration-200' : '';
?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md <?php echo $hoverClasses; ?> <?php echo $additionalClass; ?>">
    <!-- Image Header -->
    <?php if ($image): ?>
        <img src="<?php echo $image; ?>" alt="<?php echo $title; ?>" 
             class="w-full h-48 object-cover rounded-t-lg">
    <?php endif; ?>
    
    <!-- Card Content -->
    <div class="p-6">
        <!-- Title -->
        <?php if ($title): ?>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                <?php echo $title; ?>
            </h3>
        <?php endif; ?>
        
        <!-- Subtitle -->
        <?php if ($subtitle): ?>
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                <?php echo $subtitle; ?>
            </p>
        <?php endif; ?>
        
        <!-- Content -->
        <?php if ($content): ?>
            <div class="text-gray-700 dark:text-gray-300">
                <?php echo $content; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <?php if ($footer): ?>
        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-750 rounded-b-lg">
            <?php echo $footer; ?>
        </div>
    <?php endif; ?>
</div>
