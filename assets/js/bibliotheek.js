function toon() {
  const el = document.getElementById('raster');
  if (!APP.gebruiker) {
    el.innerHTML = '<div class="leeg"><p>Log in om je favoriete gerechten te bewaren en terug te vinden.</p><a class="knop" href="login.php?terug=bibliotheek.php">Inloggen</a></div>';
    return;
  }
  const lijst = RECEPTEN.filter(r => favs.has(r.id));
  el.innerHTML = lijst.length ? lijst.map(kaart).join('') :
    '<div class="leeg"><p>Je hebt nog geen favorieten. Klik op het hartje bij een recept om het hier te bewaren.</p><a class="knop" href="recepten.php">Bekijk recepten</a></div>';
}
document.addEventListener('favwijzig', toon);
toon();
