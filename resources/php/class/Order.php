<?php

declare(strict_types=1);

require_once 'Database.php';
require_once 'ValidationHelper.php';
require_once __DIR__ . '/Shipping.php';

/**
 * Order - Handles order operations
 *
 * Manages order creation, retrieval, updates, and status management
 * with proper transaction handling and validation
 */
class Order
{
    private DatabaseAdapter $db;
    private Shipping $shipping;

    public function __construct(?DatabaseAdapter $db = null, ?Shipping $shipping = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->shipping = $shipping ?? new Shipping();
    }

    /**
     * Place a new order with cart items
     *
     * @param array<mixed> $orderData Order data including cart items
     * @param array<string, mixed> $user User information
     * @return string Order ID
     * @throws Exception
     */
    public function placeOrder(array $orderData, array $user): string
    {
        // Validate input
        $validation = new ValidationHelper();
        $validation->required('cart', $orderData['cart'] ?? null);
        $validation->required('deliveryAddress', $user['deliveryAddress'] ?? null);

        if (!$validation->passed()) {
            throw new Exception('Invalid order data: ' . json_encode($validation->getErrors()));
        }

        $this->db->beginTransaction();

        try {
            $cart = $this->loadCheckoutItems($orderData['cart']);
            $deliveryAddress = $user['deliveryAddress'];
            $deliveryDate = date('Y-m-d H:i:s', strtotime('+7 days'));
            $publicID = bin2hex(random_bytes(16));
            $orderDateTime = date('Y-m-d H:i:s');

            // Validate user ID exists
            $userID = $user['id'] ?? $user['UserID'] ?? null;
            if (null === $userID) {
                throw new Exception('Invalid user ID');
            }

            // Calculate shipping cost
            $shipCost = $this->calculateShippingCost($cart);

            // Assign a sales manager
            $salesManagerID = $this->assignSalesManager();

            // Insert order
            $this->db->execute(
                "INSERT INTO Orders (publicID, dealerID, salesManagerID, orderDateTime, deliveryAddress, deliveryDate, orderStatus, shipCost)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$publicID, $userID, $salesManagerID, $orderDateTime, $deliveryAddress, $deliveryDate, 'Pending', $shipCost]
            );

            $orderID = (string) $this->db->lastInsertId();

            // Process order items
            foreach ($cart as $item) {
                $this->processOrderItem($orderID, $item);
            }

            $this->db->commit();
            return $orderID;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Read a cart item field from either an array or a CartItem-like object.
     *
     * @param array<string, mixed>|object $item
     * @param array<int, string> $keys
     * @param mixed $default
     * @return mixed
     */
    private function getCartItemField(array|object $item, array $keys, mixed $default = null): mixed
    {
        if (is_array($item)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $item) && $item[$key] !== null && $item[$key] !== '') {
                    return $item[$key];
                }
            }
        }

        foreach ($keys as $key) {
            if (isset($item->{$key}) && $item->{$key} !== '') {
                return $item->{$key};
            }
        }

