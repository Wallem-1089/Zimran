-- Barcode aliases for Store inventory items.
-- Item code remains scannable; this table stores additional manufacturer/internal barcodes.

CREATE TABLE IF NOT EXISTS inventory_item_barcodes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    inventory_item_id INT NOT NULL,
    barcode_value VARCHAR(100) NOT NULL,
    barcode_type ENUM('Manufacturer','Internal','Other') NOT NULL DEFAULT 'Manufacturer',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory_item_barcodes_value (barcode_value),
    KEY idx_inventory_item_barcodes_item (inventory_item_id),
    KEY idx_inventory_item_barcodes_active (is_active),
    CONSTRAINT fk_inventory_item_barcodes_item
        FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_inventory_item_barcodes_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
