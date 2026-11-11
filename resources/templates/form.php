<!-- Form Template -->
<form class="form-container" method="<?php echo isset($method) ? TemplateRenderer::escape($method) : 'POST'; ?>" 
      action="<?php echo isset($action) ? TemplateRenderer::escape($action) : ''; ?>">
    
    <!-- CSRF Token (if provided) -->
    <?php if (isset($csrfToken)): ?>
        <input type="hidden" name="csrf_token" value="<?php echo TemplateRenderer::escape($csrfToken); ?>">
    <?php endif; ?>

    <!-- Form Title -->
    <?php if (isset($formTitle)): ?>
        <h2 class="form-title"><?php echo TemplateRenderer::escape($formTitle); ?></h2>
    <?php endif; ?>

    <!-- Form Fields -->
    <?php if (isset($fields) && is_array($fields)): ?>
        <?php foreach ($fields as $fieldName => $fieldConfig): ?>
            <div class="form-group">
                <label for="<?php echo TemplateRenderer::escape($fieldName); ?>">
                    <?php echo TemplateRenderer::escape($fieldConfig['label'] ?? ucfirst($fieldName)); ?>
                    <?php if (isset($fieldConfig['required']) && $fieldConfig['required']): ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </label>

                <?php if ($fieldConfig['type'] === 'textarea'): ?>
                    <textarea 
                        id="<?php echo TemplateRenderer::escape($fieldName); ?>"
                        name="<?php echo TemplateRenderer::escape($fieldName); ?>"
                        class="form-control"
                        <?php if (isset($fieldConfig['required']) && $fieldConfig['required']): ?>required<?php endif; ?>
                        <?php if (isset($fieldConfig['placeholder'])): ?>placeholder="<?php echo TemplateRenderer::escape($fieldConfig['placeholder']); ?>"<?php endif; ?>
                    ><?php echo isset($fieldConfig['value']) ? TemplateRenderer::escape((string)$fieldConfig['value']) : ''; ?></textarea>

                <?php elseif ($fieldConfig['type'] === 'select'): ?>
                    <select 
                        id="<?php echo TemplateRenderer::escape($fieldName); ?>"
                        name="<?php echo TemplateRenderer::escape($fieldName); ?>"
                        class="form-control"
                        <?php if (isset($fieldConfig['required']) && $fieldConfig['required']): ?>required<?php endif; ?>
                    >
                        <option value="">-- Select --</option>
                        <?php if (isset($fieldConfig['options']) && is_array($fieldConfig['options'])): ?>
                            <?php foreach ($fieldConfig['options'] as $optionValue => $optionLabel): ?>
                                <option value="<?php echo TemplateRenderer::escape((string)$optionValue); ?>" 
                                    <?php if (isset($fieldConfig['value']) && $fieldConfig['value'] === $optionValue): ?>selected<?php endif; ?>>
                                    <?php echo TemplateRenderer::escape((string)$optionLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                <?php elseif ($fieldConfig['type'] === 'checkbox'): ?>
                    <input 
                        type="checkbox"
                        id="<?php echo TemplateRenderer::escape($fieldName); ?>"
                        name="<?php echo TemplateRenderer::escape($fieldName); ?>"
                        class="form-checkbox"
                        value="1"
                        <?php if (isset($fieldConfig['value']) && $fieldConfig['value']): ?>checked<?php endif; ?>
                    >

                <?php else: ?>
                    <!-- Default: text input -->
                    <input 
                        type="<?php echo TemplateRenderer::escape($fieldConfig['type'] ?? 'text'); ?>"
                        id="<?php echo TemplateRenderer::escape($fieldName); ?>"
                        name="<?php echo TemplateRenderer::escape($fieldName); ?>"
                        class="form-control"
                        <?php if (isset($fieldConfig['required']) && $fieldConfig['required']): ?>required<?php endif; ?>
                        <?php if (isset($fieldConfig['placeholder'])): ?>placeholder="<?php echo TemplateRenderer::escape($fieldConfig['placeholder']); ?>"<?php endif; ?>
                        value="<?php echo isset($fieldConfig['value']) ? TemplateRenderer::escape((string)$fieldConfig['value']) : ''; ?>"
                    >
                <?php endif; ?>

                <!-- Field Error (if any) -->
                <?php if (isset($errors) && isset($errors[$fieldName])): ?>
                    <small class="form-error">
                        <?php echo TemplateRenderer::escape($errors[$fieldName][0] ?? ''); ?>
                    </small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Submit Button -->
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <?php echo isset($submitLabel) ? TemplateRenderer::escape($submitLabel) : 'Submit'; ?>
        </button>
        <?php if (isset($cancelUrl)): ?>
            <a href="<?php echo TemplateRenderer::escape($cancelUrl); ?>" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
    </div>
</form>
