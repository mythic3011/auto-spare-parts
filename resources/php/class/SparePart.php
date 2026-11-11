<?php
declare(strict_types=1);

require_once 'Database.php';
require_once __DIR__ . '/ProductInOrderException.php';

class SparePart
{
    private DatabaseAdapter $db;

    public function __construct(?DatabaseAdapter $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function getSparePart(mixed $id): ?array
    {
        $sparePartId = $this->normalizeSparePartId($id);
        $result = $this->db->query("SELECT * FROM Item WHERE sparePartNum = ?", [$sparePartId]);

        return $result[0] ?? null;
    }

    public function getSparePartList(): array
    {
        $result = $this->db->query("SELECT Item.*, EXISTS (SELECT 1 FROM OrdersItem WHERE OrdersItem.sparePartNum = Item.sparePartNum) AS deletionLocked FROM Item ORDER BY sparePartNum ASC");
        return is_array($result) ? $result : [];
    }

    public function updateSparePart(mixed $id, array $data): bool
    {
        $sparePartId = $this->normalizeSparePartId($id);
        $allowedFields = ['stockItemQty', 'price', 'sparePartDescription', 'sparePartImage'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        $setClauses = [];
        $params = [];

        foreach ($updateData as $key => $value) {
            if ($key === 'stockItemQty') {
                $value = $this->normalizeNonNegativeInt($value, 'stockItemQty');
            } elseif ($key === 'price') {
                $value = $this->normalizeNonNegativeFloat($value, 'price');
            } elseif ($key === 'sparePartImage') {
                $value = $this->normalizeImageFileName($value);
            } else {
                $value = $this->normalizePlainText($value, $key, false);
            }

            $setClauses[] = "$key = ?";
            $params[] = $value;
        }

        $params[] = $sparePartId;

        $sql = "UPDATE Item SET " . implode(', ', $setClauses) . " WHERE sparePartNum = ?";
        return $this->db->query($sql, $params) !== false;
    }

    public function deleteSparePart(mixed $id): bool
    {
        return $this->batchDeleteSparePart([$id]);
    }

    public function addSparePart(array $data): array
    {
        if (!isset($data['sparePartNum']) || $data['sparePartNum'] === '' || $data['sparePartNum'] === null) {
            throw new InvalidArgumentException('Valid spare part number is required');
        }

        $sparePartNum = $this->normalizeSparePartId($data['sparePartNum']);
        $sparePartCategory = $this->normalizePositiveInt($data['sparePartCategory'] ?? null, 'sparePartCategory');
        $sparePartName = $this->normalizePlainText($data['sparePartName'] ?? '', 'sparePartName', true);
        $weight = $this->normalizeNonNegativeFloat($data['weight'] ?? null, 'weight');
        $stockItemQty = $this->normalizeNonNegativeInt($data['stockItemQty'] ?? null, 'stockItemQty');
        $price = $this->normalizeNonNegativeFloat($data['price'] ?? null, 'price');
        $description = $this->normalizePlainText($data['sparePartDescription'] ?? '', 'sparePartDescription', false);
        $image = $this->normalizeImageFileName($data['sparePartImage'] ?? 'NoImage.jpg');

        if ($this->checkSparePartExists($sparePartNum)) {
            throw new InvalidArgumentException('Spare part already exists');
        }

        $sql = "INSERT INTO Item (sparePartNum, sparePartCategory, sparePartName, weight, stockItemQty, price, sparePartDescription, sparePartImage)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $sparePartNum,
            $sparePartCategory,
            $sparePartName,
            $weight,
            $stockItemQty,
            $price,
            $description,
            $image,
        ];

        $result = $this->db->query($sql, $params);

        if ($result === false) {
            throw new RuntimeException('Failed to add new spare part');
        }

        $insertedItem = $this->getSparePart($sparePartNum);

        if (!$insertedItem) {
            throw new RuntimeException('Failed to retrieve the newly inserted spare part');
        }

        return $insertedItem;
    }

    public function getNewSparePartId(): int
    {
        $result = $this->db->query("SELECT MAX(sparePartNum) as max FROM Item");

        if ($result === false) {
            throw new RuntimeException('Database query failed');
        }

        if (empty($result) || $result[0]['max'] === null) {
            return 1;
        }

        return ((int) $result[0]['max']) + 1;
    }

    public function getSparePartById(mixed $id): ?array
    {
        $result = $this->getSparePart($id);

        if (!$result) {
            return null;
        }

        return [
            'sparePartNum' => $result['sparePartNum'] ?? '',
            'sparePartCategory' => $result['sparePartCategory'] ?? '',
            'sparePartName' => $result['sparePartName'] ?? '',
            'weight' => $result['weight'] ?? 0,
            'stockItemQty' => $result['stockItemQty'] ?? 0,
            'price' => $result['price'] ?? 0,
            'sparePartDescription' => $result['sparePartDescription'] ?? '',
            'sparePartImage' => $result['sparePartImage'] ?? 'NoImage.jpg',
        ];
    }

    public function batchDeleteSparePart(mixed $ids): bool
    {
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_unique(array_map(fn ($id) => $this->normalizeSparePartId($id), $ids)));

        if (empty($ids)) {
            return false;
        }

        sort($ids, SORT_NUMERIC);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->beginTransaction();
        try {
            // Checkout locks these same catalog rows before creating order lines.
            // Acquire them in the same order and retain them through deletion.
            foreach ($ids as $id) {
                $rows = $this->db->query('SELECT sparePartNum FROM Item WHERE sparePartNum = ? FOR UPDATE', [$id]);
                if (!is_array($rows) || $rows === []) {
                    throw new RuntimeException('A selected product no longer exists. Refresh the catalog and try again.', 404);
                }
            }
            $references = $this->db->query(
                "SELECT DISTINCT sparePartNum FROM OrdersItem WHERE sparePartNum IN ($placeholders) ORDER BY sparePartNum FOR UPDATE",
                $ids
            );
            if (!is_array($references)) {
                throw new RuntimeException('Unable to check product order references');
            }
            if ($references !== []) {
                throw new ProductInOrderException(array_map(static fn (array $row): int => (int) $row['sparePartNum'], $references));
            }
            $this->db->query("DELETE FROM Item WHERE sparePartNum IN ($placeholders)", $ids);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    public function checkSparePartExists(mixed $id): bool
    {
        $sparePartId = $this->normalizeSparePartId($id);
        $result = $this->db->query("SELECT COUNT(*) as count FROM Item WHERE sparePartNum = ?", [$sparePartId]);

        return (int) ($result[0]['count'] ?? 0) > 0;
    }

    public function getSpareParts(): array
    {
        return $this->getSparePartList();
    }

    public function getAllSpareParts(): array
    {
        return $this->getSparePartList();
    }

    private function normalizeSparePartId(mixed $id): int
    {
        if (!is_scalar($id)) {
            throw new InvalidArgumentException('Spare part id must be a positive integer');
        }

        $id = trim((string) $id);

        if ($id === '' || !ctype_digit($id) || (int) $id <= 0) {
            throw new InvalidArgumentException('Spare part id must be a positive integer');
        }

        return (int) $id;
    }

    private function normalizePositiveInt(mixed $value, string $field): int
    {
        if (!is_scalar($value)) {
            throw new InvalidArgumentException($field . ' must be a positive integer');
        }

        $value = trim((string) $value);

        if ($value === '' || !ctype_digit($value) || (int) $value <= 0) {
            throw new InvalidArgumentException($field . ' must be a positive integer');
        }

        return (int) $value;
    }

    private function normalizeNonNegativeInt(mixed $value, string $field): int
    {
        if (!is_scalar($value)) {
            throw new InvalidArgumentException($field . ' must be a non-negative integer');
        }

        $value = trim((string) $value);

        if ($value === '' || !ctype_digit($value) || (int) $value < 0) {
            throw new InvalidArgumentException($field . ' must be a non-negative integer');
        }

        return (int) $value;
    }

    private function normalizeNonNegativeFloat(mixed $value, string $field): float
    {
        if (!is_scalar($value)) {
            throw new InvalidArgumentException($field . ' must be a non-negative number');
        }

        $value = trim((string) $value);

        if ($value === '' || !is_numeric($value) || (float) $value < 0) {
            throw new InvalidArgumentException($field . ' must be a non-negative number');
        }

        return (float) $value;
    }

    private function normalizePlainText(mixed $value, string $field, bool $required): string
    {
        $text = trim((string) $value);
        if ($text === '' && !$required) {
            return '';
        }

        if ($text === '' || preg_match('/[<>]/', $text) === 1) {
            throw new InvalidArgumentException('Invalid ' . $field);
        }

        return $text;
    }

    private function normalizeImageFileName(mixed $value): string
    {
        $fileName = basename(trim((string) $value));

        if ($fileName === '') {
            return 'NoImage.jpg';
        }

        return preg_match('/^[A-Za-z0-9._-]+$/', $fileName) ? $fileName : 'NoImage.jpg';
    }
}
