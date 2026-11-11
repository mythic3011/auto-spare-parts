<?php

require_once("Database.php");
require_once("Session.php");
require_once("SparePart.php");

class Cart
{
    public $cartId;
    private $cartItems = [];
    private $sparePart;
    /**
     * @var array{cartId?: string, cartItems?: array<int, mixed>}|null
     */
    private static $memoryCartState = null;

    public function __construct()
    {
        $this->bootSessionState();
        $this->sparePart = new SparePart();
    }

    private function bootSessionState(): void
    {
        Session::begin();

        $sessionCart = $this->getSessionCartData();

        if ($sessionCart === null) {
            $this->cartId = uniqid('', true);
            $this->cartItems = [];
            $this->persistSessionState();
            return;
        }

        $this->cartId = (string) ($sessionCart['cartId'] ?? uniqid('', true));
        $this->cartItems = $this->hydrateCartItems($sessionCart['cartItems'] ?? []);
        $this->persistSessionState();
    }

    /**
     * @return array{cartId?: string, cartItems?: array<int, mixed>}|null
     */
    private function getSessionCartData(): ?array
    {
        if (!isset($_SESSION) || !is_array($_SESSION) || session_status() !== PHP_SESSION_ACTIVE) {
            if (is_array(self::$memoryCartState)) {
                return [
                    'cartId' => (string) (self::$memoryCartState['cartId'] ?? ''),
                    'cartItems' => self::$memoryCartState['cartItems'] ?? [],
                ];
            }

            return null;
        }

        foreach (['cart', 'cart_data'] as $key) {
            if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
                $sessionCart = $_SESSION[$key];

                return [
                    'cartId' => (string) ($sessionCart['cartId'] ?? $sessionCart['cart_id'] ?? $_SESSION['cartId'] ?? $_SESSION['cart_id'] ?? ''),
                    'cartItems' => $sessionCart['cartItems'] ?? $sessionCart['cart_items'] ?? $sessionCart['items'] ?? [],
                ];
            }
        }

        $cartItems = $_SESSION['cartItems'] ?? $_SESSION['cart_items'] ?? null;
        $cartId = $_SESSION['cartId'] ?? $_SESSION['cart_id'] ?? null;

        if ($cartId !== null || $cartItems !== null) {
            return [
                'cartId' => (string) ($cartId ?? ''),
                'cartItems' => is_array($cartItems) ? $cartItems : [],
            ];
        }

        return null;
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, CartItem>
     */
    private function hydrateCartItems(array $items): array
    {
        $cartItems = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $itemID = $item['itemID'] ?? $item['id'] ?? $item['partId'] ?? $item['sparePartNum'] ?? null;
                $quantity = $item['quantity'] ?? $item['qty'] ?? 1;
            } elseif (is_object($item)) {
                $itemID = $item->itemID ?? $item->id ?? $item->partId ?? $item->sparePartNum ?? null;
                $quantity = $item->quantity ?? $item->qty ?? 1;
            } else {
                continue;
            }

            if ($itemID === null || $itemID === '') {
                continue;
            }

