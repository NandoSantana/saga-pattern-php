CREATE TABLE payment_requests (
    request_id CHAR(36) PRIMARY KEY,
    order_ref CHAR(36),
    amount DECIMAL(10,2),
    status VARCHAR(20),
    response JSON
);