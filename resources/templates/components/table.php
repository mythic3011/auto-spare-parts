<?php
/**
 * Table Component
 * 
 * Usage:
 * $tableData = [
 *     'columns' => [
 *         ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
 *         ['key' => 'name', 'label' => 'Name'],
 *         ['key' => 'email', 'label' => 'Email'],
 *         ['key' => 'status', 'label' => 'Status', 'formatter' => 'statusBadge'],
 *     ],
 *     'rows' => [
 *         ['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'status' => 'active'],
 *         ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com', 'status' => 'inactive'],
 *     ],
 *     'striped' => true,
 *     'hoverable' => true,
 *     'sortable' => true,
 *     'paginate' => false,
 *     'rowsPerPage' => 10,
 *     'class' => 'additional-classes'
 * ];
 */

$columns = isset($tableData['columns']) ? $tableData['columns'] : [];
$rows = isset($tableData['rows']) ? $tableData['rows'] : [];
$striped = isset($tableData['striped']) ? $tableData['striped'] : true;
$hoverable = isset($tableData['hoverable']) ? $tableData['hoverable'] : true;
$sortable = isset($tableData['sortable']) ? $tableData['sortable'] : false;
$paginate = isset($tableData['paginate']) ? $tableData['paginate'] : false;
$rowsPerPage = isset($tableData['rowsPerPage']) ? $tableData['rowsPerPage'] : 10;
$additionalClass = isset($tableData['class']) ? htmlspecialchars($tableData['class']) : '';

$striped_class = $striped ? 'odd:bg-gray-50 dark:odd:bg-gray-800' : '';
$hover_class = $hoverable ? 'hover:bg-gray-100 dark:hover:bg-gray-700 transition' : '';
?>

<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 <?php echo $additionalClass; ?>">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <!-- Header -->
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-semibold">
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <?php foreach ($columns as $column): ?>
                    <?php
                    $label = htmlspecialchars($column['label'] ?? '');
                    $width = isset($column['width']) ? 'style="width:' . htmlspecialchars($column['width']) . '"' : '';
                    $sortAttr = $sortable ? 'class="cursor-pointer select-none" onclick="sortTable(this)"' : '';
                    ?>
                    <th scope="col" class="px-6 py-3" <?php echo $width; ?> <?php echo $sortAttr; ?>>
                        <?php echo $label; ?>
                        <?php if ($sortable): ?>
                            <span class="ml-2 text-xs">⇅</span>
                        <?php endif; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        
        <!-- Body -->
        <tbody>
            <?php if (empty($rows)): ?>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 italic" 
                        colspan="<?php echo count($columns); ?>">
                        No data available
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach (array_slice($rows, 0, $paginate ? $rowsPerPage : count($rows)) as $row): ?>
                    <tr class="border-b border-gray-200 dark:border-gray-700 <?php echo $striped_class; ?> <?php echo $hover_class; ?>">
                        <?php foreach ($columns as $column): ?>
                            <?php
                            $key = $column['key'] ?? '';
                            $value = isset($row[$key]) ? $row[$key] : '';
                            
                            // Apply formatter if provided
                            if (isset($column['formatter']) && is_callable($column['formatter'])) {
                                $value = call_user_func($column['formatter'], $value);
                            } elseif (isset($column['formatter']) && is_string($column['formatter'])) {
                                // Handle built-in formatters
                                $value = formatTableCell($column['formatter'], $value);
                            } else {
                                $value = htmlspecialchars($value);
                            }
                            ?>
                            <td class="px-6 py-4 text-gray-900 dark:text-white">
                                <?php echo $value; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($paginate && count($rows) > $rowsPerPage): ?>
    <div class="flex justify-between items-center mt-4 text-sm text-gray-600 dark:text-gray-400">
        <span>Showing 1 to <?php echo min($rowsPerPage, count($rows)); ?> of <?php echo count($rows); ?> results</span>
        <div class="flex gap-2">
            <button class="px-3 py-1 border rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition">← Previous</button>
            <button class="px-3 py-1 border rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition">Next →</button>
        </div>
    </div>
<?php endif; ?>

<script>
    function formatTableCell(formatter, value) {
        switch(formatter) {
            case 'statusBadge':
                const color = value === 'active' ? 'success' : 'warning';
                return `<span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-${color}-100 text-${color}-800">${value}</span>`;
            case 'currency':
                return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
            case 'date':
                return new Date(value).toLocaleDateString();
            default:
                return value;
        }
    }
    
    function sortTable(header) {
        // Implementation for table sorting
        console.log('Sort by:', header.textContent.trim());
    }
</script>
