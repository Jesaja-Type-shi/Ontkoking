const p = new URLSearchParams(location.search);
let cat = p.get('cat') || 'Alles', land = p.get('land') || 'Alle landen';
const veld = document.getElementById('zoekveld'); veld.value = p.get('zoek') || '';
const cats = ['Alles', 'Ontbijt', 'Lunch', 'Diner', 'Soep', 'Salade', 'Snacks', 'Dessert', 'Drankjes', 'Vegetarisch'];
const landen = new Map(RECEPTEN.filter(r => r.land).map(r => [r.land, r.vlag]));
document.getElementById('cats').innerHTML = cats.map(c => `<button class="chip" data-c="${c}">${c}</button>`).join('');
document.getElementById('landen').innerHTML = ['Alle landen', ...landen.keys()].map(l => `<button class="chip" data-l="${l}">${landen.get(l) || '🌍'} ${l}</button>`).join('');
function toon() {
  const q = veld.value.trim().toLowerCase();
  const lijst = RECEPTEN.filter(r => (cat === 'Alles' || r.cat === cat || (r.tags || []).includes(cat)) &&
    (land === 'Alle landen' || r.land === land) &&
    (r.titel + ' ' + r.cat + ' ' + (r.land || '') + ' ' + r.ing.map(i => i.t).join(' ')).toLowerCase().includes(q));
  document.querySelectorAll('.chip').forEach(b => b.classList.toggle('actief', b.dataset.c === cat || b.dataset.l === land));
  document.getElementById('raster').innerHTML = lijst.length ? lijst.map(kaart).join('') : '<p class="leeg">Geen recepten gevonden.</p>';
}
document.getElementById('cats').addEventListener('click', e => { const b = e.target.closest('[data-c]'); if (b) { cat = b.dataset.c; toon(); } });
document.getElementById('landen').addEventListener('click', e => { const b = e.target.closest('[data-l]'); if (b) { land = b.dataset.l; toon(); } });
veld.addEventListener('input', toon);
toon();
document.getElementById('zoekform').addEventListener('submit', e => { e.preventDefault(); toon(); });
