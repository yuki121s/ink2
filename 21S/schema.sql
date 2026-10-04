-- นำเข้าไฟล์นี้ผ่าน phpMyAdmin (แท็บ Import) หลังสร้างฐานข้อมูล shop21
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(200) NOT NULL,
  description TEXT NULL,
  price       DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock       INT NOT NULL DEFAULT 0,
  category    VARCHAR(50) NOT NULL,
  image       VARCHAR(255) NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  fullname       VARCHAR(120) NOT NULL,
  phone          VARCHAR(30)  NOT NULL,
  address        TEXT NOT NULL,
  payment_method VARCHAR(30)  NOT NULL,
  total_price    DECIMAL(10,2) NOT NULL,
  slip_image     VARCHAR(255) NULL,
  status         ENUM('pending','paid','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  product_id INT NULL,
  name       VARCHAR(200) NOT NULL,
  price      DECIMAL(10,2) NOT NULL,
  qty        INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (name, description, price, stock, category) VALUES
('ยาง Michelin 215/55R17', 'ยางเรเดียล นุ่มเงียบ เหมาะกับรถเก๋ง', 4290.00, 12, 'ยางรถยนต์'),
('ผ้าเบรกหน้า Brembo', 'ผ้าเบรกเซรามิก ฝุ่นน้อย', 1850.00, 8, 'ผ้าเบรก'),
('โช้คอัพหน้า KYB', 'โช้คแก๊สสำหรับรถกระบะ', 2600.00, 5, 'โช้คอัพ'),
('น้ำมันเครื่อง 5W-30 4 ลิตร', 'สังเคราะห์แท้ 100%', 1290.00, 30, 'น้ำมันเครื่อง');
