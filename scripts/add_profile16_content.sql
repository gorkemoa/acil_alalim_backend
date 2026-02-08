USE acil_alalim;

SET @user_id := 16;

-- Ensure profile exists (id=16) so inserts always have a valid owner
INSERT INTO users (id, name, email, password, province_id, district_id)
SELECT 16, 'Görkem Öztürk', 'gorkem@example.com', '$2y$12$olv10vouBOvDg3VMtylhjOM56aGj7YQQONRCapBRZP1I6G.u1g9A2', 34, 1421
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE id = 16);

-- Ensure target account identity is exactly what is requested
UPDATE users
SET name = 'Görkem Öztürk',
    email = 'gorkem@example.com',
    password = '$2y$12$olv10vouBOvDg3VMtylhjOM56aGj7YQQONRCapBRZP1I6G.u1g9A2'
WHERE id = @user_id;

-- Product 1
SET @need1 := NULL;
INSERT INTO needs (user_id, title, description, category_id, latitude, longitude, province_id, district_id, status)
SELECT @user_id,
       'Profesyonel Matkap Seti Arıyorum',
       'Mutfak dolabı ve banyo askılık montajı için darbeli matkap seti arıyorum. Uç seti ve uzatma aparatı da olursa çok iyi olur. 2 gün kullanıp aynı gün teslim ederim.',
       15705,
       41.0351, 28.9833,
       34, 1421,
       'active'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM users WHERE id = @user_id);
SET @need1 := LAST_INSERT_ID();
INSERT INTO need_images (need_id, image_path, is_main)
SELECT @need1, x.image_path, x.is_main
FROM (
    SELECT 'drill_1.jpg' AS image_path, 1 AS is_main
    UNION ALL SELECT 'drill_2.jpg', 0
    UNION ALL SELECT 'img_drill.png', 0
    UNION ALL SELECT 'camera_1.jpg', 0
) x
WHERE @need1 IS NOT NULL
  AND @need1 > 0
  AND EXISTS (SELECT 1 FROM needs WHERE id = @need1);

-- Product 2
SET @need2 := NULL;
INSERT INTO needs (user_id, title, description, category_id, latitude, longitude, province_id, district_id, status)
SELECT @user_id,
       'Laptop Şarj Adaptörü Lazım (Asus 65W)',
       'Acil kullanım için 65W Asus uyumlu adaptör arıyorum. 1-2 gün emanet de olabilir, uygun fiyatlı ikinci el de değerlendirebilirim.',
       15706,
       41.0327, 28.9772,
       34, 1421,
       'active'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM users WHERE id = @user_id);
SET @need2 := LAST_INSERT_ID();
INSERT INTO need_images (need_id, image_path, is_main)
SELECT @need2, x.image_path, x.is_main
FROM (
    SELECT 'img_charger.png' AS image_path, 1 AS is_main
    UNION ALL SELECT 'camera_2.jpg', 0
    UNION ALL SELECT 'camera_3.jpg', 0
    UNION ALL SELECT 'tent_1.jpg', 0
) x
WHERE @need2 IS NOT NULL
  AND @need2 > 0
  AND EXISTS (SELECT 1 FROM needs WHERE id = @need2);

-- Product 3
SET @need3 := NULL;
INSERT INTO needs (user_id, title, description, category_id, latitude, longitude, province_id, district_id, status)
SELECT @user_id,
       '2 Kişilik Kamp Çadırı Arıyorum',
       'Hafta sonu Belgrad Ormanı kampı için sağlam, su geçirmez 2 kişilik çadır arıyorum. Zemin brandası ve taşıma çantası olursa önceliğimdir.',
       15707,
       41.0384, 28.9704,
       34, 1421,
       'active'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM users WHERE id = @user_id);
