import math
import os
import time
from collections import defaultdict, deque
from http import HTTPStatus

from flask import Flask, jsonify, request

app = Flask(__name__)

app.config["MAX_CONTENT_LENGTH"] = int(os.getenv("MAX_CONTENT_LENGTH_BYTES", "65536"))

RATE_LIMIT_WINDOW_SECONDS = int(os.getenv("RATE_LIMIT_WINDOW_SECONDS", "60"))
RATE_LIMIT_MAX_REQUESTS = int(os.getenv("RATE_LIMIT_MAX_REQUESTS", "60"))
RATE_LIMIT_BUCKETS = defaultdict(deque)

# In-memory demo order store. Replace this with database-backed order access in production.
orders = {}

SHIPPING_CONFIG = {
    "weight": {
        "unit": "kg",
        "initial_cost": 300,
        "initial_limit": 1,
        "additional_cost": 50,
        "max_limit": 70,
        "decimal_places": 3,
    },
    "quantity": {
        "unit": "item",
        "initial_cost": 300,
        "initial_limit": 1,
        "additional_cost": 60,
        "max_limit": 30,
        "decimal_places": 0,
    },
}

SHIPPING_DEFAULT_MODE = os.getenv("SHIPPING_DEFAULT_MODE", "weight").lower().strip()
SHIPPING_SAFE_MODE = SHIPPING_DEFAULT_MODE if SHIPPING_DEFAULT_MODE in SHIPPING_CONFIG else "weight"


def get_client_ip():
    forwarded_for = request.headers.get("X-Forwarded-For", "")

    if forwarded_for:
        return forwarded_for.split(",", 1)[0].strip()

    return request.remote_addr or "unknown"


def is_rate_limited(client_key):
    now = time.monotonic()
    bucket = RATE_LIMIT_BUCKETS[client_key]

    while bucket and now - bucket[0] > RATE_LIMIT_WINDOW_SECONDS:
        bucket.popleft()

    if len(bucket) >= RATE_LIMIT_MAX_REQUESTS:
        retry_after = max(1, math.ceil(RATE_LIMIT_WINDOW_SECONDS - (now - bucket[0])))
        return True, retry_after

    bucket.append(now)
    return False, 0


def error_response(message, status_code=HTTPStatus.BAD_REQUEST, extra=None):
    payload = {"result": "failure", "message": message}

    if extra:
        payload.update(extra)

    response = jsonify(payload)
    response.status_code = status_code
    return response


def success_response(payload=None, status_code=HTTPStatus.OK):
    data = {"result": "success"}

    if payload:
        data.update(payload)

    response = jsonify(data)
    response.status_code = status_code
    return response


@app.before_request
def apply_rate_limit():
    if request.endpoint == "health":
        return None

    client_key = f"{get_client_ip()}:{request.endpoint or request.path}"
    limited, retry_after = is_rate_limited(client_key)

    if not limited:
        return None

    response = error_response(
        "Too many requests. Please try again later.",
        HTTPStatus.TOO_MANY_REQUESTS,
        {"retry_after_seconds": retry_after},
    )
    response.headers["Retry-After"] = str(retry_after)
    return response


@app.after_request
def add_security_headers(response):
    response.headers.setdefault("X-Content-Type-Options", "nosniff")
    response.headers.setdefault("X-Frame-Options", "DENY")
    response.headers.setdefault("Referrer-Policy", "no-referrer")
    response.headers.setdefault("Cache-Control", "no-store")
    return response


@app.errorhandler(HTTPStatus.NOT_FOUND)
def handle_not_found(error):
    return error_response("Endpoint not found.", HTTPStatus.NOT_FOUND)


@app.errorhandler(HTTPStatus.METHOD_NOT_ALLOWED)
def handle_method_not_allowed(error):
    return error_response("Method not allowed.", HTTPStatus.METHOD_NOT_ALLOWED)


@app.errorhandler(HTTPStatus.REQUEST_ENTITY_TOO_LARGE)
def handle_request_entity_too_large(error):
    return error_response("Request body is too large.", HTTPStatus.REQUEST_ENTITY_TOO_LARGE)


@app.errorhandler(Exception)
def handle_unexpected_error(error):
    app.logger.exception("Unhandled API error")
    return error_response("Internal server error.", HTTPStatus.INTERNAL_SERVER_ERROR)



