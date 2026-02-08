<?php
// scripts/seed_smart_comments.php

require_once __DIR__ . '/../api/db.php';

// Context-aware comment templates
$contextTemplates = [
    'elektronik' => [
        'keywords' => ['laptop', 'şarj', 'kamera', 'dslr', 'fotoğraf', 'bilgisayar', 'telefon', 'tablet'],
        'comments' => [
            "Bataryası ne kadar gidiyor?",
            "Şarj aleti orijinal mi?",
            "Kozmetik olarak ne durumda, çizik var mı?",
            "Garantisi devam ediyor mu?",
            "Hangi model tam olarak? Model numarasını yazar mısınız?",
            "Yarın gelip test edip alabilir miyim?",
            "Bende bir alt modeli var, takas düşünür müsünüz?",
            "Faturası mevcut mu?",
        ]
    ],
    'ev_tadilat' => [
        'keywords' => ['matkap', 'tornavida', 'çekiç', 'alet', 'tadilat', 'boya', 'raf', 'monte'],
        'comments' => [
            "Uçları tam mı? Beton delmek için uygun mu?",
            "Sadece 1 saatliğine lazım, yardımcı olur musunuz?",
            "Komşuyuz galiba, hangi bloktasınız?",
            "Bende de vardı ama bozuldu, emanet alabilir miyim?",
            "İşim bitince hemen teslim ederim, çok acil.",
            "Şarjlı mı yoksa kablolu mu?",
            "Darbeli özelliği var mı?",
        ]
    ],
    'spor_outdoor' => [
        'keywords' => ['bisiklet', 'pompa', 'çadır', 'kamp', 'mat', 'tulum', 'dağ', 'outdoor'],
        'comments' => [
            "Çadır kaç kişilik? Su geçiriyor mu?",
            "Pompa iğne uçlu mu yoksa kalın uçlu mu?",
            "Bisikletin viteslerinde sorun var mı?",
            "Hafta sonu ekipçe kampa gideceğiz, çok makbule geçer.",
            "Kask da veriyor musunuz yanında?",
            "Lastik ebadı nedir?",
            "Kurulumu kolay mı, yardımcı olur musunuz?",
        ]
    ],
    'enstruman' => [
        'keywords' => ['gitar', 'akustik', 'bağlama', 'piyano', 'keman', 'müzik', 'enstrüman', 'nota'],
        'comments' => [
            "Telleri yeni mi, paslanma var mı?",
            "Kılıfı (gigbag) var mı taşıma için?",
            "Akordu yapılı mı?",
            "Yeni başlamak istiyorum, uygun mudur sizce?",
            "Sesi nasıl, bir video atma şansınız var mı?",
            "Eşik yüksekliği nasıl? Çalımı kolay mı?",
        ]
    ],
    'egitim' => [
        'keywords' => ['ders', 'matematik', 'özel', 'sınav', 'yks', 'lgs', 'kitap', 'eğitim'],
        'comments' => [
            "Hangi günler uygunsunuz?",
            "Online mı yoksa yüz yüze mi veriyorsunuz?",
            "Konu anlatımı mı yapıyoruz yoksa sadece soru çözümü mü?",
            "İlk ders ücretsiz deneme yapabilir miyiz?",
            "Hangi seviye için anlatıyorsunuz?",
            "Güneşli tarafındayım, eve gelebilir misiniz?",
        ]
    ],
    'genel' => [
        'comments' => [
            "Konum tam olarak neresi?",
            "Hala güncel mi?",
            "Özelden mesaj attım, bakar mısınız?",
            "Çok teşekkürler böyle bir ilan açtığınız için.",
            "İhtiyacım olan tam olarak buydu.",
            "Bugün içinde alabilir miyim?",
            "Nasıl irtibat kurabiliriz?",
            "Sizinle daha önce de görüşmüştük galiba.",
        ]
    ]
];

// Get all users
$stmt = $pdo->query("SELECT id, name FROM users");
$users = $stmt->fetchAll();
$userIds = array_column($users, 'id');

// Get all needs
$stmt = $pdo->query("SELECT id, title, description FROM needs");
$needs = $stmt->fetchAll();

if (empty($userIds) || empty($needs)) {
    die("User veya Need bulunamadı.\n");
}

echo "Akıllı yorumlar oluşturuluyor ve ekleniyor...\n";

// Clear old comments (Optional: user's choice, but usually better for "non-static" feel to refresh)
// $pdo->exec("DELETE FROM comments");

$count = 0;
foreach ($needs as $need) {
    $title = mb_strtolower($need['title'], 'UTF-8');
    $relevantPool = [];
    
    // Find relevant pool based on keywords
    foreach ($contextTemplates as $category => $data) {
        if ($category === 'genel') continue;
        foreach ($data['keywords'] as $keyword) {
            if (strpos($title, $keyword) !== false) {
                $relevantPool = array_merge($relevantPool, $data['comments']);
                break;
            }
        }
    }
    
    // Always mix with general comments
    $finalPool = array_merge($relevantPool, $contextTemplates['genel']['comments']);
    
    // Add 5-10 random comments per need
    $numAdd = rand(5, 12);
    for ($i = 0; $i < $numAdd; $i++) {
        $sender = $users[array_rand($users)];
        $commentRaw = $finalPool[array_rand($finalPool)];
        
        // Add some dynamic variety to the text (non-static)
        $prefixes = ["", "Merhaba, ", "Selamlar, ", "Hayırlı işler, ", "İyi günler, "];
        $suffixes = ["", " :)", " ?", "...", "!", " Teşekkürler."];
        
        $commentText = $prefixes[array_rand($prefixes)] . $commentRaw . $suffixes[array_rand($suffixes)];
        
        $sql = "INSERT INTO comments (sender_id, need_id, comment, created_at) VALUES (:sender_id, :need_id, :comment, :created_at)";
        $stmt = $pdo->prepare($sql);
        
        // Random date in the last 15 days
        $date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 21600) . ' minutes'));
        
        try {
            $stmt->execute([
                'sender_id' => $sender['id'],
                'need_id' => $need['id'],
                'comment' => $commentText,
                'created_at' => $date
            ]);
            $count++;
        } catch (Exception $e) {
            // Skip duplicates if there's a constraint, but here it's fine
        }
    }
}

echo "Toplam $count adet bağlam duyarlı (dinamik) yorum eklendi.\n";
echo "İpuçları: \n";
echo "- Yorumlar ilan başlıklarına göre (Matkap, Gitar, Laptop vb.) özelleştirildi.\n";
echo "- Selamlamalar ve emojiler rastgele eklendi.\n";
echo "- Tarihler son 15 güne rastgele yayıldı.\n";
