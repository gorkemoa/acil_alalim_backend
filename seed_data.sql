-- Seed Data for Acil Alalım Platform

USE acil_alalim;

-- Clear existing data (optional, but good for a clean seed)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE notifications;
TRUNCATE TABLE favorites;
TRUNCATE TABLE ratings;
TRUNCATE TABLE reports;
TRUNCATE TABLE comments;
TRUNCATE TABLE need_images;
TRUNCATE TABLE needs;
TRUNCATE TABLE blocked_users;
TRUNCATE TABLE password_resets;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users
-- Passwords are 'Gorkem123.' hashed
INSERT INTO users (id, name, email, password, province_id, district_id, karma_score, latitude, longitude, phone, whatsapp, bio, website) VALUES
(1, 'Görkem', 'gorkem@example.com', '$2y$10$T1K7.5lI.0lO2U7o8s6v6uX5R5r5r5r5r5r5r5r5r5r5r5r5r5r5r', 34, 1421, 100, 41.0082, 28.9784, '+905551112233', '+905551112233', 'Yardım etmeyi severim.', 'https://gorkem.dev'),
(2, 'Ahmet Yılmaz', 'ahmet@mail.com', '$2y$10$T1K7.5lI.0lO2U7o8s6v6uX5R5r5r5r5r5r5r5r5r5r5r5r5r5r5r', 6, 1231, 50, 39.9334, 32.8597, '+905551112234', '+905551112234', NULL, NULL),
(3, 'Ayşe Demir', 'ayse@test.com', '$2y$10$T1K7.5lI.0lO2U7o8s6v6uX5R5r5r5r5r5r5r5r5r5r5r5r5r5r5r', 35, 1819, 75, 38.4192, 27.1287, NULL, NULL, 'Outdoor ve kamp seviyorum.', NULL),
(4, 'Mehmet Can', 'mehmet@corp.com', '$2y$10$T1K7.5lI.0lO2U7o8s6v6uX5R5r5r5r5r5r5r5r5r5r5r5r5r5r5r', 34, 1421, 20, 41.0122, 28.9760, NULL, NULL, NULL, NULL),
(5, 'Selin Kaya', 'selin@web.com', '$2y$10$T1K7.5lI.0lO2U7o8s6v6uX5R5r5r5r5r5r5r5r5r5r5r5r5r5r5r', 16, 1829, 30, 40.1885, 29.0610, NULL, NULL, NULL, NULL);

-- 2. Needs / Products
INSERT INTO needs (id, user_id, title, description, category_id, latitude, longitude, province_id, district_id, allow_comments, status, is_sponsor) VALUES
(1, 2, 'Matkap Lazım', 'Duvara raf takmak için 2 saatliğine darbeli matkap arıyorum.', 15705, 39.9350, 32.8600, 6, 1231, 1, 'active', 1),
(2, 3, 'Laptop Şarj Cihazı', 'Asus marka laptopumun şarjı bozuldu, emanet verebilecek var mı?', 15706, 38.4200, 27.1300, 35, 1819, 1, 'active', 0),
(3, 1, 'Kamp Çadırı', 'Hafta sonu için 2 kişilik çadır arıyorum.', 15707, 41.0100, 28.9800, 34, 1421, 1, 'active', 1),
(4, 4, 'Bisiklet Pompası', 'Lastiğim indi, acil pompa lazım.', 15708, 41.0150, 28.9700, 34, 1421, 1, 'active', 0),
(5, 5, 'Matematik Özel Ders', 'Üniversite sınavına hazırlık için yardımcı olabilecek var mı?', 15709, 40.1900, 29.0600, 16, 1829, 0, 'active', 0);

-- 3. Comments (Halka Açık Mesajlaşma)
INSERT INTO comments (sender_id, need_id, comment) VALUES
(1, 1, 'Bende var Ahmet Bey, gelip alabilirsiniz.'),
(2, 1, 'Harika olur Görkem Bey, teşekkürler.'),
(3, 2, 'Bende yedek var ama ucu uyar mı bakmak lazım.'),
(1, 3, 'Çadırı temiz teslim ederim, merak etmeyin.'),
(2, 3, 'Zemin brandası da getirebilirim.'),
(4, 4, 'Pompa ve yama kiti var, Kadıköy Rıhtımda teslim edebilirim.'),
(5, 5, '50 fotoğraf teslim, 2 saatlik çekim yapabilirim.'),
(3, 6, 'Başlangıç için uygun gitarım var, yanında pena veririm.');

-- 4. Favorites
INSERT INTO favorites (user_id, need_id) VALUES
(1, 1),
(1, 2),
(3, 3),
(5, 1);

-- 5. Ratings
INSERT INTO ratings (rater_id, rated_id, score, comment) VALUES
(1, 2, 5, 'Çok yardımcı oldu, güvenilir biri.'),
(2, 1, 5, 'İşimi çok hızlı çözdü, teşekkürler.'),
(4, 1, 4, 'İyi iletişim, tavsiye ederim.');

-- 6. Notifications
INSERT INTO notifications (user_id, title, body, is_read) VALUES
(1, 'Yeni Yorum!', 'İlanınıza Ahmet Yılmaz tarafından yorum yapıldı.', 0),
(2, 'İlanın Beğenildi', 'Matkap ilanın biri tarafından favoriye eklendi.', 1),
(3, 'Yakınında İlan!', 'İzmir çevresinde yeni bir laptop ilanı var.', 0);

-- 7. Blocked Users
INSERT INTO blocked_users (blocker_id, blocked_id) VALUES
(1, 5); -- Görkem Selin'i engelledi
