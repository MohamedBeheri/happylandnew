-- =====================================================
--  Happy Land - هابي لاند
--  نظام إدارة ملاهي - شبين الكوم / نادي الجمهورية
--  استورد هذا الملف من phpMyAdmin (Import)
-- =====================================================

CREATE DATABASE IF NOT EXISTS kidsarea
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kidsarea;

SET FOREIGN_KEY_CHECKS = 0;

-- ============== المستخدمين والصلاحيات ==============
CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) DEFAULT '',
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    phone        VARCHAR(20)  UNIQUE,
    national_id  VARCHAR(20)  UNIQUE,
    role         ENUM('admin','cashier') NOT NULL DEFAULT 'admin',
    is_active    TINYINT(1) DEFAULT 1,
    is_moderator TINYINT(1) DEFAULT 0,
    is_superuser TINYINT(1) DEFAULT 0,
    is_root      TINYINT(1) DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============== الألعاب ==============
CREATE TABLE game (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    icon  VARCHAR(16) NOT NULL DEFAULT '🎮'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============== التذاكر ==============
CREATE TABLE sale_ticket (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    date           DATE NOT NULL,
    total_price    DECIMAL(10,2) DEFAULT 0,
    discount       DECIMAL(5,2)  DEFAULT 0,
    after_discount DECIMAL(10,2) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sale_ticket_item (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    sale_ticket_id INT NOT NULL,
    game_id        INT NOT NULL,
    amount         INT DEFAULT 1,
    FOREIGN KEY (sale_ticket_id) REFERENCES sale_ticket(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id)        REFERENCES game(id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============== العملاء ==============
CREATE TABLE client (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    national_id VARCHAR(20) UNIQUE,
    gender      ENUM('male','female') DEFAULT 'male',
    birth_date  DATE,
    age         INT,
    phone       VARCHAR(15),
    phone2      VARCHAR(15),
    email       VARCHAR(100),
    address     TEXT,
    photo       VARCHAR(255),
    added_by_id INT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_blocked  TINYINT(1) DEFAULT 0,
    FOREIGN KEY (added_by_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============== المنتجات / المخزن ==============
CREATE TABLE product_category (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT NOT NULL,
    price       DECIMAL(10,2) NOT NULL,
    stock       INT DEFAULT 0,
    image       VARCHAR(255),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_category(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============== مبيعات المنتجات ==============
CREATE TABLE sale (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    customer_id    INT NULL,
    date           DATE NULL,
    discount       DECIMAL(5,2)  DEFAULT 0,
    total_price    DECIMAL(10,2) DEFAULT 0,
    after_discount DECIMAL(10,2) DEFAULT 0,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES client(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sale_item (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    sale_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT DEFAULT 1,
    FOREIGN KEY (sale_id)    REFERENCES sale(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============== الماليات ==============
CREATE TABLE financial_item (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    financial_type ENUM('expenses','incomes') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE transaction (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    date        DATE NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    ticket_id   INT NULL,
    sale_id     INT NULL,
    FOREIGN KEY (category_id) REFERENCES financial_item(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id)   REFERENCES sale_ticket(id)    ON DELETE CASCADE,
    FOREIGN KEY (sale_id)     REFERENCES sale(id)           ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============== بيانات ابتدائية ==============
-- المستخدم الافتراضي: admin / admin123  (أدمن كامل الصلاحيات)
-- (كلمة المرور مخزّنة كـ SHA-256؛ نظام الدخول يدعمها ويدعم bcrypt للمستخدمين الجدد)
INSERT INTO users (name, username, password, role, is_active, is_superuser, is_root)
VALUES ('المدير العام', 'admin',
        '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin', 1, 1, 1);

INSERT INTO game (name, price, icon) VALUES
    ('ترامبولين – ربع ساعة', 30.00, '🤸'),
    ('ترامبولين – نص ساعة', 50.00, '🤸'),
    ('ترامبولين – ساعة', 80.00, '🤸'),
    ('سباق عربيات', 25.00, '🏎️'),
    ('VR 360', 35.00, '🥽'),
    ('VR Game', 35.00, '🎮'),
    ('ضفدعة', 25.00, '🐸'),
    ('هوكي', 30.00, '🏒'),
    ('باسكت بول', 30.00, '🏀'),
    ('سيارات فيديو جيم', 30.00, '🕹️'),
    ('موتسيكل', 30.00, '🏍️'),
    ('قطر', 20.00, '🚂'),
    ('مطافي', 25.00, '🚒'),
    ('عربية هزاز أطفال', 25.00, '🚗'),
    ('قطر هزاز أطفال', 15.00, '🚃'),
    ('مصاصات', 10.00, '🍭'),
    ('رسم وتلوين', 15.00, '🎨');

INSERT INTO financial_item (name, financial_type) VALUES
    ('إيجار', 'expenses'), ('كهرباء', 'expenses'),
    ('مبيعات تذاكر', 'incomes'), ('مبيعات منتجات', 'incomes'), ('إيرادات أخرى', 'incomes');

INSERT INTO product_category (name) VALUES ('مشروبات'), ('سناكس'), ('ألعاب');
