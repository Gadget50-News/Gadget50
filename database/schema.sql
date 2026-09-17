CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'editor', 'member') NOT NULL DEFAULT 'member',
    status ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
    is_anonymous_allowed TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    url VARCHAR(255) NOT NULL,
    position ENUM('header', 'footer', 'sidebar') NOT NULL DEFAULT 'header',
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    excerpt TEXT NULL,
    image VARCHAR(255) NULL,
    category_id INT NULL,
    author_id INT NULL,
    author_name VARCHAR(200) NULL,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('draft', 'pending', 'published', 'archived') NOT NULL DEFAULT 'pending',
    views INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (setting_key, setting_value)
VALUES
    ('site_name', 'Gadget 50'),
    ('site_logo', ''),
    ('site_favicon', ''),
    ('header_text', 'Breaking stories, trusted reporting.'),
    ('footer_copyright', '© 2026 Gadget 50. All rights reserved.'),
    ('breaking_news', 'Gadget 50 delivers the latest updates across technology, business, and culture.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO categories (name, slug, description, status)
VALUES
    ('General', 'general', 'General news and updates', 'active'),
    ('Technology', 'technology', 'Tech trends and gadget news', 'active'),
    ('Business', 'business', 'Business and market updates', 'active'),
    ('Entertainment', 'entertainment', 'Lifestyle and entertainment stories', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO menus (title, url, position, sort_order, status)
VALUES
    ('Home', '/', 'header', 1, 'active'),
    ('Technology', '/category/technology', 'header', 2, 'active'),
    ('Business', '/category/business', 'header', 3, 'active'),
    ('Entertainment', '/category/entertainment', 'header', 4, 'active'),
    ('Privacy', '/privacy', 'footer', 1, 'active'),
    ('Contact', '/contact', 'footer', 2, 'active')
ON DUPLICATE KEY UPDATE title = VALUES(title), url = VALUES(url);
