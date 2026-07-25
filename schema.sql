-- MySQL Database Schema for BOM Ingestion

-- Create and select database
CREATE DATABASE IF NOT EXISTS `bom_ingestion`;
USE `bom_ingestion`;

-- Create table: Part
CREATE TABLE IF NOT EXISTS `Part` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `part_no` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `unit` VARCHAR(10) NOT NULL,
    `parent_part_id` INT NOT NULL DEFAULT 0 COMMENT 'ID of the parent part. 0 for top-level parts.',
    `required_quantity` DECIMAL(12, 4) NOT NULL DEFAULT 1.0000 COMMENT 'Quantity required. Decimal allowed for fractional units.',
    `ref` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Reference designator (e.g. ARM1-4)',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'Status of the part (e.g., active, inactive)',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
