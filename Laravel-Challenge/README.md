
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>  

<p align="center">  
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>  
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>  
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>  
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>  
</p>  

Uygulama geliştirilirken kullanılanlar:
---
______

```bash
- PHP 8.0
- Laravel 7
- PhpStorm 2021.2.1
- Çalışma localhost üzerinde yapıldığı için cache - driver=database olarak belirlenmiştir. 
  Performansı artırmak için redis veya memcached paketleri kullanılabilir. 
  Driver adı kullanılan cache türüne göre değiştirerek performans artırımı yapılabilir.
- BTREE algoritması kullanılarak tablolara indeksleme yapılmıştır.
- API route için AuthClientToken isimli middleware oluşturularak purchase 
  ve check subscription işlemlerinde token kontrolü sağlanmaktadır.
- Standart cache süreleri 12 saat, client_token ve u_id bilgilerini tutan cache süreleri 7 gün, 
  check subscription için kullanılan cache süresi 5 dakika olarak belirlenmiştir.
```

**Kurulum yapıldıktan sonra app/Http/Controllers/Api/PurchasesController.php dosyası içerisinde 19. satırda bulunan http://your_domain/api/v1/ios isteğinde kendi domaininizi yazarak uygulamayı çalıştırabilirsiniz.**

## Api Listesi

**Register**

> POST api/v1/register

- Üyelik işlemi (token alma) için kullanılır. u_id, app_id, language ve os bilgileri parametre olarak gönderilmelidir.
- İstek geldiğinde, cache üzerinde mevcut u_id=client_token eşleşmesi kontrol edilir.
  Varsa cevap olarak döndürülür. Yoksa veritabanı üzerinde u_id kaydı kontrol edilir.
  Varsa eşleşen client_token cevap olarak döndürülür. Yoksa devices, register tablosuna kayıt edilir.
- Aynı u_id ile defalarca register isteği gelebilir. Bu durum handle edilerek register OK
  cevabı döndürülmektedir. Register OK cevabında her device’a farklı olarak bir
  client-token hazırlanıp response da client_token döndürülmektedir.
-
```bash
{
    "result": "true",
    "message": "account created",
    "client_token": "$2y$10$jxyAh.Ldi4wQhUnT0LKHN.UotG0Il7xVmiat1RilY6bFbpR0sBwJK"
}
```

**Google (Android) & Apple (IOS) API Mock**

> POST api/v1/google
>
> POST api/v1/ios

- Parametre olarak receipt gönderilme işlemi ile status ve expire_date bilgileri elde edilmektedir. (örneğin; receipt=0554135a82ahustdc596318190e89ed91)

```bash
{
    "status": "true",
    "expire_date": "2021-08-30 08:22:28"
}
```

**Check Subscription**

> GET api/v1/get-subscriptions

- Parametre olarak client_token gönderilerek abonelik durumu & satın alma bilgilerine erişim sağlanır.
- İstek geldiğinde, client_token daha önce oluşturulmuş AuthClientToken isimli middleware ile kontrol edilir.  Eşleşme yoksa sonuç "false" ve mesaj "unauthorized access" olarak döndürülür.
- Cache üzerinde mevcut client_token ve purchase listesinde eşleşme var mı kontrol edilerek
  varsa döndürülür, yoksa veritabanında status=1 olan ve expire_date>now() olan kayıtlar getirilir. Cache'e yazılır ve cevap döndürülür.

```bash
{
    "result": "true",
    "subscriptions_list": [
        {
            "id": 3,
            "u_id": 35,
            "receipt": "0554135a82ahustdc596318190e89ed91",
            "status": 1,
            "expire_date": "2021-08-30 11:23:57",
            "created_at": "2021-08-30T08:23:58.000000Z",
            "updated_at": "2021-08-30T08:23:58.000000Z"
        }
    ]
}
```

**Purchase**

> POST-GET api/v1/purchase

- Parametre olarak client_token ve receipt satın alma işlemi yapılmaktadır.
- İstek geldiğinde, client_token daha önce oluşturulmuş AuthClientToken isimli middleware ile kontrol edilir.
- Aldığı receipt string değerinin son karakteri tek bir sayı ise OK cevabını vererek bu cevap
  içerisinde status:true ve expire-date: Y-m-d H:i:s UTC-6 time zone dönmektedir. Değilse status:false dönmektedir.
- API’ımız client’tan aldığı isteği IOS ya da Google mock platformlarında doğrulayarak
  sonucu DB’ye işleyerek client’a response dönmektedir. Bu çalışmada IOS mock doğrulama tercih edilmiştir.
  (Kod blokları içerisinde PurchasesController dosyası içerisinde 20. satırda ios yerine google yazılarak google mock test edilebilir.)
```bash
{
    "result": "true",
    "message": "purchase successful"
}
```

## Genel anlamda aşamalardan kısaca bahsetmek gerekirse:

- Tüm sonuçlar istendiği gibi gerçekleştiği taktirde ilk önce Register işlemi ile kaydolma işlemi yapılarak client_token oluşturulmaktadır.
- Google (Android) & Apple (IOS) API Mock test edilerek status ve expire_date verileri elde edilmektedir.
- Purchase işlemi ile satın alma işlemi yapılarak veri tabanına (id, u_id, receipt, status, expire_date) kaydedilmektedir.
- Check Subscription işlemi ile de client_token üzerinden sorgulama yapılarak kullanıcının satın alma geçmişi görüntülenmektedir.

## Yapılan Teknik Koşullar

```bash
- Her appin iOS ve Google credential bilgileri farklıdır.
- DB şeması, sql dosyası ve migration olarak verilmiştir. 
- DB tablolarının özellikle device tablosunun milyonlarca 
  kayıt altında çalışması beklenmektedir. 
- API tarafında bir çok farklı appten aynı anda HTTP istekler 
  gelecek yüksek trafik olacağı göz önünde bulundurulmuştur.
- Bir device bir app altında aynı anda sadece bir aboneliği bulunabilir
- Bir device farklı appler olmak şartı ile birden fazla aboneliği olabilir. 
```
