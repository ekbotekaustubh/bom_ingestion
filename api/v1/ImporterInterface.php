<?php

/**
 * Interface BomImporterInterface
 * Defines the contract for parsing Bill of Materials files.
 */
interface BomImporterInterface {
    /**
     * Parse the given BOM file and return the structured array.
     *
     * @param string $filePath Absolute path to the uploaded file.
     * @return array The parsed data structure representing products and parts.
     */
    public function parse(string $filePath): array;
}
