const landSlug = l => l.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/\s+/g, '-');
const landFoto = (l, vlag) => APP.landfotos[landSlug(l)] ? `<img src="${APP.base}${APP.landfotos[landSlug(l)]}" alt="">` : vlag;
document.getElementById('populair').innerHTML = RECEPTEN.filter(foto).slice(0, 6).map(kaart).join('');
const fotos = RECEPTEN.filter(foto).map(r => APP.base + foto(r));
const hero = document.getElementById('hero'); let i = 0;
const zet = () => hero.style.backgroundImage = `linear-gradient(180deg,rgba(30,20,15,.3),rgba(30,20,15,.65)),url("${fotos[i]}")`;
zet(); setInterval(() => { i = (i + 1) % fotos.length; zet(); }, 4000);
const landen = new Map(RECEPTEN.filter(r => r.land).map(r => [r.land, r.vlag]));
document.getElementById('landen').innerHTML = [...landen].map(([l, v]) => `<a class="rondje" href="${APP.base}pages/recepten.php?land=${encodeURIComponent(l)}"><span>${landFoto(l, v)}</span>${l}</a>`).join('');
