// Tampil/sembunyi sandi
document.querySelectorAll('[data-eye]').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.parentElement.querySelector('input');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-label', show ? 'Sembunyikan sandi' : 'Tampilkan sandi');
    btn.style.color = show ? 'var(--green2)' : '';
  });
});

// Kotak OTP: pindah otomatis, tempel kode, backspace
const otp = [...document.querySelectorAll('.otp input')];
otp.forEach((el, i) => {
  el.addEventListener('input', () => {
    el.value = el.value.replace(/\D/g, '').slice(0, 1);
    if (el.value && otp[i + 1]) otp[i + 1].focus();
  });
  el.addEventListener('keydown', e => { if (e.key === 'Backspace' && !el.value && otp[i - 1]) otp[i - 1].focus(); });
  el.addEventListener('paste', e => {
    const t = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, otp.length);
    if (!t) return;
    e.preventDefault();
    [...t].forEach((c, k) => { otp[k].value = c; });
    otp[Math.min(t.length, otp.length - 1)].focus();
  });
});

// Hitung mundur kirim ulang OTP
const cd = document.getElementById('countdown');
if (cd) {
  let s = 59;
  const link = document.getElementById('resend');
  const t = setInterval(() => {
    s--; cd.textContent = '(' + s + ')';
    if (s <= 0) { clearInterval(t); cd.textContent = ''; if (link) link.hidden = false; }
  }, 1000);
}

// Bookmark & bagikan berita
const bm = document.getElementById('bookmark');
if (bm) bm.addEventListener('click', () => {
  const on = bm.classList.toggle('on');
  bm.setAttribute('aria-pressed', on);
});
const sh = document.getElementById('share');
if (sh) sh.addEventListener('click', async () => {
  const data = { title: document.title, url: location.href };
  try {
    if (navigator.share) await navigator.share(data);
    else { await navigator.clipboard.writeText(location.href); sh.title = 'Tautan disalin'; alert('Tautan berita disalin.'); }
  } catch (_) {}
});

// Konfirmasi hapus
document.querySelectorAll('[data-confirm]').forEach(f => {
  f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});
