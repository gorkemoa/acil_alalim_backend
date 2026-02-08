-- Database Setup for Acil Alalım Platform

CREATE DATABASE IF NOT EXISTS acil_alalim;
USE acil_alalim;

-- Location and category reference tables
CREATE TABLE provinces (
  id INT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE districts (
  id INT PRIMARY KEY,
  province_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE
);

CREATE TABLE categories (
  id INT PRIMARY KEY,
  parent_id INT DEFAULT 0,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(150),
  url_path VARCHAR(255)
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  profile_photo VARCHAR(255),
  province_id INT,
  district_id INT,
  phone VARCHAR(30),
  whatsapp VARCHAR(30),
  bio VARCHAR(500),
  website VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ,FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE SET NULL
  ,FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL
);

CREATE TABLE needs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  category_id INT,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  province_id INT,
  district_id INT,
  allow_comments BOOLEAN DEFAULT 1,
  status VARCHAR(50) DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE SET NULL,
  FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL
);

CREATE TABLE comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  need_id INT NOT NULL,
  comment TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (need_id) REFERENCES needs(id) ON DELETE CASCADE
);

CREATE TABLE favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  need_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (need_id) REFERENCES needs(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  is_read BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
