# Order API Reference

## Base URL

```
http://localhost/resources/php/order_api.php
```

## Authentication

All requests require an Authorization header with a valid JWT token:

```
Authorization: {token}
```

## Response Format

### Success Response (200, 201)

```json
{
  "status": "success",
  "message": "Operation successful",
  "timestamp": "2024-01-14 12:00:00",
  "data": {}
}
```

### Error Response (400, 401, 403, 404, 422, 500)

```json
{
  "status": "error",
  "message": "Error description",
  "timestamp": "2024-01-14 12:00:00",
  "data": {
    "errors": {}
  }
}
```

### Validation Error (422)

```json
{
  "status": "error",
  "message": "Validation failed",
  "timestamp": "2024-01-14 12:00:00",
  "data": {
    "errors": {
      "field_name": ["Error message"],
      "another_field": ["Error 1", "Error 2"]
    }
  }
}
```

## Endpoints

### GET /order_api.php?action=dealer-list

Get all orders for current dealer

**Headers:**

- Authorization: {token}

**Response:**

```json
{
  "status": "success",
  "message": "Orders retrieved successfully",
  "timestamp": "2024-01-14 12:00:00",
  "data": [
    {
      "orderID": "1",
      "dealerID": "1",
      "orderDateTime": "2024-01-14 10:00:00",
      "deliveryDate": "2024-01-21 10:00:00",
      "orderStatus": "Pending",
      "shipCost": 50.0,
      "itemCount": 3,
      "totalCost": 250.0
    }
  ]
}
```

---

### GET /order_api.php?action=manager-list

Get all orders (sales manager only)

**Headers:**

- Authorization: {token}

**Response:** Same as dealer-list (includes all orders)

---

### GET /order_api.php?action=dealer-get&id={orderID}

Get order details for dealer

**Parameters:**

- id (required): Order ID

**Response:**

```json
{
  "status": "success",
  "message": "Order retrieved successfully",
  "timestamp": "2024-01-14 12:00:00",
  "data": {
    "summary": {
      "id": "1",
      "orderDate": "14/01/2024",
      "orderTime": "10:00",
      "orderStatus": "Pending",
      "deliveryAddress": "123 Main St, City",
      "deliveryDate": "21/01/2024",
      "dealerCompany": "ABC Motors",
      "dealerContact": "John Smith",
      "dealerPhone": "555-0123",
      "salesManagerName": "Jane Doe",
      "salesManagerContact": "555-0456",
      "totalAmount": 200.0,
      "totalWeight": 50.5,
      "shippingCost": 50.0,
      "totalCost": 250.0
    },
    "items": [
      {
        "name": "Oil Filter",
        "id": "PART001",
        "quantity": 2,
        "price": 25.0,
        "total": 50.0
      }
    ]
  }
}
```

---

### GET /order_api.php?action=manager-get&id={orderID}

Get order details (sales manager only)

**Parameters:**

- id (required): Order ID

**Response:** Same format as dealer-get

---

### GET /order_api.php?action=stock-report

Get stock report

**Headers:**

- Authorization: {token}

**Response:**

```json
{
  "status": "success",
  "message": "Stock report retrieved successfully",
  "timestamp": "2024-01-14 12:00:00",
  "data": [
    {
      "sparePartNum": "PART001",
      "sparePartName": "Oil Filter",
      "stockItemQty": 150,
      "category": "Filters"
    }
  ]
}
```

---

### POST /order_api.php?action=place-order

Place a new order

**Headers:**

- Authorization: {token}
- Content-Type: application/json

**Request Body:**

```json
{
  "cart": [
    {
      "itemID": "PART001",
      "quantity": 2,
      "price": 25.0,
      "weight": 0.5
    },
    {
      "itemID": "PART002",
      "quantity": 1,
      "price": 150.0,
      "weight": 1.0
    }
  ],
  "deliveryAddress": "123 Main St, City, State, Country"
}
```

**Response (201):**

```json
{
  "status": "success",
  "message": "Order placed successfully",
  "timestamp": "2024-01-14 12:00:00",
  "data": {
    "id": "1"
  }
}
```

