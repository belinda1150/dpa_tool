CREATE TABLE compliance_responses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id INT(11) UNSIGNED NOT NULL,
    item_key VARCHAR(50) NOT NULL,
    response ENUM('yes','no','partial','na') DEFAULT NULL,
    notes TEXT,
    updated_by INT(11) UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_org_item (org_id, item_key),
    FOREIGN KEY (org_id) REFERENCES organizations(org_id),
    FOREIGN KEY (updated_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
