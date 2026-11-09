const r = RECEPTEN.find(x => x.id === new URLSearchParams(location.search).get('id'));
const el = document.getElementById('detail');
if (!r) {
  el.innerHTML = '<p class="leeg">Dit recept bestaat niet. <a href="recepten.php">Terug naar recepten</a></p>';
} else {
  document.title = r.titel + ' – Ontkoking';
  const eenheid = r.info[3].replace(/^\d+\s*/, '');
  const getal = (n, p) => (n * p / r.basis).toLocaleString('nl-NL', { maximumFractionDigits: 1 });
  const ingredienten = p => r.ing.map(i => `<li><label><input type="checkbox"><span>${i.n !== undefined ? getal(i.n, p) + ' ' : ''}${i.t}</span></label></li>`).join('');
  const aan = favs.has(r.id);
  const gerelateerd = RECEPTEN.filter(x => x.id !== r.id)
    .map(x => [x, (r.land && x.land === r.land ? 2 : 0) + (x.cat === r.cat ? 1 : 0)])
    .filter(([, s]) => s > 0).sort((a, b) => b[1] - a[1]).slice(0, 3).map(([x]) => x);
  el.innerHTML = `
    <div class="terugrij"><a class="terug" href="recepten.php">← Terug naar recepten</a><button type="button" class="knop kleinknop" onclick="window.print()">🖨️ Print recept</button></div>
    <div class="detail-raster">
      <div class="links">
        <div class="foto-groot">
          ${afb(r)}
          <button class="hart groot ${aan ? 'aan' : ''}" data-id="${r.id}" aria-pressed="${aan}">♥</button>
          <div class="naam-balk"><span>${r.titel}</span></div>
        </div>
        <section class="blok">
          <h2>Wat heb je nodig?</h2>
          <p>${r.intro}</p>
          <div class="info">${r.land ? `<span>${r.vlag} ${r.land}</span>` : ''}${r.info.map(i => `<span>${i}</span>`).join('')}</div>
          <p>Aantal: <select id="porties">${r.opties.map(o => `<option value="${o}" ${o === r.basis ? 'selected' : ''}>${o}</option>`).join('')}</select> ${eenheid}</p>
          <ul class="ingr" id="ingr">${ingredienten(r.basis)}</ul>
        </section>
        <section class="blok reacties-blok">
          <h2>Reacties</h2>
          <div id="reacties"></div>
          ${APP.gebruiker ? `<form id="reactieform">
            <textarea name="tekst" rows="3" placeholder="Schrijf een reactie als ${esc(APP.gebruiker)}..." required maxlength="500"></textarea>
            <button class="knop" type="submit">Plaatsen</button>
          </form>` : `<p class="grijs"><a href="login.php?terug=${encodeURIComponent('detail.php?id=' + r.id)}">Log in</a> om te reageren.</p>`}
        </section>
      </div>
      <aside class="blok">
        <h2>Tutorial en uitleg</h2>
        <ol class="stappen">${r.stappen.map(([t, u]) => `<li><h3>${t}</h3><p>${u}</p></li>`).join('')}</ol>
      </aside>
    </div>
    ${gerelateerd.length ? `<section class="gerelateerd"><h2>Misschien ook lekker</h2><div class="raster">${gerelateerd.map(kaart).join('')}</div></section>` : ''}`;

  document.getElementById('porties').addEventListener('change', e => {
    document.getElementById('ingr').innerHTML = ingredienten(Number(e.target.value));
  });

  let reacties = PAGE.reacties;
  const toonReacties = () => {
    document.getElementById('reacties').innerHTML = reacties.length
      ? reacties.map(c => `<div class="reactie"><strong>${esc(c.naam)}</strong> <small>${esc(c.datum)}</small><p>${esc(c.tekst)}</p></div>`).join('')
      : '<p class="grijs">Nog geen reacties. Wees de eerste!</p>';
  };
  const rf = document.getElementById('reactieform');
  if (rf) rf.addEventListener('submit', async e => {
    e.preventDefault();
    const tekst = rf.tekst.value.trim();
    if (!tekst) return;
    const res = await fetch('reactie.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': APP.csrf },
      body: JSON.stringify({ id: r.id, tekst })
    });
    const d = await res.json().catch(() => ({}));
    if (res.ok) { reacties = d.reacties; rf.reset(); toonReacties(); }
    else alert(d.fout || 'Plaatsen mislukt.');
  });
  toonReacties();
}
