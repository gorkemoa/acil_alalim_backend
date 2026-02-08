# İhtiyaç İlanı Platformu Backend Dökümanı (Ters E-Ticaret)

## 📌 Proje Tanımı

Bu platform klasik e-ticaretin tersidir.

Kullanıcılar ürün satmak yerine **ihtiyaç duydukları şeyleri ilan olarak paylaşır.**

Örnek ilanlar:

- Laptop lazım  
- Ev arkadaşı arıyorum  
- Fotoğrafçı arıyorum  
- Bisiklet lazım  
- Yazılım projesi için ekip arkadaşı arıyorum  

Diğer kullanıcılar bu ihtiyaçları görür, iletişime geçer ve çözüm sunar.

Bu döküman, **ödeme sistemi olmayan**, ancak diğer yönleriyle kapsamlı bir backend mimarisini tanımlar.

---

# 🎯 Sistem Amaçları

- İhtiyaç ilanı oluşturma  
- Konum bazlı ilan sistemi  
- Kullanıcı sistemi  
- Mesajlaşma sistemi  
- Favori sistemi  
- Bildirim altyapısı  
- Raporlama & moderasyon  
- Ölçeklenebilir REST API altyapısı  

Ödeme sistemi BU PROJEDE YOKTUR.

---

# 🧱 Teknoloji Yığını

- PHP 8+ (Frameworksüz)
- MySQL
- Apache (XAMPP/LAMP)
- RESTful API
- JSON veri formatı
- PDO ile güvenli DB bağlantısı
- Localhost geliştirme ortamı

---

# 📁 Klasör Yapısı

/project-root
/api
index.php
db.php
.htaccess

/controllers
AuthController.php
NeedController.php
MessageController.php
FavoriteController.php
NotificationController.php

/models
User.php
Need.php
Message.php
Favorite.php
Notification.php

/services
LocationService.php
ValidationService.php


---

# 🗄️ Veritabanı Tasarımı

## users

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(150) UNIQUE,
  password VARCHAR(255),
  profile_photo VARCHAR(255),
  province_id INT,
  district_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
needs
CREATE TABLE needs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(255),
  description TEXT,
  category_id INT,
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  province_id INT,
  district_id INT,
  status VARCHAR(50) DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
messages
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT,
  receiver_id INT,
  need_id INT,
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
favorites
CREATE TABLE favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  need_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
notifications
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(255),
  body TEXT,
  is_read BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
🔐 Kimlik Doğrulama
JWT veya simple token sistemi

Login sonrası token üretimi

Header üzerinden doğrulama

Authorization: Bearer TOKEN
🔌 API Standartları
JSON request/response

UTF-8 encoding

Doğru HTTP status code kullanımı

Pagination desteği

🚀 API ENDPOINTLERİ
AUTH
POST /api/register
Kullanıcı oluşturur.

POST /api/login
Token döner.

NEEDS (İhtiyaç İlanları)
POST /api/needs
İlan oluşturur.

GET /api/needs
Tüm ilanları listeler.

GET /api/needs/{id}
İlan detayı.

PUT /api/needs/{id}
İlan güncelle.

DELETE /api/needs/{id}
İlan sil.

📍 Konuma Göre Listeleme
GET
/api/needs/near?lat=38.42&lng=27.14&radius=10
Haversine SQL
SELECT *,
(6371 * acos(
  cos(radians(:lat)) *
  cos(radians(latitude)) *
  cos(radians(longitude) - radians(:lng)) +
  sin(radians(:lat)) *
  sin(radians(latitude))
)) AS distance
FROM needs
HAVING distance < :radius
ORDER BY distance;
💬 Mesajlaşma
POST /api/messages
Mesaj gönder.

GET /api/messages/{need_id}
İlan bazlı sohbet getir.

⭐ Favoriler
POST /api/favorites
Favoriye ekle.

GET /api/favorites
Kullanıcının favorileri.

🔔 Bildirimler
GET /api/notifications
Bildirim listesi.

PUT /api/notifications/{id}
Okundu işaretle.

🗺️ Harita Entegrasyonu
Frontend tarafı:

Google Maps / Mapbox

Marker gösterimi

Konum seçerek ilan oluşturma

Backend:

lat/lng saklama

radius sorgusu

şehir bazlı filtreleme

🧪 Postman Test Planı
Register

Login

İlan oluştur

Yakındaki ilanları çek

Mesaj gönder

Favoriye ekle

Bildirimleri kontrol et

⚙️ Validasyon Kuralları
title min 5 karakter

description min 10 karakter

lat/lng zorunlu

email format kontrolü

password min 6 karakter

🛡️ Güvenlik
Prepared statements

XSS filtreleme

Rate limiting (opsiyonel)

Token expiration

📈 Ölçeklenebilirlik
İleride:

Redis cache

CDN görseller

Queue sistemi

Microservice mimarisi

🔮 Gelecek Genişletmeler
AI öneri sistemi

Akıllı eşleşme algoritması

Spam tespiti

Admin paneli

İlan boost sistemi (ödeme olmadan)

✅ Sonuç
Bu backend:

Ters e-ticaret mantığına uygun

Konum bazlı

Mesajlaşmalı

Favorili

Bildirimli

Ölçeklenebilir

Ödeme sistemi olmayan
kapsamlı bir ihtiyaç platformu altyapısıdır.
