<!-- Orders Table Template -->
<table class="orders-table">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Order Date</th>
            <th>Status</th>
            <th>Items</th>
            <th>Total Amount</th>
            <th>Delivery Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <tr class="order-row" data-order-id="<?php echo TemplateRenderer::escape($order['orderID']); ?>">
                    <td class="order-id">
                        <a href="?id=<?php echo TemplateRenderer::escape($order['orderID']); ?>">
                            <?php echo TemplateRenderer::escape($order['orderID']); ?>
                        </a>
                    </td>
                    <td class="order-date">
                        <?php echo date('d/m/Y H:i', strtotime($order['orderDateTime'] ?? 'now')); ?>
                    </td>
                    <td class="order-status">
                        <span class="status-badge status-<?php echo strtolower($order['orderStatus']); ?>">
                            <?php echo TemplateRenderer::escape($order['orderStatus']); ?>
                        </span>
                    </td>
                    <td class="order-items">
                        <?php echo isset($order['itemCount']) ? TemplateRenderer::escape((string)$order['itemCount']) : '0'; ?>
                    </td>
                    <td class="order-total">
                        $<?php echo number_format((float)($order['totalCost'] ?? 0), 2); ?>
                    </td>
                    <td class="delivery-date">
                        <?php echo date('d/m/Y', strtotime($order['deliveryDate'] ?? 'now')); ?>
                    </td>
                    <td class="actions">
                        <a href="?action=view&id=<?php echo TemplateRenderer::escape($order['orderID']); ?>" 
                           class="btn btn-sm btn-info">View</a>
                        <?php if ($order['orderStatus'] === 'Pending'): ?>
                            <a href="?action=cancel&id=<?php echo TemplateRenderer::escape($order['orderID']); ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure?')">Cancel</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center text-muted">No orders found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
