// Parola alanlarındaki göz düğmesi.
// Yalnızca input türünü değiştirir; değer, ad ve form gönderimi etkilenmez.

export default function parolaGoster() {
    document.addEventListener('click', (olay) => {
        const dugme = olay.target.closest('[data-parola-goster]');

        if (!dugme) {
            return;
        }

        const alan = document.getElementById(dugme.dataset.parolaGoster);

        if (!alan) {
            return;
        }

        const gorunur = alan.type === 'text';

        alan.type = gorunur ? 'password' : 'text';

        dugme.setAttribute('aria-pressed', String(!gorunur));
        dugme.setAttribute('aria-label', gorunur ? 'Parolayı göster' : 'Parolayı gizle');
        dugme.title = gorunur ? 'Parolayı göster' : 'Parolayı gizle';

        dugme.querySelector('[data-durum="kapali"]').hidden = !gorunur;
        dugme.querySelector('[data-durum="acik"]').hidden = gorunur;

        // Odak alanda kalsın ki kullanıcı yazmaya devam edebilsin.
        alan.focus();
        alan.setSelectionRange(alan.value.length, alan.value.length);
    });
}
