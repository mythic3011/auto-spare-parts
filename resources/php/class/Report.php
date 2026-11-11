<?php

require_once 'Database.php';

class Report {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getStockReport() {
        $result = $this->db->query("SELECT
    CAST(i.sparePartNum AS CHAR) AS partNumber,
    i.sparePartName AS partName,
    CONCAT('../../resources/image/spare/', i.sparePartImage) AS partImage,
    GROUP_CONCAT(CAST(sales.orderID AS CHAR)) AS orderNumbers,
    GROUP_CONCAT(CAST(sales.orderQty AS CHAR)) AS orderQuantities,
    CAST(SUM(sales.orderQty * sales.sparePartOrderPrice) AS CHAR) AS totalSalesAmount
FROM Item i
LEFT JOIN (
    SELECT oi.sparePartNum, o.orderID, oi.orderQty, oi.sparePartOrderPrice
    FROM OrdersItem oi
    INNER JOIN Orders o ON o.orderID = oi.orderID
) sales ON CAST(i.sparePartNum AS CHAR) = sales.sparePartNum
GROUP BY i.sparePartNum, i.sparePartName, i.sparePartImage");
        return $result ? $result : [];
    }
}
