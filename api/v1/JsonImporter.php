<?php

require_once __DIR__ . '/ImporterInterface.php';

/**
 * Class JsonImporter
 * Implements BomImporterInterface for parsing JSON files.
 */
class JsonImporter implements BomImporterInterface {
    /**
     * Parse the given JSON file.
     *
     * @param string $filePath
     * @return array Mapped rows containing part details.
     * @throws Exception If file reading or parsing fails.
     */
    public function parse(string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("File does not exist or is not readable: " . basename($filePath));
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if ($data === null) {
            throw new Exception("Invalid JSON formatting: " . json_last_error_msg());
        }

        $parsedParts = [];
        
        // Check if this is the edge-list-1 format
        if (is_array($data) && isset($data['format_version']) && $data['format_version'] === 'edge-list-1') {
            return $this->parseEdgeList($data);
        }

        // Handle root-level array or product-wrapped structure (fallback)
        $items = [];
        if (is_array($data)) {
            if (isset($data['bom']) && is_array($data['bom'])) {
                $items = $data['bom'];
            } elseif (isset($data['parts']) && is_array($data['parts'])) {
                $items = $data['parts'];
            } else {
                $items = $data;
            }
        }

        $this->traverse($items, null, $parsedParts);

        return $parsedParts;
    }

    /**
     * Parse the edge-list-1 format.
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    private function parseEdgeList(array $data): array {
        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
        $relationships = isset($data['relationships']) && is_array($data['relationships']) ? $data['relationships'] : [];

        // Build parent-to-child adjacency list and child-to-parent list (to find roots)
        $adjacencyList = [];
        $hasParent = [];

        foreach ($relationships as $rel) {
            if (!is_array($rel)) {
                continue;
            }
            $parent = isset($rel['parent']) ? trim((string)$rel['parent']) : '';
            $child = isset($rel['child']) ? trim((string)$rel['child']) : '';
            $qty = isset($rel['qty']) ? (float)$rel['qty'] : 1.0;
            $ref = isset($rel['ref']) && trim((string)$rel['ref']) !== '' ? trim((string)$rel['ref']) : null;
            $unit = isset($rel['unit']) && trim((string)$rel['unit']) !== '' ? trim((string)$rel['unit']) : 'ea';

            if ($parent === '' || $child === '') {
                continue;
            }

            $adjacencyList[$parent][] = [
                'child' => $child,
                'qty' => $qty,
                'ref' => $ref,
                'unit' => $unit
            ];

            $hasParent[$child] = true;
        }

        // Identify roots: items that do not have a parent
        $roots = [];
        
        // Check items first to preserve order/existence
        foreach ($items as $partNo => $name) {
            $partNo = trim((string)$partNo);
            if ($partNo !== '' && !isset($hasParent[$partNo])) {
                $roots[] = $partNo;
            }
        }

        // Check adjacency list keys that might not be in $items but are not children
        foreach (array_keys($adjacencyList) as $parent) {
            if ($parent !== '' && !isset($hasParent[$parent]) && !in_array($parent, $roots, true)) {
                $roots[] = $parent;
            }
        }

        $parsedParts = [];
        $visited = [];

        // Recursive helper to traverse the tree/graph top-down
        $traverse = function (string $partNo, ?string $parentPartNo, float $qty, ?string $ref, string $unit) use (
            &$traverse,
            &$parsedParts,
            &$visited,
            $items,
            $adjacencyList
        ) {
            // Cycle detection
            if (isset($visited[$partNo])) {
                throw new Exception("Cycle detected in BOM relationships involving: " . $partNo);
            }
            $visited[$partNo] = true;

            // Determine item name
            $name = isset($items[$partNo]) ? trim((string)$items[$partNo]) : $partNo;

            // Add current node to parsed parts list
            $parsedParts[] = [
                'part_no' => $partNo,
                'name' => $name,
                'required_quantity' => $qty,
                'unit' => $unit,
                'parent_part_no' => $parentPartNo,
                'ref' => $ref
            ];

            // Traverse children
            if (isset($adjacencyList[$partNo])) {
                foreach ($adjacencyList[$partNo] as $edge) {
                    $traverse($edge['child'], $partNo, $edge['qty'], $edge['ref'], $edge['unit']);
                }
            }

            unset($visited[$partNo]);
        };

        // Start traversal from each root
        foreach ($roots as $rootPartNo) {
            $traverse($rootPartNo, null, 1.0, null, 'ea');
        }

        return $parsedParts;
    }

    /**
     * Traverse nested JSON structure recursively.
     *
     * @param array $items
     * @param string|null $parentPartNo
     * @param array &$parsedParts
     */
    private function traverse(array $items, ?string $parentPartNo, array &$parsedParts): void {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            // Extract fields
            $component = isset($item['component']) ? trim((string)$item['component']) : '';
            if ($component === '') {
                // Try alternate key names
                $component = isset($item['name']) ? trim((string)$item['name']) : '';
                if ($component === '') {
                    continue;
                }
            }

            $findNo = isset($item['find_no']) ? trim((string)$item['find_no']) : '';
            if ($findNo === '') {
                $findNo = isset($item['find_number']) ? trim((string)$item['find_number']) : '';
            }

            $qtyVal = isset($item['qty']) ? $item['qty'] : null;
            if ($qtyVal === null) {
                $qtyVal = isset($item['quantity']) ? $item['quantity'] : 1;
            }
            $requiredQuantity = (float)$qtyVal;

            $unit = isset($item['units']) ? trim((string)$item['units']) : '';
            if ($unit === '') {
                $unit = isset($item['unit']) ? trim((string)$item['unit']) : '';
                if ($unit === '') {
                    $unit = isset($item['uom']) ? trim((string)$item['uom']) : 'ea';
                }
            }

            $ref = isset($item['ref']) ? trim((string)$item['ref']) : null;
            if ($ref === '') {
                $ref = null;
            }

            // Generate part number: convert to UPPERCASE, spaces to underscores, find number separated by dash
            $partNoComponent = strtoupper(str_replace(' ', '_', $component));
            $partNo = $partNoComponent;
            if ($findNo !== '') {
                $partNo .= '-' . $findNo;
            }

            $parsedParts[] = [
                'part_no' => $partNo,
                'name' => $component,
                'required_quantity' => $requiredQuantity,
                'unit' => $unit,
                'parent_part_no' => $parentPartNo,
                'ref' => $ref
            ];

            // Recurse children if present
            $children = null;
            if (isset($item['children']) && is_array($item['children'])) {
                $children = $item['children'];
            } elseif (isset($item['parts']) && is_array($item['parts'])) {
                $children = $item['parts'];
            }

            if ($children !== null) {
                $this->traverse($children, $partNo, $parsedParts);
            }
        }
    }
}
