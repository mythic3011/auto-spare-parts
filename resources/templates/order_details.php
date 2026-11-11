<!-- Order Details Template -->
<div class="order-details-container">
    <?php if (isset($order)): ?>
        <!-- Summary Section -->
        <div class="order-summary">
            <h2>Order #<?php echo TemplateRenderer::escape($order['summary']['id']); ?></h2>
            
            <div class="summary-grid">
                <div class="summary-item">
                    <label>Order Date:</label>
                    <span><?php echo TemplateRenderer::escape($order['summary']['orderDate']); ?></span>
                </div>
                <div class="summary-item">
                    <label>Order Time:</label>
                    <span><?php echo TemplateRenderer::escape($order['summary']['orderTime']); ?></span>
                </div>
                <div class="summary-item">
                    <label>Status:</label>
                    <span class="status-badge status-<?php echo strtolower($order['summary']['orderStatus']); ?>">
                        <?php echo TemplateRenderer::escape($order['summary']['orderStatus']); ?>
                    </span>
                </div>
                <div class="summary-item">
                    <label>Delivery Date:</label>
                    <span><?php echo TemplateRenderer::escape($order['summary']['deliveryDate']); ?></span>
                </div>
            </div>

            <!-- Delivery Address -->
            <div class="address-section">
                <h3>Delivery Address</h3>
                <p><?php echo TemplateRenderer::escape($order['summary']['deliveryAddress']); ?></p>
            </div>

            <!-- Contact Information -->
            <div class="contact-section">
                <div class="contact-group">
                    <h4>Dealer Information</h4>
                    <p><strong><?php echo TemplateRenderer::escape($order['summary']['dealerCompany'] ?? 'N/A'); ?></strong></p>
                    <p>Contact: <?php echo TemplateRenderer::escape($order['summary']['dealerContact'] ?? 'N/A'); ?></p>
                    <p>Phone: <?php echo TemplateRenderer::escape($order['summary']['dealerPhone'] ?? 'N/A'); ?></p>
                </div>
                <div class="contact-group">
                    <h4>Sales Manager</h4>
                    <p><strong><?php echo TemplateRenderer::escape($order['summary']['salesManagerName'] ?? 'N/A'); ?></strong></p>
                    <p>Phone: <?php echo TemplateRenderer::escape($order['summary']['salesManagerContact'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>

        <!-- Items Section -->
        <div class="order-items-section">
            <h3>Order Items</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($order['items'])): ?>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td><?php echo TemplateRenderer::escape($item['id']); ?></td>
                                <td><?php echo TemplateRenderer::escape($item['name']); ?></td>
                                <td class="text-center"><?php echo TemplateRenderer::escape((string)$item['quantity']); ?></td>
                                <td class="text-right">$<?php echo number_format($item['price'], 2); ?></td>
                                <td class="text-right">$<?php echo number_format($item['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Order Totals -->
        <div class="order-totals">
            <div class="totals-row">
                <span class="label">Subtotal:</span>
                <span class="value">$<?php echo number_format($order['summary']['totalAmount'], 2); ?></span>
            </div>
            <div class="totals-row">
                <span class="label">Shipping Cost:</span>
                <span class="value">$<?php echo number_format($order['summary']['shippingCost'], 2); ?></span>
            </div>
            <div class="totals-row total">
                <span class="label">Total Cost:</span>
                <span class="value">$<?php echo number_format($order['summary']['totalCost'], 2); ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <?php if ($order['summary']['orderStatus'] === 'Pending'): ?>
                <button class="btn btn-danger" onclick="cancelOrder('<?php echo TemplateRenderer::escape($order['summary']['id']); ?>')">
                    Cancel Order
                </button>
            <?php endif; ?>
            <button class="btn btn-primary" onclick="downloadInvoice('<?php echo TemplateRenderer::escape($order['summary']['id']); ?>')">
                Download Invoice
            </button>
            <a href="viewOrderRecord.html" class="btn btn-secondary">Back to Orders</a>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            Order not found or you don't have permission to view it.
        </div>
    <?php endif; ?>
</div>
