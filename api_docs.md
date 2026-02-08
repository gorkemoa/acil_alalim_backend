# 🚀 Acil Alalım API - Profesyonel Dökümantasyon

## 🔑 Giriş ve Güvenlik
- **Base URL:** `http://localhost:8000/index.php?path=`
- **Kimlik Doğrulama:** `Authorization: Bearer <TOKEN>`

---

## 👤 1. Kullanıcı ve Hesap Yönetimi (AUTH)

| Özellik | Metot | Endpoint | Açıklama |
| :--- | :--- | :--- | :--- |
| **Üye Ol** | POST | `/auth/register` | Yeni kullanıcı oluşturur. |
| **Giriş Yap** | POST | `/auth/login` | Token ve kullanıcı bilgisi döner. |
| **Şifremi Unuttum**| POST | `/auth/forgot-password` | E-postaya sıfırlama kodu gönderir (Token döner). |
| **Şifre Sıfırla** | POST | `/auth/reset-password` | Token ile yeni şifre belirler. |
| **Profilim** | GET | `/auth/profile` | Giriş yapan kullanıcının bilgilerini getirir. |
| **Kullanıcı Profili**| GET | `/auth/profile/{id}` | Başka bir kullanıcının genel profilini getirir. |
| **Profil Güncelle** | POST | `/auth/profile` | İsim, şehir, profil resmi vb. günceller. |
| **Hesabı Sil** | DELETE | `/auth/delete-account` | Kullanıcıyı kalıcı olarak siler. |

---

## 📦 2. Ürün / İhtiyaç Yönetimi (PRODUCTS)

| Özellik | Metot | Endpoint | Açıklama |
| :--- | :--- | :--- | :--- |
| **Ürün Yükle** | POST | `/products` | Fotoğraflı ilan oluşturur (Multipart/form-data). `province_id`, `district_id`, `category_id` zorunlu. |
| **Tüm Ürünler** | GET | `/products` | Kategori/il/ilçe id ile listelenir (response `*_id` alanları içerir). |
| **Ürün Detayı** | GET | `/products/{id}` | Ürün ve resim detaylarını getirir. |
| **Ürün Güncelle** | PUT | `/products/{id}` | İlan detaylarını düzenler. |
| **Ürün Sil** | DELETE | `/products/{id}` | İlanı kaldırır. |
| **Canlı Arama** | GET | `/products/search?q=...` | Başlık ve şehre göre hızlı arama. |
| **Sponsor Yap** | PUT | `/products/{id}/sponsor` | Ürünü öne çıkarır (`?status=1`). |

---

## ⭐ 3. Favori İşlemleri

| Özellik | Metot | Endpoint | Açıklama |
| :--- | :--- | :--- | :--- |
| **Favoriye Ekle** | POST | `/favorites` | `{"need_id": 1}` şeklinde ekleme. |
| **Favorilerim** | GET | `/favorites` | Favorideki ürünlerin listesi. |
| **Favoriden Çıkar**| DELETE | `/favorites` | `{"need_id": 1}` şeklinde silme. |

---

## 🚫 4. Engelleme ve Güvenlik

| Özellik | Metot | Endpoint | Açıklama |
| :--- | :--- | :--- | :--- |
| **Kullanıcı Engelle**| POST | `/users/block` | `{"user_id": 5}` şeklinde engelleme. |
| **Engeli Kaldır** | POST | `/users/unblock` | Engel listesinden çıkarır. |
| **Engel Listesi** | GET | `/users/blocked-list` | Engellenen kullanıcıları görür. |
| **Kullanıcı Şikayet**| POST | `/reports` | Taciz veya sahte içerik bildirimi. |

---

## 🔔 5. Bildirimler

| Özellik | Metot | Endpoint | Açıklama |
| :--- | :--- | :--- | :--- |
| **Bildirimler** | GET | `/notifications` | Tüm bildirimleri getirir. |
| **Okundu Yap** | PUT | `/notifications/{id}` | Bildirimi okundu işaretler. |

---

## 💬 6. Yorumlar

| Özellik | Metot | Endpoint | Açıklama |
| :--- | :--- | :--- | :--- |
| **Yorum Ekle** | POST | `/comments` | İlan altına halka açık yorum yazar. |
| **Yorumları Gör** | GET | `/comments/{need_id}` | İlana ait tüm yorumları listeler. |
