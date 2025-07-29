# Gezi Rotası Web Uygulaması

Bu proje, **HTML, CSS, JavaScript ve PHP** kullanılarak geliştirilmiş bir **gezi rotası yönetim sistemidir**. Kullanıcılar platform üzerinde gezip görmek istedikleri yerleri ekleyebilir, bu yerlerle ilgili blog yazıları yazabilir, rotalar oluşturabilir ve çeşitli turlara katılabilir. Ayrıca bir **admin paneli**, **ödeme entegrasyonu**, **harita desteği** ve **chatbot** gibi birçok özellik de projeye entegre edilmiştir.

##  Temel Özellikler

-  Kullanıcı Kayıt ve Giriş Sistemi
-  Rota Oluşturma ve Yer Ekleme
-  Blog Yazısı Paylaşımı
-  Harita Entegrasyonu (iFrame ile)
-  Stripe API ile Ödeme Sistemi
-  Chatbot Entegrasyonu
-  Admin Paneli (Kullanıcı ve içerik yönetimi)
-  PHPMyAdmin destekli veritabanı bağlantısı

##  Kullanılan Teknolojiler

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Veritabanı:** MySQL 
- **API'ler & Entegrasyonlar:**
  - Stripe API (ödeme işlemleri)
  - Google Maps veya benzeri harita API (iFrame ile)
  - Chatbot


##  Kurulum

1. Proje dosyalarını bir web sunucusuna (XAMPP, WAMP vb.) yerleştirin.
2. `database/` klasöründeki `.sql` dosyasını phpMyAdmin üzerinden içe aktarın.
3. Gerekli ayarları `includes/db.php` (veya benzeri dosya) üzerinden yapılandırın.
4. Stripe API anahtarlarınızı ilgili PHP dosyasına ekleyin.
5. Web sunucunuzu başlatın ve `localhost/gezi-rotasi-web` adresinden erişin.


##  Notlar

- Güvenlik önlemleri olarak giriş kontrolü ve form validasyonu yapılmıştır.
- Stripe entegrasyonu test ortamı için yapılandırılmıştır.
- Chatbot kısmı, kullanıcılara sıkça sorulan sorulara otomatik yanıt verecek şekilde yapılandırılmıştır.