        if (is_object($item) && method_exists($item, 'toArray')) {
            $data = $item->toArray();

            if (is_array($data)) {
                foreach ($keys as $key) {
                    if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                        return $data[$key];
                    }
                }
            }
        }

        return $default;
    }

    /**
     * @param array<string, mixed>|object $item
     */
    private function getCartItemId(array|object $item): string
    {
        $id = $this->getCartItemField($item, ['itemID', 'id', 'partId', 'sparePartNum'], '');
        if (!is_string($id) && !is_int($id)) {
            throw new InvalidArgumentException('Invalid item ID');
        }
        return (string) $id;
    }

    /**
     * Resolve all authoritative checkout values while holding catalog row locks.
     * Client prices and weights are deliberately ignored for legacy compatibility.
     * @return list<array{itemID:string,quantity:int,price:string,weight:float}>
     */
    private function loadCheckoutItems(mixed $submitted): array
    {
        if (!is_array($submitted) || $submitted === []) {
            throw new InvalidArgumentException('Invalid order data: cart must contain items');
        }
        $quantities = [];
        foreach ($submitted as $item) {
            if (!is_array($item) && !is_object($item)) {
                throw new InvalidArgumentException('Invalid item data');
            }
            $id = $this->getCartItemId($item);
            $quantity = $this->getCartItemField($item, ['quantity', 'qty']);
            if ((!is_int($quantity) && !is_string($quantity))
                || !preg_match('/^[1-9][0-9]*$/D', $id)
                || filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false) {
                throw new InvalidArgumentException('Invalid item data');
            }
            if (isset($quantities[$id])) {
                throw new InvalidArgumentException('Duplicate cart item');
            }
            $quantities[$id] = (int) $quantity;
        }
        // Consistent lock order reduces deadlocks between overlapping checkouts.
        ksort($quantities, SORT_NUMERIC);
        $cart = [];
        foreach ($quantities as $id => $quantity) {
            $part = $this->db->fetchOne(
                'SELECT price, weight, stockItemQty FROM Item WHERE sparePartNum = ? FOR UPDATE',
                [(string) $id]
            );
            if ($part === null) {
                throw new InvalidArgumentException('Unknown cart item');
            }
            if ((int) $part['stockItemQty'] < $quantity) {
                throw new RuntimeException('Insufficient stock');
            }
            $price = (string) $part['price'];
            if (!preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D', $price)
                || !is_numeric($part['weight']) || !is_finite((float) $part['weight']) || (float) $part['weight'] < 0) {
                throw new RuntimeException('Invalid catalog data');
            }
            $cart[] = ['itemID' => (string) $id, 'quantity' => $quantity, 'price' => $price, 'weight' => (float) $part['weight']];
        }
        return $cart;
    }

    /**
     * Process a single order item
     *
     * @param string $orderID
     * @param array<string, mixed>|object $item
     * @throws Exception
     */
    private function processOrderItem(string $orderID, array $item): void
    {
        $itemID = $this->getCartItemId($item);
        $quantity = $item['quantity'];
        $price = $item['price'];

        if ($itemID === '' || $quantity <= 0 || $price < 0) {
            throw new Exception('Invalid item data');
        }

        // Atomically reserve stock only when enough quantity exists.
        $updatedRows = $this->db->execute(
            "UPDATE Item
             SET stockItemQty = stockItemQty - ?
             WHERE sparePartNum = ?
               AND stockItemQty >= ?",
            [$quantity, $itemID, $quantity]
        );

        if ($updatedRows < 1) {
            throw new Exception("Insufficient stock for item: {$itemID}");
        }

        // Insert order item
        $this->db->execute(
            "INSERT INTO OrdersItem (orderID, sparePartNum, orderQty, sparePartOrderPrice) VALUES (?, ?, ?, ?)",
            [$orderID, $itemID, $quantity, $price]
        );
    }

    /**
     * Calculate shipping cost for cart items
     *
     * @param array<int, array<string, mixed>|object> $cart
     * @return float
     * @throws Exception
     */
    private function calculateShippingCost(array $cart): float
    {
        if (empty($cart)) {
            return 0.0;
        }

        $totalWeight = 0.0;
        $totalQuantity = 0;

        foreach ($cart as $item) {
            $quantity = $item['quantity'];
            $weight = $item['weight'];
            $totalWeight += $weight * $quantity;
            $totalQuantity += $quantity;
        }

        $shipCostWeight = $this->shipping->calculateShippingCost('weight', max(1.0, $totalWeight));
        $shipCostQuantity = $this->shipping->calculateShippingCost('quantity', $totalQuantity);
        return max($shipCostWeight, $shipCostQuantity);
    }

    /**
     * Get a single order by ID
     *
     * @return array<string, mixed>|null
     */
    public function getOrder(string $orderID): ?array
    {
        return $this->db->fetchOne("SELECT * FROM Orders WHERE orderID = ?", [$orderID]);
    }

    /**
     * Get order total cost including shipping
     */
    public function getOrderOrderTotal(string $orderID): float
    {
        $orderItems = new OrdersItem();
        $items = $orderItems->getOrdersItemsByOrderID($orderID);

        $totalCost = 0.0;
        foreach ($items as $item) {
            $totalCost += ((int)$item['orderQty'] * (float)$item['sparePartOrderPrice']);
        }

        $order = $this->getOrder($orderID);
        if ($order) {
            $totalCost += (float)$order['shipCost'];
        }

        return $totalCost;
    }

    /**
     * Get all orders for a dealer
     *
     * @return array<array<string, mixed>>
     */
    public function getOrderListByDealer(string $dealerID): array
    {
        return $this->fetchOrderList($dealerID);
    }

    /**
     * Get all orders for sales manager view
     *
     * @return array<array<string, mixed>>
     */
    public function getOrderListBySalesManager(): array
    {
        return $this->fetchOrderList(null);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function fetchOrderList(?string $dealerID): array
    {
        $sql = "SELECT o.*,
                    COUNT(oi.sparePartNum) as itemCount,
                    COALESCE(SUM(oi.orderQty * oi.sparePartOrderPrice), 0) + COALESCE(o.shipCost, 0) as totalCost
             FROM Orders o
             LEFT JOIN OrdersItem oi ON o.orderID = oi.orderID";
        $params = [];
        if ($dealerID !== null) {
            $sql .= " WHERE o.dealerID = ?";
            $params[] = $dealerID;
        }
        $sql .= " GROUP BY o.orderID ORDER BY o.orderDateTime DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get order details for dealer
     *
     * @return array<string, mixed>|null
     */
    public function getOrderByIdAndDealer(string $orderID, string $dealerID): ?array
    {
        $order = $this->fetchOrderHeader($orderID, $dealerID);

        return $order === null ? null : $this->buildOrderDetails($order);
    }

    /**
     * Get order details for sales manager
     *
     * @return array<string, mixed>|null
     */
    public function getOrderByIdAndSalesManager(string $orderID): ?array
    {
        $order = $this->fetchOrderHeader($orderID, null);

        return $order === null ? null : $this->buildOrderDetails($order);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchOrderHeader(string $orderID, ?string $dealerID): ?array
    {
        $sql = "SELECT o.*,
                    d.dealerName as dealerCompany,
                    d.contactName as dealerContact,
                    d.contactNumber as dealerPhone,
                    sm.managerName as salesManagerName,
                    sm.contactNumber as salesManagerContact
             FROM Orders o
             LEFT JOIN Dealer d ON o.dealerID = d.dealerID
             LEFT JOIN SalesManager sm ON o.salesManagerID = sm.salesManagerID
             WHERE o.orderID = ?";
        $params = [$orderID];
        if ($dealerID !== null) {
            $sql .= " AND o.dealerID = ?";
            $params[] = $dealerID;
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Build order details with items
     *
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    private function buildOrderDetails(array $order): array
    {
        $orderItems = $this->db->fetchAll(
            "SELECT oi.*, i.sparePartName, i.weight
             FROM OrdersItem oi
             JOIN Item i ON oi.sparePartNum = i.sparePartNum
             WHERE oi.orderID = ?",
            [$order['orderID']]
        );

        $totalAmount = 0.0;
        $totalWeight = 0.0;
        $items = [];

        foreach ($orderItems as $item) {
            $itemTotal = (int)$item['orderQty'] * (float)$item['sparePartOrderPrice'];
            $totalAmount += $itemTotal;
            $totalWeight += (int)$item['orderQty'] * (float)($item['weight'] ?? 0);

            $items[] = [
                'name' => $item['sparePartName'],
                'id' => $item['sparePartNum'],
                'quantity' => (int)$item['orderQty'],
                'price' => (float)$item['sparePartOrderPrice'],
                'total' => $itemTotal,
            ];
        }

        return [
            'summary' => [
                'id' => $order['orderID'],
                'orderDate' => $this->formatDate($order['orderDateTime']),
                'orderTime' => $this->formatTime($order['orderDateTime']),
                'orderStatus' => $order['orderStatus'],
                'deliveryAddress' => $order['deliveryAddress'],
                'deliveryDate' => $this->formatDate($order['deliveryDate']),
                'dealerCompany' => $order['dealerCompany'] ?? 'N/A',
                'dealerContact' => $order['dealerContact'] ?? 'N/A',
                'dealerPhone' => $order['dealerPhone'] ?? 'N/A',
                'salesManagerName' => $order['salesManagerName'] ?? 'N/A',
                'salesManagerContact' => $order['salesManagerContact'] ?? 'N/A',
                'salesManager' => $order['salesManagerName'] ?? 'N/A',
                'salesManagerId' => $order['salesManagerID'] ?? 'N/A',
                'contactPerson' => $order['dealerContact'] ?? 'N/A',
                'contactPhone' => $order['dealerPhone'] ?? 'N/A',
                'totalAmount' => $totalAmount,
                'totalWeight' => $totalWeight,
                'shippingCost' => (float)$order['shipCost'],
                'totalCost' => $totalAmount + (float)$order['shipCost'],
            ],
            'items' => $items,
        ];
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(string $orderID, string $status): ?array
    {
        $allowedStatuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('Invalid order status');
        }

        $order = $this->getOrder($orderID);
        if (null === $order) {
            return null;
        }

        $this->db->execute(
            "UPDATE Orders SET orderStatus = ? WHERE orderID = ?",
            [$status, $orderID]
        );
        return $this->getOrder($orderID);
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(string $orderID): ?array
    {
        return $this->updateOrderStatus($orderID, 'Cancelled');
    }

    /**
     * Assign a sales manager randomly
     */
    private function assignSalesManager(): ?string
    {
        try {
            $managers = $this->db->fetchAll("SELECT salesManagerID FROM SalesManager LIMIT 100");

            if (empty($managers)) {
                error_log('No sales managers available');
                return null;
            }

            $randomIndex = array_rand($managers);
            return $managers[$randomIndex]['salesManagerID'] ?? null;
        } catch (Exception $e) {
            error_log('Sales manager assignment failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Format date string
     */
    private function formatDate(string $dateTime): string
    {
        $timestamp = strtotime($dateTime);
        if ($timestamp === false) {
            return 'N/A';
        }

        return date('d/m/Y', $timestamp);
    }

    /**
     * Format time string
     */
    private function formatTime(string $dateTime): string
    {
        $timestamp = strtotime($dateTime);
        if ($timestamp === false) {
            return 'N/A';
        }

        return date('H:i', $timestamp);
    }

}

/**
 * OrdersItem - Handles order item operations
 */
class OrdersItem
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get a single order item
     *
     * @return array<string, mixed>|null
     */
    public function getOrdersItem(string $orderID, string $sparePartNum): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM OrdersItem WHERE orderID = ? AND sparePartNum = ?",
            [$orderID, $sparePartNum]
        );
    }

    /**
     * Get all items for an order
     *
     * @return array<array<string, mixed>>
     */
    public function getOrdersItemsByOrderID(string $orderID): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM OrdersItem WHERE orderID = ? ORDER BY sparePartNum",
            [$orderID]
        ) ?? [];
    }

    /**
     * Get all order items
     *
     * @return array<array<string, mixed>>
     */
    public function getOrdersItemList(): array
    {
        return $this->db->fetchAll("SELECT * FROM OrdersItem ORDER BY orderID") ?? [];
    }
}
