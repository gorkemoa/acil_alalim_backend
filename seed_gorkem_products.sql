
-- Insert User 16 if not exists (or replace)
DELETE FROM users WHERE id = 16;
INSERT INTO users (id, name, email, password, province_id, district_id, karma_score, latitude, longitude, phone, whatsapp, bio, website, profile_photo) 
VALUES (16, 'Görkem Öztürk', 'gorkem16@example.com', '$2y$10$T1K7.5lI.0lO2U7o8s6v6uX5R5r5r5r5r5r5r5r5r5r5r5r5r5r5r', 34, 1421, 95, 41.0082, 28.9784, '+905559998877', '+905559998877', 'Fotoğrafçılık ve doğa sporları ile ilgileniyorum. Ekipmanlarımı paylaşıyorum.', 'https://gorkem.design', 'gorkem_profile.jpg');

-- Insert Products for User 16
-- 1. DSLR Camera
INSERT INTO needs (id, user_id, title, description, category_id, latitude, longitude, province_id, district_id, status, is_sponsor) 
VALUES (101, 16, 'Canon 5D Mark IV Kamera', 'Profesyonel çekimler için kiralık veya kısa süreliğine ödünç verebilirim. Lensler dahil değildir, sadece body.', 15706, 41.0082, 28.9784, 34, 1421, 'active', 1);

INSERT INTO need_images (need_id, image_path, is_main) VALUES (101, 'camera_1.jpg', 1);
INSERT INTO need_images (need_id, image_path, is_main) VALUES (101, 'camera_2.jpg', 0);
INSERT INTO need_images (need_id, image_path, is_main) VALUES (101, 'camera_3.jpg', 0);

-- 2. Mountain Bike
INSERT INTO needs (id, user_id, title, description, category_id, latitude, longitude, province_id, district_id, status, is_sponsor) 
VALUES (102, 16, 'Özel Yapım Dağ Bisikleti', 'Haftasonu turları için ideal, bakımlı dağ bisikleti. Kask ile birlikte verilecektir.', 15708, 41.0122, 28.9760, 34, 1421, 'active', 1);

INSERT INTO need_images (need_id, image_path, is_main) VALUES (102, 'bike_1.jpg', 1);
INSERT INTO need_images (need_id, image_path, is_main) VALUES (102, 'bike_2.jpg', 0);

-- 3. Drill Set
INSERT INTO needs (id, user_id, title, description, category_id, latitude, longitude, province_id, district_id, status, is_sponsor) 
VALUES (103, 16, 'Bosch Darbeli Matkap Seti', 'Ev tadilatları için tam set matkap ucuyla birlikte. Birkaç saatlik işler için ideal.', 15705, 41.0200, 28.9500, 34, 1421, 'active', 0);

INSERT INTO need_images (need_id, image_path, is_main) VALUES (103, 'drill_1.jpg', 1);
INSERT INTO need_images (need_id, image_path, is_main) VALUES (103, 'drill_2.jpg', 0);

-- 4. Camping Tent
INSERT INTO needs (id, user_id, title, description, category_id, latitude, longitude, province_id, district_id, status, is_sponsor) 
VALUES (104, 16, '4 Kişilik Kamp Çadırı', 'Su geçirmez, kurulumu kolay kamp çadırı. Mat ve tulum yoktur, sadece çadır.', 15707, 41.0050, 28.9850, 34, 1421, 'active', 0);

INSERT INTO need_images (need_id, image_path, is_main) VALUES (104, 'tent_1.jpg', 1);
INSERT INTO need_images (need_id, image_path, is_main) VALUES (104, 'tent_2.jpg', 0);

-- 5. Guitar
INSERT INTO needs (id, user_id, title, description, category_id, latitude, longitude, province_id, district_id, status, is_sponsor) 
VALUES (105, 16, 'Yamaha Akustik Gitar', 'Müzik öğrencileri veya pratik yapmak isteyenler için. Kılıfıyla birlikte.', 15708, 41.0150, 28.9900, 34, 1421, 'active', 1);

INSERT INTO need_images (need_id, image_path, is_main) VALUES (105, 'guitar_1.jpg', 1);
INSERT INTO need_images (need_id, image_path, is_main) VALUES (105, 'guitar_2.jpg', 0);
