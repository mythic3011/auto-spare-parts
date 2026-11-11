<?php
/**
 * Modal Component
 * 
 * Usage:
 * $modalData = [
 *     'id' => 'confirm-modal',
 *     'title' => 'Confirm Action',
 *     'content' => '<p>Are you sure?</p>',
 *     'size' => 'md',              // sm, md, lg, xl
 *     'showCloseButton' => true,
 *     'footer' => [                // Optional footer buttons
 *         ['text' => 'Cancel', 'variant' => 'secondary', 'dismissModal' => true],
 *         ['text' => 'Confirm', 'variant' => 'danger', 'onclick' => 'handleConfirm()']
 *     ]
 * ];
 */

$id = isset($modalData['id']) ? htmlspecialchars($modalData['id']) : 'modal-' . uniqid();
$title = isset($modalData['title']) ? htmlspecialchars($modalData['title']) : '';
$content = isset($modalData['content']) ? $modalData['content'] : '';
$size = isset($modalData['size']) ? $modalData['size'] : 'md';
$showCloseButton = isset($modalData['showCloseButton']) ? $modalData['showCloseButton'] : true;
$footer = isset($modalData['footer']) ? $modalData['footer'] : null;

// Size classes
$sizeClasses = match($size) {
    'sm' => 'max-w-sm',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
    default => 'max-w-md'
};
?>

<!-- Modal Overlay -->
<div id="<?php echo $id; ?>Overlay" 
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300"
     onclick="closeModal('<?php echo $id; ?>')">
</div>

<!-- Modal Container -->
<div id="<?php echo $id; ?>" 
     class="hidden fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white dark:bg-gray-800 rounded-lg shadow-2xl z-50 w-full mx-4 <?php echo $sizeClasses; ?> transition-all duration-300">
    
    <!-- Header -->
    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            <?php echo $title; ?>
        </h2>
        
        <?php if ($showCloseButton): ?>
            <button type="button" 
                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition"
                    onclick="closeModal('<?php echo $id; ?>')">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        <?php endif; ?>
    </div>
    
    <!-- Content -->
    <div class="p-6 text-gray-700 dark:text-gray-300">
        <?php echo $content; ?>
    </div>
    
    <!-- Footer -->
    <?php if ($footer): ?>
        <div class="flex gap-3 justify-end p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-b-lg">
            <?php foreach ($footer as $button): ?>
                <?php
                $btnText = htmlspecialchars($button['text'] ?? 'Button');
                $btnVariant = $button['variant'] ?? 'primary';
                $btnOnclick = isset($button['onclick']) ? htmlspecialchars($button['onclick']) : '';
                $dismissModal = isset($button['dismissModal']) ? $button['dismissModal'] : false;
                
                $variantClasses = match($btnVariant) {
                    'secondary' => 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white hover:bg-gray-400 dark:hover:bg-gray-500',
                    'danger' => 'bg-danger-600 text-white hover:bg-danger-700',
                    'success' => 'bg-success-600 text-white hover:bg-success-700',
                    default => 'bg-primary-600 text-white hover:bg-primary-700'
                };
                ?>
                
                <button type="button" 
                        class="px-4 py-2 rounded-lg font-semibold transition <?php echo $variantClasses; ?>"
                        <?php if ($btnOnclick): ?>onclick="<?php echo $btnOnclick; ?>"<?php endif; ?>
                        <?php if ($dismissModal): ?>onclick="closeModal('<?php echo $id; ?>')"<?php endif; ?>>
                    <?php echo $btnText; ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Script -->
<script>
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        const overlay = document.getElementById(modalId + 'Overlay');
        if (modal) {
            modal.classList.remove('hidden');
            overlay?.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        const overlay = document.getElementById(modalId + 'Overlay');
        if (modal) {
            modal.classList.add('hidden');
            overlay?.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('[id$="Overlay"]').forEach(overlay => {
                if (!overlay.classList.contains('hidden')) {
                    const modalId = overlay.id.replace('Overlay', '');
                    closeModal(modalId);
                }
            });
        }
    });
</script>
