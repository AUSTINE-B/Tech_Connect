-- TechConnect MySQL/MariaDB initial schema for XAMPP.
-- Create/select the database `techconnect` in phpMyAdmin before importing this file.
-- Compatible target: MySQL 8.x or XAMPP MariaDB with InnoDB, utf8mb4 and CHECK support.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS app_user (
    id CHAR(36) NOT NULL PRIMARY KEY,
    email VARCHAR(254) NOT NULL UNIQUE,
    display_name VARCHAR(160) NOT NULL,
    phone VARCHAR(32) NULL,
    password_hash CHAR(64) NOT NULL,
    password_salt CHAR(32) NOT NULL,
    role ENUM('customer','technician','dispatcher','supervisor','price_manager','price_approver','payroll','admin') NOT NULL,
    preferred_language ENUM('en','fr') NOT NULL DEFAULT 'en',
    terms_version VARCHAR(32) NULL,
    consented_at DATETIME(6) NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL,
    KEY idx_user_role_active(role,active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_session (
    token_hash CHAR(64) NOT NULL PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    revoked_at DATETIME(6) NULL,
    CONSTRAINT fk_session_user FOREIGN KEY(user_id) REFERENCES app_user(id),
    KEY idx_session_user_expiry(user_id,expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_trade_authorization (
    user_id CHAR(36) NOT NULL,
    trade_code VARCHAR(40) NOT NULL,
    zone_code VARCHAR(60) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY(user_id,trade_code,zone_code),
    CONSTRAINT fk_authorization_user FOREIGN KEY(user_id) REFERENCES app_user(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_item (
    code VARCHAR(32) NOT NULL PRIMARY KEY,
    category VARCHAR(80) NOT NULL,
    trade_code VARCHAR(40) NOT NULL,
    name_en VARCHAR(180) NOT NULL,
    name_fr VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    price_xaf BIGINT NOT NULL,
    price_min_xaf BIGINT NOT NULL DEFAULT 0,
    price_max_xaf BIGINT NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NOT NULL DEFAULT '',
    icon_path VARCHAR(255) NOT NULL DEFAULT '',
    unit VARCHAR(32) NOT NULL DEFAULT 'fixed',
    planned_minutes INT NOT NULL DEFAULT 30,
    warranty_days INT NOT NULL DEFAULT 0,
    published BOOLEAN NOT NULL DEFAULT TRUE,
    version_code VARCHAR(32) NOT NULL DEFAULT '2026.01',
    CHECK(price_xaf >= 0), CHECK(planned_minutes >= 0), CHECK(warranty_days >= 0),
    KEY idx_catalog_trade_published(trade_code,published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_category (
    category_name VARCHAR(80) NOT NULL PRIMARY KEY,
    trade_code VARCHAR(40) NOT NULL,
    image_path VARCHAR(255) NOT NULL DEFAULT 'assets/Images/catalog/handyman-1.jpg',
    icon_path VARCHAR(255) NOT NULL DEFAULT 'assets/Icons/clarity--tools-solid.svg',
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    KEY idx_service_category_trade(trade_code,active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS service_zone (
    code VARCHAR(60) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_request (
    id CHAR(36) NOT NULL PRIMARY KEY,
    public_ref VARCHAR(40) NOT NULL UNIQUE,
    customer_id CHAR(36) NOT NULL,
    category VARCHAR(80) NOT NULL,
    trade_code VARCHAR(40) NOT NULL,
    zone_code VARCHAR(60) NOT NULL,
    issue TEXT NOT NULL,
    address TEXT NOT NULL COMMENT 'Development only: production must encrypt this field or use an encrypted address service',
    preferred_slot VARCHAR(64) NOT NULL,
    consent_version VARCHAR(32) NOT NULL,
    consented_at DATETIME(6) NOT NULL,
    status VARCHAR(32) NOT NULL,
    idempotency_key VARCHAR(128) NULL UNIQUE,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    CONSTRAINT fk_request_customer FOREIGN KEY(customer_id) REFERENCES app_user(id),
    CONSTRAINT fk_request_zone FOREIGN KEY(zone_code) REFERENCES service_zone(code),
    KEY idx_request_customer_created(customer_id,created_at),
    KEY idx_request_status_created(status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispatch_assignment (
    id CHAR(36) NOT NULL PRIMARY KEY,
    request_id CHAR(36) NOT NULL,
    technician_id CHAR(36) NOT NULL,
    assigned_by CHAR(36) NOT NULL,
    status VARCHAR(24) NOT NULL,
    reason VARCHAR(500) NOT NULL,
    eta VARCHAR(64) NULL,
    assigned_at DATETIME(6) NOT NULL,
    responded_at DATETIME(6) NULL,
    revoked_at DATETIME(6) NULL,
    active_request_id CHAR(36) GENERATED ALWAYS AS (CASE WHEN revoked_at IS NULL THEN request_id ELSE NULL END) STORED,
    CONSTRAINT fk_assignment_request FOREIGN KEY(request_id) REFERENCES service_request(id),
    CONSTRAINT fk_assignment_technician FOREIGN KEY(technician_id) REFERENCES app_user(id),
    CONSTRAINT fk_assignment_actor FOREIGN KEY(assigned_by) REFERENCES app_user(id),
    UNIQUE KEY uq_one_active_assignment(active_request_id),
    KEY idx_assignment_tech_status(technician_id,status,assigned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estimate_version (
    id CHAR(36) NOT NULL PRIMARY KEY,
    request_id CHAR(36) NOT NULL,
    version_number INT NOT NULL,
    pricebook_version VARCHAR(32) NOT NULL,
    status VARCHAR(24) NOT NULL,
    subtotal_xaf BIGINT NOT NULL,
    callout_fee_xaf BIGINT NOT NULL DEFAULT 0,
    tax_xaf BIGINT NOT NULL DEFAULT 0,
    total_xaf BIGINT NOT NULL,
    warranty_summary TEXT NOT NULL,
    created_by CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    customer_decision_at DATETIME(6) NULL,
    CONSTRAINT fk_estimate_request FOREIGN KEY(request_id) REFERENCES service_request(id),
    CONSTRAINT fk_estimate_creator FOREIGN KEY(created_by) REFERENCES app_user(id),
    UNIQUE KEY uq_estimate_version(request_id,version_number),
    CHECK(version_number > 0), CHECK(subtotal_xaf >= 0), CHECK(callout_fee_xaf >= 0), CHECK(tax_xaf >= 0), CHECK(total_xaf >= 0),
    KEY idx_estimate_request_created(request_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estimate_line (
    id CHAR(36) NOT NULL PRIMARY KEY,
    estimate_id CHAR(36) NOT NULL,
    catalog_code VARCHAR(32) NOT NULL,
    description_snapshot VARCHAR(180) NOT NULL,
    quantity INT NOT NULL,
    unit_price_xaf BIGINT NOT NULL,
    line_total_xaf BIGINT NOT NULL,
    warranty_days INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_line_estimate FOREIGN KEY(estimate_id) REFERENCES estimate_version(id),
    CONSTRAINT fk_line_catalog FOREIGN KEY(catalog_code) REFERENCES catalog_item(code),
    CHECK(quantity > 0), CHECK(unit_price_xaf >= 0), CHECK(line_total_xaf >= 0), CHECK(warranty_days >= 0),
    KEY idx_line_estimate(estimate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_attempt (
    id CHAR(36) NOT NULL PRIMARY KEY,
    request_id CHAR(36) NOT NULL,
    estimate_id CHAR(36) NOT NULL,
    provider VARCHAR(64) NOT NULL,
    amount_xaf BIGINT NOT NULL,
    status VARCHAR(32) NOT NULL,
    idempotency_key VARCHAR(128) NOT NULL UNIQUE,
    provider_reference VARCHAR(160) NULL,
    created_at DATETIME(6) NOT NULL,
    verified_at DATETIME(6) NULL,
    CONSTRAINT fk_payment_request FOREIGN KEY(request_id) REFERENCES service_request(id),
    CONSTRAINT fk_payment_estimate FOREIGN KEY(estimate_id) REFERENCES estimate_version(id),
    CHECK(amount_xaf > 0),
    KEY idx_payment_status_created(status,created_at),
    KEY idx_payment_estimate(estimate_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS session_event (
    id CHAR(36) NOT NULL PRIMARY KEY,
    request_id CHAR(36) NOT NULL,
    actor_id CHAR(36) NULL,
    event_type VARCHAR(64) NOT NULL,
    from_status VARCHAR(32) NULL,
    to_status VARCHAR(32) NULL,
    reason VARCHAR(500) NOT NULL DEFAULT '',
    payload_json JSON NOT NULL,
    created_at DATETIME(6) NOT NULL,
    CONSTRAINT fk_event_request FOREIGN KEY(request_id) REFERENCES service_request(id),
    CONSTRAINT fk_event_actor FOREIGN KEY(actor_id) REFERENCES app_user(id),
    KEY idx_event_request_created(request_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_timer_event (
    id CHAR(36) NOT NULL PRIMARY KEY,
    request_id CHAR(36) NOT NULL,
    technician_id CHAR(36) NOT NULL,
    action ENUM('START','PAUSE','RESUME','COMPLETE') NOT NULL,
    reason VARCHAR(500) NOT NULL DEFAULT '',
    created_at DATETIME(6) NOT NULL,
    CONSTRAINT fk_timer_request FOREIGN KEY(request_id) REFERENCES service_request(id),
    CONSTRAINT fk_timer_tech FOREIGN KEY(technician_id) REFERENCES app_user(id),
    KEY idx_timer_request_created(request_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_case (
    id CHAR(36) NOT NULL PRIMARY KEY,
    request_id CHAR(36) NOT NULL,
    customer_id CHAR(36) NOT NULL,
    case_type ENUM('REWORK','DISPUTE','SUPPORT') NOT NULL,
    detail TEXT NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'OPEN',
    created_at DATETIME(6) NOT NULL,
    resolved_at DATETIME(6) NULL,
    CONSTRAINT fk_case_request FOREIGN KEY(request_id) REFERENCES service_request(id),
    CONSTRAINT fk_case_customer FOREIGN KEY(customer_id) REFERENCES app_user(id),
    KEY idx_case_request_status(request_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_event (
    id CHAR(36) NOT NULL PRIMARY KEY,
    actor_id CHAR(36) NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(64) NOT NULL,
    entity_id VARCHAR(64) NOT NULL,
    reason VARCHAR(500) NOT NULL DEFAULT '',
    created_at DATETIME(6) NOT NULL,
    CONSTRAINT fk_audit_actor FOREIGN KEY(actor_id) REFERENCES app_user(id),
    KEY idx_audit_entity(entity_type,entity_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_address_access_log (
    id CHAR(36) NOT NULL PRIMARY KEY,
    employee_id CHAR(36) NOT NULL,
    request_id CHAR(36) NOT NULL,
    assignment_id CHAR(36) NOT NULL,
    reason VARCHAR(200) NOT NULL,
    accessed_at DATETIME(6) NOT NULL,
    CONSTRAINT fk_address_access_employee FOREIGN KEY(employee_id) REFERENCES app_user(id),
    CONSTRAINT fk_address_access_request FOREIGN KEY(request_id) REFERENCES service_request(id),
    CONSTRAINT fk_address_access_assignment FOREIGN KEY(assignment_id) REFERENCES dispatch_assignment(id),
    KEY idx_address_access_request(request_id,accessed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO service_zone(code,name,city) VALUES
    ('DOUALA_BONAMOUSSADI','Bonamoussadi','Douala'),
    ('DOUALA_AKWA','Akwa','Douala'),
    ('DOUALA_BONAPRISO','Bonapriso','Douala'),
    ('DOUALA_MAKEPE','Makepe','Douala'),
    ('YAOUNDE_CENTRE','Yaoundé','Yaoundé');

INSERT IGNORE INTO catalog_item(code,category,trade_code,name_en,name_fr,description,price_xaf,unit,planned_minutes,warranty_days,version_code) VALUES
    ('PL-014','Plumbing','plumbing','Replace flexible inlet hose','Remplacer le flexible d’arrivée','Labour and standard fitting; replacement hose shown separately if non-standard.',8000,'fixed',35,30,'2026.01'),
    ('PL-006','Plumbing','plumbing','Leak inspection and pressure test','Inspection de fuite et test de pression','Diagnostic task; excludes concealed pipe replacement.',12000,'fixed',45,0,'2026.01'),
    ('PL-021','Plumbing','plumbing','Test water pressure','Tester la pression d’eau','Standard pressure check.',5000,'fixed',25,0,'2026.01'),
    ('EL-021','Electrical','electrical','Socket replacement','Remplacement de prise','Labour; standard socket included only where stated on the estimate.',6500,'fixed',30,30,'2026.01'),
    ('EL-008','Electrical','electrical','Electrical fault inspection','Diagnostic électrique','Inspection and safe fault isolation; parts excluded.',10000,'fixed',45,0,'2026.01'),
    ('AP-018','Appliance repair','appliance','Washer diagnosis','Diagnostic de lave-linge','Inspection; replacement parts require a new estimate.',5000,'fixed',30,0,'2026.01'),
    ('AP-025','Appliance repair','appliance','Fan repair','Réparation de ventilateur','Standard labour; replacement motor or parts excluded.',7000,'fixed',40,30,'2026.01');

INSERT IGNORE INTO service_category(category_name,trade_code,image_path,icon_path) SELECT category,MIN(trade_code),MIN(image_path),MIN(icon_path) FROM catalog_item WHERE published=1 GROUP BY category;
