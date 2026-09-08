// Satış formundaki satırların eklenmesi, silinmesi ve toplam hesabı.
// Sunucu aynı hesabı yeniden yapar; buradaki hesap yalnızca kullanıcıya anlık geri bildirimdir.

const sayiyaCevir = (metin) => {
    const temiz = String(metin ?? '').replace(/\s/g, '');

    if (temiz === '') {
        return 0;
    }

    // "1.499,90" -> 1499.90 ; "1499.90" -> 1499.90
    const normal = temiz.includes(',') ? temiz.replace(/\./g, '').replace(',', '.') : temiz;
    const deger = Number.parseFloat(normal);

    return Number.isFinite(deger) ? deger : 0;
};

const paraYaz = (deger) =>
    deger.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function satisFormu() {
    const govde = document.getElementById('satir-govdesi');
    const sablon = document.getElementById('satir-sablonu');
    const ekleDugmesi = document.getElementById('satir-ekle');

    if (!govde || !sablon || !ekleDugmesi) {
        return;
    }

    let sayac = 0;

    const hesapla = () => {
        let araToplam = 0;
        let kdvToplam = 0;

        govde.querySelectorAll('.satir').forEach((satir) => {
            const urun = satir.querySelector('[data-alan="urun"]');
            const miktar = sayiyaCevir(satir.querySelector('[data-alan="miktar"]').value);
            const fiyat = sayiyaCevir(satir.querySelector('[data-alan="fiyat"]').value);
            const tutar = miktar * fiyat;

            const secili = urun.selectedOptions[0];
            const kdvOrani = secili ? Number.parseFloat(secili.dataset.kdv ?? '0') : 0;

            satir.querySelector('[data-alan="tutar"]').textContent = paraYaz(tutar);

            araToplam += tutar;
            kdvToplam += (tutar * kdvOrani) / 100;
        });

        const indirim = Math.min(sayiyaCevir(document.getElementById('indirim')?.value), araToplam);

        if (indirim > 0 && araToplam > 0) {
            kdvToplam *= (araToplam - indirim) / araToplam;
        }

        document.getElementById('ozet-ara-toplam').textContent = paraYaz(araToplam);
        document.getElementById('ozet-kdv').textContent = paraYaz(kdvToplam);
        document.getElementById('ozet-toplam').textContent = paraYaz(araToplam - indirim + kdvToplam);
    };

    const satirEkle = () => {
        const parca = sablon.content.cloneNode(true);
        const satir = parca.querySelector('.satir');

        satir.querySelectorAll('[name]').forEach((alan) => {
            alan.name = alan.name.replace('__INDEX__', String(sayac));
        });

        sayac += 1;
        govde.appendChild(parca);
        hesapla();
    };

    // Ürün seçilince fiyatı ürün kartından doldur; kullanıcı yine de değiştirebilir.
    govde.addEventListener('change', (olay) => {
        if (olay.target.dataset.alan === 'urun') {
            const secili = olay.target.selectedOptions[0];
            const fiyatAlani = olay.target.closest('.satir').querySelector('[data-alan="fiyat"]');

            if (secili?.dataset.fiyat) {
                fiyatAlani.value = secili.dataset.fiyat;
            }
        }

        hesapla();
    });

    govde.addEventListener('input', hesapla);

    govde.addEventListener('click', (olay) => {
        if (olay.target.dataset.alan !== 'sil') {
            return;
        }

        olay.target.closest('.satir').remove();

        if (govde.querySelectorAll('.satir').length === 0) {
            satirEkle();
        }

        hesapla();
    });

    document.getElementById('indirim')?.addEventListener('input', hesapla);
    ekleDugmesi.addEventListener('click', satirEkle);

    satirEkle();
}
