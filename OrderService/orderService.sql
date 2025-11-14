CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_order_id CHAR(36) UNIQUE,
    status VARCHAR(20),
    payload JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_requests (
    request_id CHAR(36) PRIMARY KEY,
    service_order_id CHAR(36),
    response JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
