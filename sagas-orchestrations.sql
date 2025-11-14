CREATE TABLE sagas (
    id CHAR(36) PRIMARY KEY, -- UUID da saga
    type VARCHAR(100),       -- tipo de saga, ex: OrderSaga
    state VARCHAR(50),       -- pending, completed, compensating, failed
    step VARCHAR(100),       -- nome do passo atual
    payload JSON,            -- dados da saga
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
