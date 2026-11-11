<!-- Alert/Message Template -->
<?php if (isset($alert)): ?>
    <div class="alert alert-<?php echo TemplateRenderer::escape($alert['type']); ?>">
        <button class="alert-close" onclick="this.parentElement.style.display='none';">&times;</button>
        <strong><?php echo ucfirst($alert['type']); ?>:</strong>
        <?php echo TemplateRenderer::escape($alert['message']); ?>
    </div>
<?php endif; ?>
