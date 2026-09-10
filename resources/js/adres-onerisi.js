// Kayıt formunda şirket adından adres önerisi.
// Sunucu aynı çevrimi yeniden yapar; buradaki yalnızca anlık geri bildirimdir.

const TURKCE = { ç: 'c', Ç: 'c', ğ: 'g', Ğ: 'g', ı: 'i', İ: 'i', ö: 'o', Ö: 'o', ş: 's', Ş: 's', ü: 'u', Ü: 'u' };

const adreseCevir = (metin) =>
    String(metin ?? '')
        .replace(/[çÇğĞıİöÖşŞüÜ]/g, (harf) => TURKCE[harf])
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        // Sunucudaki Str::slug ile aynı davranış: noktalama silinir,
        // boşluk ve alt çizgi tireye dönüşür.
        .replace(/[^a-z0-9\s_-]+/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');

export default function adresOnerisi() {
    const sirket = document.getElementById('company');
    const adres = document.getElementById('slug');
    const onizleme = document.querySelector('[data-adres-onizleme]');

    if (!sirket || !adres) {
        return;
    }

    // Kullanıcı adresi kendi eliyle değiştirdiyse öneriyle ezmeyiz.
    let elleDegistirildi = adres.value.trim() !== '';

    const onizlemeyiYaz = () => {
        if (!onizleme) {
            return;
        }

        onizleme.textContent = adres.value ? adres.value + onizleme.dataset.adresOnizleme : '';
    };

    sirket.addEventListener('input', () => {
        if (elleDegistirildi) {
            return;
        }

        adres.value = adreseCevir(sirket.value);
        onizlemeyiYaz();
    });

    adres.addEventListener('input', () => {
        elleDegistirildi = adres.value.trim() !== '';
        onizlemeyiYaz();
    });

    // Alandan çıkınca yazılanı geçerli bir adrese çevir.
    adres.addEventListener('blur', () => {
        adres.value = adreseCevir(adres.value);
        onizlemeyiYaz();
    });

    onizlemeyiYaz();
}
