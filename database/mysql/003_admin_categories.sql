-- Admin-managed service categories. Existing catalog categories are preserved and seeded.
CREATE TABLE IF NOT EXISTS service_category (
    category_name VARCHAR(80) NOT NULL PRIMARY KEY,
    trade_code VARCHAR(40) NOT NULL,
    image_path VARCHAR(255) NOT NULL DEFAULT 'assets/Images/catalog/handyman-1.jpg',
    icon_path VARCHAR(255) NOT NULL DEFAULT 'assets/Icons/clarity--tools-solid.svg',
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    KEY idx_service_category_trade(trade_code,active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO service_category(category_name,trade_code,image_path,icon_path)
SELECT category,MIN(trade_code),MIN(image_path),MIN(icon_path) FROM catalog_item WHERE published=1 GROUP BY category ON DUPLICATE KEY UPDATE trade_code=VALUES(trade_code),image_path=IF(service_category.image_path='',VALUES(image_path),service_category.image_path),icon_path=IF(service_category.icon_path='',VALUES(icon_path),service_category.icon_path);