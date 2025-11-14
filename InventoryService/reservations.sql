CREATE TABLE inventory_reservations (
    request_id CHAR(36) PRIMARY KEY,
    order_ref CHAR(36),
    items JSON,
    status VARCHAR(20),
    response JSON
);