SET @need3 := LAST_INSERT_ID();
INSERT INTO need_images (need_id, image_path, is_main)
SELECT @need3, x.image_path, x.is_main
FROM (
    SELECT 'tent_1.jpg' AS image_path, 1 AS is_main
    UNION ALL SELECT 'tent_2.jpg', 0
    UNION ALL SELECT 'img_tent.png', 0
    UNION ALL SELECT 'bike_1.jpg', 0
) x
WHERE @need3 IS NOT NULL
  AND @need3 > 0
  AND EXISTS (SELECT 1 FROM needs WHERE id = @need3);

-- Product 4
SET @need4 := NULL;
INSERT INTO needs (user_id, title, description, category_id, latitude, longitude, province_id, district_id, status)
SELECT @user_id,
       'Bisiklet Pompası ve Yama Kiti Gerekiyor',
       'Şehir bisikletimin arka lastiği indi. El pompası + hızlı yama kiti arıyorum. Kadıköy çevresinden hızlı teslim alabilirim.',
       15708,
       41.0309, 29.0055,
       34, 1421,
       'active'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM users WHERE id = @user_id);
SET @need4 := LAST_INSERT_ID();
INSERT INTO need_images (need_id, image_path, is_main)
SELECT @need4, x.image_path, x.is_main
FROM (
    SELECT 'bike_1.jpg' AS image_path, 1 AS is_main
    UNION ALL SELECT 'bike_2.jpg', 0
    UNION ALL SELECT 'drill_1.jpg', 0
    UNION ALL SELECT 'camera_1.jpg', 0
) x
WHERE @need4 IS NOT NULL
  AND @need4 > 0
  AND EXISTS (SELECT 1 FROM needs WHERE id = @need4);

-- Product 5
SET @need5 := NULL;
INSERT INTO needs (user_id, title, description, category_id, latitude, longitude, province_id, district_id, status)
SELECT @user_id,
       'Fotoğrafçı Arıyorum (Doğum Günü Çekimi)',
       'Küçük bir doğum günü organizasyonu için 2 saatlik fotoğraf çekimi yapabilecek birini arıyorum. Portföy paylaşabilenler yazsın.',
       15709,
       41.0273, 28.9951,
       34, 1421,
       'active'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM users WHERE id = @user_id);
SET @need5 := LAST_INSERT_ID();
INSERT INTO need_images (need_id, image_path, is_main)
SELECT @need5, x.image_path, x.is_main
FROM (
    SELECT 'camera_1.jpg' AS image_path, 1 AS is_main
    UNION ALL SELECT 'camera_2.jpg', 0
    UNION ALL SELECT 'camera_3.jpg', 0
    UNION ALL SELECT 'img_6988cab238266.png', 0
) x
WHERE @need5 IS NOT NULL
  AND @need5 > 0
  AND EXISTS (SELECT 1 FROM needs WHERE id = @need5);

-- Product 6
SET @need6 := NULL;
INSERT INTO needs (user_id, title, description, category_id, latitude, longitude, province_id, district_id, status)
SELECT @user_id,
       'Akustik Gitar Arıyorum (Başlangıç Seviyesi)',
       'Yeni başlayacağım için uygun fiyatlı, temiz durumda akustik gitar arıyorum. Pena ve kılıf varsa ekstra iyi olur.',
       15707,
       41.0221, 28.9876,
       34, 1421,
       'active'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM users WHERE id = @user_id);
SET @need6 := LAST_INSERT_ID();
INSERT INTO need_images (need_id, image_path, is_main)
SELECT @need6, x.image_path, x.is_main
FROM (
    SELECT 'guitar_1.jpg' AS image_path, 1 AS is_main
    UNION ALL SELECT 'guitar_2.jpg', 0
    UNION ALL SELECT 'tent_2.jpg', 0
    UNION ALL SELECT 'img_tent.png', 0
) x
WHERE @need6 IS NOT NULL
  AND @need6 > 0
  AND EXISTS (SELECT 1 FROM needs WHERE id = @need6);

-- Debug output
SELECT id, name, email
FROM users
WHERE id = @user_id;

SELECT id, user_id, title, created_at
FROM needs
WHERE user_id = @user_id
ORDER BY id DESC
LIMIT 10;
