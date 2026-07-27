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
    `ref` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Reference designator (e.g. ARM1-4)',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'Status of the part (e.g., active, inactive)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_part_no` (`part_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create table: bom_relationships
CREATE TABLE IF NOT EXISTS `bom_relationships` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `parent_part_id` INT NOT NULL,
    `child_part_id` INT NOT NULL,
    `quantity` DECIMAL(12, 4) NOT NULL DEFAULT 1.0000,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_parent_child` (`parent_part_id`, `child_part_id`),
    CONSTRAINT `fk_parent_part` FOREIGN KEY (`parent_part_id`) REFERENCES `Part` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_child_part` FOREIGN KEY (`child_part_id`) REFERENCES `Part` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