def normalize_shipping_value(mode, value):
    mode = str(mode).lower().strip()

    if mode not in SHIPPING_CONFIG:
        return None, f"Mode must be one of: {', '.join(sorted(SHIPPING_CONFIG))}."

    config = SHIPPING_CONFIG[mode]

    try:
        numeric_value = float(value)
    except (TypeError, ValueError):
        return None, f"{mode.capitalize()} must be a numeric value."

    if not math.isfinite(numeric_value):
        return None, f"{mode.capitalize()} must be a finite numeric value."

    if numeric_value <= 0:
        return None, f"{mode.capitalize()} must be greater than 0."

    if mode == "quantity" and not numeric_value.is_integer():
        return None, "Quantity must be a whole number."

    numeric_value = round(numeric_value, config["decimal_places"])

    if numeric_value > config["max_limit"]:
        return None, f"Maximum {mode} per package is {config['max_limit']} {config['unit']}."

    return numeric_value, None


def calculate_shipping_cost_from_weight(weight):
    return calculate_shipping_cost("weight", weight)


def calculate_shipping_cost_from_quantity(quantity):
    return calculate_shipping_cost("quantity", quantity)


def calculate_shipping_cost(mode, value):
    mode = str(mode).lower().strip()
    numeric_value, error_message = normalize_shipping_value(mode, value)

    if error_message:
        return {
            "result": "rejected",
            "reason": error_message,
            "mode": mode,
        }

    config = SHIPPING_CONFIG[mode]
    cost = config["initial_cost"]

    if numeric_value > config["initial_limit"]:
        additional_units = math.ceil(numeric_value - config["initial_limit"])
        cost += additional_units * config["additional_cost"]

    return {
        "result": "accepted",
        "mode": mode,
        "value": numeric_value,
        "unit": config["unit"],
        "cost": cost,
        "currency": "HKD",
        "breakdown": {
            "initial_cost": config["initial_cost"],
            "initial_limit": config["initial_limit"],
            "additional_cost": config["additional_cost"],
            "chargeable_additional_units": max(0, math.ceil(numeric_value - config["initial_limit"])),
            "max_limit": config["max_limit"],
        },
    }


@app.route("/ship_cost_api/<mode>/<value>", methods=["GET"])
def ship_cost_api(mode, value):
    result = calculate_shipping_cost(mode, value)
    status_code = HTTPStatus.OK if result["result"] == "accepted" else HTTPStatus.BAD_REQUEST
    response = jsonify(result)
    response.status_code = status_code
    return response


# JSON POST API for shipping cost
@app.route("/ship_cost_api", methods=["POST"])
def ship_cost_api_json():
    data = request.get_json(silent=True)

    if not isinstance(data, dict):
        return error_response("Request body must be valid JSON.")

    mode = str(data.get("mode", SHIPPING_SAFE_MODE)).lower().strip()
    value = data.get("value")

    if value is None and mode == "weight":
        value = data.get("weight")

    if value is None and mode == "quantity":
        value = data.get("quantity")

    if value is None:
        return error_response("Shipping value is required.")

    result = calculate_shipping_cost(mode, value)
    status_code = HTTPStatus.OK if result["result"] == "accepted" else HTTPStatus.BAD_REQUEST
    response = jsonify(result)
    response.status_code = status_code
    return response


@app.route("/update_order", methods=["POST"])
def update_order():
    data = request.get_json(silent=True)

    if not isinstance(data, dict):
        return error_response("Request body must be valid JSON.")

    order_id = str(data.get("order_id", "")).strip()
    sales_manager_id = str(data.get("sales_manager_id", "")).strip()
    order_status = str(data.get("order_status", "")).strip()

    if not order_id:
        return error_response("order_id is required.")

    if order_id not in orders:
        return error_response("Order not found.", HTTPStatus.NOT_FOUND)

    if not sales_manager_id:
        return error_response("sales_manager_id is required.")

    if not order_status:
        return error_response("order_status is required.")

    order = orders[order_id]
    order["sales_manager_id"] = sales_manager_id
    order["order_status"] = order_status

    shipping_mode = str(order.get("shipping_mode") or SHIPPING_SAFE_MODE).lower().strip()
    shipping_value = order.get("total_weight") if shipping_mode == "weight" else order.get("order_quantity")
    shipping_cost_data = calculate_shipping_cost(shipping_mode, shipping_value)

    if shipping_cost_data["result"] != "accepted":
        return error_response(shipping_cost_data["reason"])

    order["shipping_cost"] = shipping_cost_data["cost"]

    return success_response({"order": order})


@app.route("/health", methods=["GET"])
def health():
    return success_response({
        "status": "ok",
        "shipping_default_mode": SHIPPING_SAFE_MODE,
        "shipping_modes": sorted(SHIPPING_CONFIG.keys()),
    })


@app.route("/api/", methods=["GET"])
def api():
    return success_response({"message": "Hello from the API!"})


@app.route("/", methods=["GET"])
def index():
    return error_response("Allowed only from /api/.", HTTPStatus.NOT_FOUND)


if __name__ == "__main__":
    debug_enabled = os.getenv("FLASK_DEBUG", "0") == "1"
    app.run(host="127.0.0.1", port=8080, debug=debug_enabled)
