-- The Gift Boxx database schema (MySQL / MariaDB).
-- The installer converts this file automatically when SQLite is used for local testing.

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  name VARCHAR(190) NOT NULL DEFAULT '',
  phone VARCHAR(40) NOT NULL DEFAULT '',
  role VARCHAR(20) NOT NULL DEFAULT 'customer',
  address_json TEXT NULL,
  reset_token VARCHAR(100) NULL,
  reset_expires DATETIME NULL,
  woo_id INT NULL,
  created_at DATETIME NOT NULL,
  last_login DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(64) NOT NULL,
  scope VARCHAR(20) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_login_attempts ON login_attempts (ip, scope, created_at);

CREATE TABLE settings (
  skey VARCHAR(100) NOT NULL PRIMARY KEY,
  svalue MEDIUMTEXT NULL,
  encrypted TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  description TEXT NULL,
  image VARCHAR(255) NULL,
  seo_title VARCHAR(255) NULL,
  seo_description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  woo_id INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE brands (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  woo_id INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(20) NOT NULL DEFAULT 'simple',
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  sku VARCHAR(100) NULL,
  gtin VARCHAR(50) NULL,
  brand_id INT NULL,
  box_type_id INT NULL,
  page_bg_color VARCHAR(20) NULL,
  page_text_color VARCHAR(20) NULL,
  short_description MEDIUMTEXT NULL,
  description MEDIUMTEXT NULL,
  regular_price DECIMAL(10,2) NULL,
  sale_price DECIMAL(10,2) NULL,
  sale_from DATETIME NULL,
  sale_to DATETIME NULL,
  price_min DECIMAL(10,2) NOT NULL DEFAULT 0,
  price_max DECIMAL(10,2) NOT NULL DEFAULT 0,
  manage_stock TINYINT(1) NOT NULL DEFAULT 0,
  stock_qty INT NULL,
  stock_status VARCHAR(20) NOT NULL DEFAULT 'instock',
  weight DECIMAL(10,3) NULL,
  length DECIMAL(10,2) NULL,
  width DECIMAL(10,2) NULL,
  height DECIMAL(10,2) NULL,
  tax_status VARCHAR(20) NOT NULL DEFAULT 'taxable',
  tags TEXT NULL,
  attributes_json TEXT NULL,
  default_attributes_json TEXT NULL,
  upsell_ids TEXT NULL,
  cross_sell_ids TEXT NULL,
  seo_title VARCHAR(255) NULL,
  seo_description TEXT NULL,
  focus_keyword VARCHAR(190) NULL,
  google_sync TINYINT(1) NOT NULL DEFAULT 1,
  adult TINYINT(1) NOT NULL DEFAULT 0,
  views INT NOT NULL DEFAULT 0,
  rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
  rating_count INT NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  woo_id INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_products_status ON products (status, featured);

CREATE TABLE box_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  keywords VARCHAR(255) NOT NULL DEFAULT '',
  bg_color VARCHAR(20) NOT NULL DEFAULT '#F5EEE4',
  bg_image VARCHAR(255) NULL,
  text_color VARCHAR(20) NOT NULL DEFAULT '#1D1714',
  accent_color VARCHAR(20) NOT NULL DEFAULT '#BAA183',
  grain TINYINT(1) NOT NULL DEFAULT 1,
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_categories (
  product_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY (product_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE variations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  attributes_json TEXT NOT NULL,
  sku VARCHAR(100) NULL,
  gtin VARCHAR(50) NULL,
  description TEXT NULL,
  regular_price DECIMAL(10,2) NULL,
  sale_price DECIMAL(10,2) NULL,
  sale_from DATETIME NULL,
  sale_to DATETIME NULL,
  manage_stock TINYINT(1) NOT NULL DEFAULT 0,
  stock_qty INT NULL,
  stock_status VARCHAR(20) NOT NULL DEFAULT 'instock',
  weight DECIMAL(10,3) NULL,
  length DECIMAL(10,2) NULL,
  width DECIMAL(10,2) NULL,
  height DECIMAL(10,2) NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  woo_id INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_variations_product ON variations (product_id);

CREATE TABLE product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  variation_id INT NULL,
  path VARCHAR(255) NOT NULL,
  alt VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_images_product ON product_images (product_id, variation_id);

CREATE TABLE reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT NULL,
  name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  rating INT NOT NULL,
  body TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  woo_id INT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_reviews_product ON reviews (product_id, status);

CREATE TABLE coupons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL UNIQUE,
  type VARCHAR(20) NOT NULL DEFAULT 'percent',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  min_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  max_uses INT NULL,
  used INT NOT NULL DEFAULT 0,
  expires_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  number VARCHAR(40) NOT NULL UNIQUE,
  access_key VARCHAR(64) NOT NULL,
  user_id INT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  status VARCHAR(30) NOT NULL DEFAULT 'pending_payment',
  payment_method VARCHAR(30) NOT NULL DEFAULT '',
  payment_status VARCHAR(30) NOT NULL DEFAULT 'unpaid',
  payment_ref VARCHAR(190) NULL,
  gateway_order_id VARCHAR(190) NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  shipping DECIMAL(10,2) NOT NULL DEFAULT 0,
  fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  coupon_code VARCHAR(60) NULL,
  billing_json TEXT NULL,
  shipping_json TEXT NULL,
  gift_message TEXT NULL,
  delivery_date VARCHAR(20) NULL,
  customer_note TEXT NULL,
  shiprocket_order_id VARCHAR(60) NULL,
  shipment_id VARCHAR(60) NULL,
  awb VARCHAR(60) NULL,
  courier VARCHAR(120) NULL,
  tracking_url VARCHAR(255) NULL,
  stock_reduced TINYINT(1) NOT NULL DEFAULT 0,
  tracked TINYINT(1) NOT NULL DEFAULT 0,
  woo_id INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_orders_status ON orders (status, created_at);
CREATE INDEX idx_orders_email ON orders (email);

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NULL,
  variation_id INT NULL,
  name VARCHAR(255) NOT NULL,
  variation_label VARCHAR(255) NULL,
  sku VARCHAR(100) NULL,
  qty INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_order_items_order ON order_items (order_id);

CREATE TABLE order_notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  note TEXT NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_order_notes_order ON order_notes (order_id);

CREATE TABLE abandoned_carts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token VARCHAR(64) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL,
  name VARCHAR(190) NOT NULL DEFAULT '',
  phone VARCHAR(40) NOT NULL DEFAULT '',
  cart_json TEXT NOT NULL,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  reminders_sent INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_abandoned_status ON abandoned_carts (status, updated_at);

CREATE TABLE pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  content MEDIUMTEXT NULL,
  seo_title VARCHAR(255) NULL,
  seo_description TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'published',
  show_in_footer TINYINT(1) NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE enquiries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(20) NOT NULL DEFAULT 'contact',
  name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  company VARCHAR(190) NOT NULL DEFAULT '',
  occasion VARCHAR(190) NOT NULL DEFAULT '',
  quantity VARCHAR(60) NOT NULL DEFAULT '',
  budget VARCHAR(60) NOT NULL DEFAULT '',
  needed_by VARCHAR(40) NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'new',
  admin_note TEXT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_enquiries_status ON enquiries (status, created_at);

CREATE TABLE wishlist (
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE redirects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_path VARCHAR(190) NOT NULL UNIQUE,
  to_url VARCHAR(255) NOT NULL,
  hits INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  excerpt TEXT NULL,
  content MEDIUMTEXT NULL,
  cover_image VARCHAR(255) NULL,
  author VARCHAR(120) NOT NULL DEFAULT '',
  tags TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  seo_title VARCHAR(255) NULL,
  seo_description TEXT NULL,
  focus_keyword VARCHAR(190) NULL,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_posts_status ON posts (status, published_at);
