const afbeelding = [
    "images/burger.jpeg",
    "images/brownie'.jpeg",
    "images/pasta.jpg",
    "images/wrap.jpeg"
];

let huidigeAfbeelding = 0;

const hero = document.querySelector(".hero");

setInterval(() => {
    huidigeAfbeelding =
    (huidigeAfbeelding + 1) % afbeelding.length;

    hero.style.backgroundImage =
     `linear-gradient(rgba(65, 64, 64, 0.4), rgba(55, 55, 55, 0.4)),
     url(${afbeelding[huidigeAfbeelding]})`;
}, 4000);