            $cartItems[] = new CartItem((string) $itemID, (int) $quantity);
        }

        return $cartItems;
    }

    /**
     * @return array<int, array{itemID: string, quantity: int}>
     */
    private function exportCartItems(): array
    {
        return array_map(
            static function (CartItem $item): array {
                return [
                    'itemID' => (string) $item->itemID,
                    'quantity' => (int) $item->quantity,
                ];
            },
            $this->cartItems
        );
    }

    private function persistSessionState(): void
    {
        $payload = [
            'cartId' => (string) $this->cartId,
            'cartItems' => $this->exportCartItems(),
        ];
        self::$memoryCartState = $payload;

        if (session_status() === PHP_SESSION_NONE) {
            return;
        }

        $_SESSION['cartId'] = $payload['cartId'];
        $_SESSION['cart_id'] = $payload['cartId'];
        $_SESSION['cartItems'] = $payload['cartItems'];
        $_SESSION['cart_items'] = $payload['cartItems'];
        $_SESSION['cart'] = $payload;
    }

    public function addItem($itemID, $quantity)
    {
        $sparePartData = $this->sparePart->getSparePart($itemID);
        if (!$sparePartData) {
            throw new Exception("Item not found: $itemID");
        }

        if ($sparePartData['stockItemQty'] < $quantity) {
            throw new Exception("Not enough stock for item: $itemID");
        }

        $existingItem = $this->getCartItem($itemID);
        if ($existingItem) {
            $existingItem->quantity += $quantity;
        } else {
            $this->cartItems[] = new CartItem($itemID, $quantity);
        }

        $this->persistSessionState();
        return true;
    }

    public function getCartItems()
    {
        $this->refreshStateFromStore();
        return $this->cartItems;
    }

    public function getCartItem($itemID)
    {
        $this->refreshStateFromStore();
        foreach ($this->cartItems as $cartItem) {
            if ($cartItem->itemID == $itemID) {
                return $cartItem;
            }
        }
        return null;
    }

    public function updateQuantity($itemID, $quantity)
    {
        $sparePartData = $this->sparePart->getSparePart($itemID);
        if (!$sparePartData) {
            throw new Exception("Item not found: $itemID");
        }

        if ($sparePartData['stockItemQty'] < $quantity) {
            throw new Exception("Not enough stock for item: $itemID");
        }

        $cartItem = $this->getCartItem($itemID);
        if ($cartItem) {
            $cartItem->quantity = $quantity;
            $this->persistSessionState();
            return $cartItem;
        } else {
            throw new Exception("Item not found in cart: $itemID");
        }
    }

    public function removeItem($itemID)
    {
        $this->cartItems = array_filter($this->cartItems, function ($item) use ($itemID) {
            return $item->itemID != $itemID;
        });
        $this->cartItems = array_values($this->cartItems);
        $this->persistSessionState();
    }

    public function getCartTotal()
    {
        $this->refreshStateFromStore();
        $total = 0;
        foreach ($this->cartItems as $item) {
            $sparePartData = $this->sparePart->getSparePart($item->itemID);
            if ($sparePartData) {
                $total += $sparePartData['price'] * $item->quantity;
            }
        }
        return $total;
    }

    public function clearCart()
    {
        $this->cartItems = [];
        $this->persistSessionState();
    }

    public static function resetStateForTesting(): void
    {
        self::$memoryCartState = null;
    }

    public function getCartId()
    {
        return $this->cartId;
    }

    public function checkStock($itemID)
    {
        $this->refreshStateFromStore();
        $sparePartData = $this->sparePart->getSparePart($itemID);
        if (!$sparePartData) {
            throw new Exception("Item not found: $itemID");
        }

        $cartItem = $this->getCartItem($itemID);
        $cartQuantity = $cartItem ? $cartItem->quantity : 0;

        return [
            'stockItemQty' => $sparePartData['stockItemQty'],
            'cartQuantity' => $cartQuantity,
            'isAvailable' => $sparePartData['stockItemQty'] >= $cartQuantity
        ];
    }

    private function refreshStateFromStore(): void
    {
        $sessionCart = $this->getSessionCartData();
        if ($sessionCart === null) {
            return;
        }

        $nextCartId = (string) ($sessionCart['cartId'] ?? '');
        if ($nextCartId !== '') {
            $this->cartId = $nextCartId;
        }

        $this->cartItems = $this->hydrateCartItems($sessionCart['cartItems'] ?? []);
    }

    public function calculateShippingCost($cart)
    {
        $cartItems = [];
        $totalWeight = 0;
        $totalQuantity = 0;

        foreach ($cart as $item) {
            $cartItems[] = [
                'id' => $item->itemID,
                'name' => $item->itemName,
                'price' => $item->itemPrice,
                'quantity' => $item->quantity,
                'totalPrice' => $item->getTotalPrice(),
            ];
            $totalWeight += $item->weight * $item->quantity;
            $totalQuantity += $item->quantity;
        }

        $shipCostWeight = $this->getShippingCost('weight', $totalWeight);
        $shipCostQuantity = $this->getShippingCost('quantity', $totalQuantity);

        // Return the higher of the two costs
        return max($shipCostWeight, $shipCostQuantity);
    }

    // call api to get shipping cost
    private function getShippingCost($mode, $value)
    {
        $apiUrl = "http://127.0.0.1:8080/ship_cost_api/{$mode}/{$value}";

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set a timeout of 10 seconds

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error calling shipping cost API: ' . curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (!$result || !isset($result['result'])) {
            throw new Exception('Invalid response from shipping cost API');
        }

        if ($result['result'] === 'rejected') {
            throw new Exception('Shipping cost calculation rejected: ' . ($result['reason'] ?? 'Unknown reason'));
        }

        return $result['cost'] ?? 0;
    }
    public function getTotalWeight(){
        $totalWeight = 0;
        foreach ($this->cartItems as $item) {
            $sparePartData = $this->sparePart->getSparePart($item->itemID);
            if ($sparePartData) {
                $totalWeight += $sparePartData['weight'] * $item->quantity;
            }
        }
        return $totalWeight;
    }
}

class CartItem
{
    public $itemID;
    public $quantity;

    public function __construct($itemID, $quantity)
    {
        $this->itemID = $itemID;
        $this->quantity = $quantity;
    }

    public function getItemDetails()
    {
        $sparePart = new SparePart();
        return $sparePart->getSparePart($this->itemID);
    }

    public function getTotalPrice()
    {
        $details = $this->getItemDetails();
        return $details ? $details['price'] * $this->quantity : 0;
    }

}
?>
