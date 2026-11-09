const formulier = document.getElementById("receptForm");
const overzicht = document.getElementById("recepten");
const melding = document.getElementById("melding");

let recepten = JSON.parse(localStorage.getItem("recepten")) || [];
let bewerkIndex = -1;

function opslaan() {
    localStorage.setItem("recepten", JSON.stringify(recepten));
}

function tonen() {
    overzicht.innerHTML = "";

    recepten.forEach((recept, index) => {
        const kaart = document.createElement("div");
        kaart.className = "recept";
        const titel = document.createElement("h3");
        titel.textContent = recept.naam;
        const info = document.createElement("p");
        info.textContent =
            recept.categorie + " - " + recept.tijd + " minuten";
        const ingredientenTitel = document.createElement("h4");
        ingredientenTitel.textContent = "Ingrediënten";
        const ingredienten = document.createElement("p");
        ingredienten.textContent = recept.ingredienten;
        const bereidingTitel = document.createElement("h4");
        bereidingTitel.textContent = "Bereidingswijze";
        const bereiding = document.createElement("p");
        bereiding.textContent = recept.bereiding;
        const aanpassen = document.createElement("button");
        aanpassen.textContent = "Aanpassen";
        aanpassen.onclick = () => bewerken(index);
        const verwijderen = document.createElement("button");
        verwijderen.textContent = "Verwijderen";
        verwijderen.className = "verwijderen";
        verwijderen.onclick = () => verwijderenRecept(index);

        kaart.append(
            titel, info, ingredientenTitel, ingredienten,
            bereidingTitel, bereiding, aanpassen, verwijderen
        );

        overzicht.appendChild(kaart);
    });
}

formulier.addEventListener("submit", function(event) {
    event.preventDefault();

    const recept = {
        naam: document.getElementById("naam").value.trim(),
        categorie: document.getElementById("categorie").value,
        tijd: document.getElementById("tijd").value,
        ingredienten: document.getElementById("ingredienten").value.trim(),
        bereiding: document.getElementById("bereiding").value.trim()
    };

    if (bewerkIndex === -1) {
        recepten.push(recept);
        melding.textContent = "Recept toegevoegd!";
    } else {
        recepten[bewerKIndex] = recept;
        melding.textContent = "Recept aangepast!";
        bewerkIndex = -1;
        document.getElementById("opslaan").textContent = "Recept toevoegen";
    }

    opslaan();
    tonen();
    formulier.reset();
});

function bewerken(index) {
    const recept = recepten[index];

    document.getElementById("naam").value = recept.naam;
    document.getElementById("categorie").value = recept.categorie;
    document.getElementById("tijd").value = recept.tijd;
    document.getElementById("ingredienten").value = recept.ingredienten;
    document.getElementById("bereiding").value = recept.bereiding;

    bewerkIndex = index;
    document.getElementById("opslaan").textContent = "Wijzigingen opslaan";
    formulier.scrollIntoView({ behavior: "smooth" });
}

function verwijderenRecept(index) {
    if (confirm("Weet je zeker dat je dit recept wilt verwijderen?")) {
        recepten.splice(index, 1);
        opslaan();
        tonen();
        melding.textContent = "Recept verwijderd!";
    }
}

tonen();