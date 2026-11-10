const favs = {
  get() { return APP.favs; },
  has(id) { return APP.favs.includes(id); },
  async toggle(id) {
    const res = await fetch(APP.base + 'pages/favoriet.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': APP.csrf },
      body: JSON.stringify({ id })
    });
    const d = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(d.fout || 'Opslaan mislukt.');
    APP.favs = d.favs;
    return d.aan;
  }
};
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

const escRecept = r => ({
  ...r,
  titel: esc(r.titel), cat: esc(r.cat), intro: esc(r.intro), emoji: esc(r.emoji || ''),
  land: r.land ? esc(r.land) : undefined, vlag: r.vlag ? esc(r.vlag) : undefined,
  info: r.info.map(esc),
  ing: r.ing.map(i => ({ ...i, t: esc(i.t) })),
  stappen: r.stappen.map(([t, u]) => [esc(t), esc(u)])
});
(APP.eigen || []).forEach(r => RECEPTEN.push(escRecept(r)));

const foto = r => APP.fotos[r.id] || r.img || null;
const afb = r => foto(r)
  ? `<img src="${APP.base}${foto(r)}" alt="${r.titel}" loading="lazy">`
  : `<div class="plaats" role="img" aria-label="${r.titel}">${r.emoji || "🍽️"}</div>`;

function kaart(r) {
  const aan = favs.has(r.id);
  return `<article class="kaart">
    <a class="kaart-link" href="${APP.base}pages/detail.php?id=${r.id}">
      <div class="foto">${afb(r)}<span class="badge">${r.cat}</span></div>
      <h3>${r.titel}</h3><p>${r.vlag ? r.vlag + ' ' : ''}${r.cat} · ${r.info[0]} · ${r.info[2]}</p>
    </a>
    <button class="hart ${aan ? 'aan' : ''}" data-id="${r.id}" aria-pressed="${aan}" aria-label="Bewaar ${r.titel} in bibliotheek">♥</button>
  </article>`;
}

function telling() {
  const t = document.getElementById('telling'), n = APP.favs.length;
  if (t) { t.textContent = n; t.hidden = !n; }
}

document.addEventListener('click', async e => {
  const b = e.target.closest('.hart');
  if (!b) return;
  if (!APP.gebruiker) {
    location.href = APP.base + 'pages/login.php?terug=' + encodeURIComponent(location.pathname.split('/').pop() + location.search);
    return;
  }
  let aan;
  try { aan = await favs.toggle(b.dataset.id); }
  catch (err) { alert(err.message); return; }
  document.querySelectorAll(`.hart[data-id="${b.dataset.id}"]`).forEach(x => {
    x.classList.toggle('aan', aan);
    x.setAttribute('aria-pressed', aan);
  });
  telling();
  toast(aan ? 'Toegevoegd aan je bibliotheek ♥' : 'Verwijderd uit je bibliotheek');
  document.dispatchEvent(new Event('favwijzig'));
});

function toast(tekst) {
  const box = document.getElementById('toast');
  if (!box) return;
  const t = document.createElement('div');
  t.className = 'toast';
  t.textContent = tekst;
  box.appendChild(t);
  setTimeout(() => t.remove(), 2600);
}

const menuKnop = document.querySelector('.menu-knop'), menu = document.querySelector('.menu');
if (menuKnop && menu) menuKnop.addEventListener('click', () => {
  menuKnop.setAttribute('aria-expanded', menu.classList.toggle('open'));
});
