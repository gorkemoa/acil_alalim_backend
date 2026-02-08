<?php
// scripts/seed_extra_comments.php

require_once __DIR__ . '/../api/db.php';

$comments = [
    "Hala duruyor mu? Acil ihtiyacım var.",
    "İrtibata geçebilir miyiz? Mesaj kutunuza bakar mısınız?",
    "Konum tam olarak neresi? Yakınım, hemen gelip alabilirim.",
    "Bende benzeri var, işinizi görür mu? Fotoğraf atabilirim.",
    "Teşekkür ederim, çok yardımcı oldunuz. Harika bir uygulama.",
    "Mesaj attım, dönüş yaparsanız sevinirim.",
    "Telefon numaranızı bulamadım, nasıl ulaşabilirim?",
    "Hafta sonu için lazım, o zamana kadar birine söz verdiniz mi?",
    "Ürün hala temiz mi? Bir problemi var mı?",
    "Çok ihtiyacım vardı, denk gelmesi çok iyi oldu.",
    "Beşiktaş tarafındayım, transferi nasıl yaparız?",
    "Kadıköy'e yakın mısınız?",
    "Yarın sabah gelip almam mümkün mü?",
    "Elinizde başka var mı?",
    "Çok teşekkürler, ilanınız sayenizde işim çözüldü.",
    "Biraz geç gördüm ama umarım başkasına gitmemiştir.",
    "Kısa süreliğine ödünç alabilir miyim?",
    "Hangi mahallede oturuyorsunuz?",
    "Özelden yazdım, kontrol eder misiniz?",
    "Gerçekten çok naziksiniz, bu yardımlaşma harika.",
    "Ücretli mi yoksa ücretsiz mi?",
    "Diğer ilçelere gönderim yapabiliyor musunuz?",
    "Akşam saat 8'den sonra müsait misiniz?",
    "Bende de var ama bozuk, tamir edebilir miyiz?",
    "İhtiyacı olan başka birini tanıyorum, ona yönlendirebilir miyim?",
    "Hangi marka/model bu?",
    "Daha önce kullanılmış mı?",
    "Hemen şimdi gelsem uygun olur mu?",
    "Çok acil lazım, lütfen bana ayırın.",
    "Size nasıl teşekkür etsem az."
];

// Get all users
$stmt = $pdo->query("SELECT id FROM users");
$userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get all needs
$stmt = $pdo->query("SELECT id FROM needs");
$needIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($userIds) || empty($needIds)) {
    die("User veya Need bulunamadı. Önce seed_data.sql çalıştırın.\n");
}

echo "Yorumlar ekleniyor...\n";

$count = 0;
foreach ($needIds as $needId) {
    // Add 8-15 random comments to each need
    $numComments = rand(8, 15);
    for ($i = 0; $i < $numComments; $i++) {
        $senderId = $userIds[array_rand($userIds)];
        $commentText = $comments[array_rand($comments)];
        
        $sql = "INSERT INTO comments (sender_id, need_id, comment, created_at) VALUES (:sender_id, :need_id, :comment, :created_at)";
        $stmt = $pdo->prepare($sql);
        
        // Random date in the last 30 days
        $date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 43200) . ' minutes'));
        
        try {
            $stmt->execute([
                'sender_id' => $senderId,
                'need_id' => $needId,
                'comment' => $commentText,
                'created_at' => $date
            ]);
            $count++;
        } catch (Exception $e) {
            echo "Hata (Need $needId): " . $e->getMessage() . "\n";
        }
    }
}

echo "Toplam $count adet örnek yorum eklendi.\n";