**Validation Errors (422):**

```json
{
  "status": "error",
  "message": "Validation failed",
  "timestamp": "2024-01-14 12:00:00",
  "data": {
    "errors": {
      "cart": ["Cart is required"],
      "deliveryAddress": ["Delivery address is required"]
    }
  }
}
```

---

### POST /order_api.php?action=update-status

Update order status

**Headers:**

- Authorization: {token}
- Content-Type: application/json

**Request Body:**

```json
{
  "id": "1",
  "status": "Confirmed"
}
```

**Valid Statuses:**

- Pending
- Confirmed
- Shipped
- Delivered
- Cancelled

**Response (200):**

```json
{
  "status": "success",
  "message": "Order status updated successfully",
  "timestamp": "2024-01-14 12:00:00",
  "data": {
    "orderID": "1",
    "orderStatus": "Confirmed"
  }
}
```

---

### POST /order_api.php?action=cancel

Cancel an order

**Headers:**

- Authorization: {token}
- Content-Type: application/json

**Request Body:**

```json
{
  "id": "1"
}
```

**Response (200):**

```json
{
  "status": "success",
  "message": "Order cancelled successfully",
  "timestamp": "2024-01-14 12:00:00",
  "data": {
    "orderID": "1",
    "orderStatus": "Cancelled"
  }
}
```

---

## Error Codes

| Code | Type                  | Description                              |
| ---- | --------------------- | ---------------------------------------- |
| 200  | OK                    | Request successful                       |
| 201  | Created               | Resource created successfully            |
| 400  | Bad Request           | Invalid input or missing required fields |
| 401  | Unauthorized          | No or invalid authentication token       |
| 403  | Forbidden             | Insufficient permissions                 |
| 404  | Not Found             | Resource not found                       |
| 405  | Method Not Allowed    | Wrong HTTP method                        |
| 422  | Unprocessable Entity  | Validation failed                        |
| 500  | Internal Server Error | Server error                             |

---

## Cart Item Format

Each item in the cart must have:

- `itemID` (string): Spare part identifier
- `quantity` (integer): Number of items (must be > 0)
- `price` (float): Unit price (must be >= 0)
- `weight` (float, optional): Item weight for shipping calculation

---

## Examples Using cURL

### Get Dealer Orders

```bash
curl -X GET "http://localhost/resources/php/order_api.php?action=dealer-list" \
  -H "Authorization: Bearer ${AUTH_TOKEN:?Set AUTH_TOKEN}"
```

### Place Order

```bash
curl -X POST "http://localhost/resources/php/order_api.php?action=place-order" \
  -H "Authorization: Bearer ${AUTH_TOKEN:?Set AUTH_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "cart": [
      {"itemID": "PART001", "quantity": 2, "price": 25.00, "weight": 0.5}
    ],
    "deliveryAddress": "123 Main St, City"
  }'
```

### Update Order Status

```bash
curl -X POST "http://localhost/resources/php/order_api.php?action=update-status" \
  -H "Authorization: Bearer ${AUTH_TOKEN:?Set AUTH_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"id": "1", "status": "Confirmed"}'
```

### Cancel Order

```bash
curl -X POST "http://localhost/resources/php/order_api.php?action=cancel" \
  -H "Authorization: Bearer ${AUTH_TOKEN:?Set AUTH_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"id": "1"}'
```

---

## Common Issues

### "No authentication token provided"

- Solution: Add Authorization header with valid token

### "Order not found or you do not have permission"

- Solution: Verify order belongs to current dealer/manager

### "Cart cannot be empty"

- Solution: Ensure cart array contains at least one item

### "Insufficient stock"

- Solution: Check available stock via stock-report endpoint

### "Invalid order status"

- Solution: Use one of: Pending, Confirmed, Shipped, Delivered, Cancelled

---

## Changelog

### v2.0.0 (Jan 2026)

- Improved API response format with standardized structure
- Added validation error details
- Fixed order listing performance
- Better error messages
- Added proper HTTP status codes

### v1.0.0 (Original)

- Basic order management endpoints
