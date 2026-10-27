<?php
require_once __DIR__ . '/../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Ontkoking</title>
    <link rel="icon" type="image/webp" href="assets/images/logo.png">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #222;
        }

        header {
            background-color: #fff;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        header h1 {
            margin: 0;
            color: #000;
        }

        nav {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 22px;
        }

        nav a {
            color: #222;
            text-decoration: none;
        }

        nav a:hover {
            color: #cb937a;
        }

        .account {
            color: #666;
            font-size: 0.95rem;
        }

        .hero {
            padding: 64px 20px;
            text-align: center;
            background-color: #e3b9a1;
        }

        .hero h2 {
            margin: 0 0 12px;
            font-size: clamp(2rem, 5vw, 2.8rem);
        }

        .hero p {
            max-width: 600px;
            margin: 0 auto 26px;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .button {
            display: inline-block;
            padding: 12px 20px;
            border: 2px solid #f58353;
            border-radius: 5px; 
            background-color: #f58353;
            color: #fff;
            font-weight: bold;
            text-decoration: none;
        }

        .button:hover {
            background-color: #cb4e1b;
            border-color: #cb4e1b;
        }

        .button-secondary {
            background-color: transparent;
            border-color: #222;
            color: #222;
        }

        .button-secondary:hover {
            background-color: #222;
            border-color: #222;
            color: #fff;
        }

        .featured {
            width: 90%;
            max-width: 1100px;
            margin: 42px auto 0;
        }

        .featured h2 {
            margin: 0 0 8px;
        }

        .featured-intro {
            margin: 0 0 22px;
            color: #666;
        }

        .recipe-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 25px;
        }

        .recipe-card {
            overflow: hidden;
            padding-bottom: 20px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .recipe-card img {
            display: block;
            width: 100%;
            height: 190px;
            object-fit: cover;
        }

        .recipe-card h3 {
            margin: 15px 15px 5px;
        }

        .recipe-card p {
            margin: 0 15px 15px;
            color: #666;
        }

        .recipe-card a {
            margin-left: 15px;
            color: #e65f25;
            font-weight: bold;
            text-decoration: none;
        }

        .recipe-card a:hover {
            color: #cb4e1b;
        }

        footer {
            margin-top: 60px;
            padding: 25px;
            background-color: #222;
            color: #fff;
            text-align: center;
        }

        footer p {
            margin: 0;
        }

        @media (max-width: 800px) {
            header {
                flex-direction: column;
                padding: 20px;
            }

            nav {
                justify-content: center;
                gap: 12px 18px;
            }

            .recipe-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 550px) {
            .hero {
                padding: 48px 20px;
            }

            .recipe-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Ontkoking</h1>
        <nav aria-label="Hoofdnavigatie">
            <a href="index.php" aria-current="page">Home</a>
            <a href="pages/recepten.php">Recepten</a>
            <a href="pages/invoer.php">Recepten toevoegen</a>
            <?php if (isLoggedIn()): ?>
                <span class="account">Welkom, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="pages/logout.php">Uitloggen</a>
            <?php else: ?>
                <a href="pages/login.php">Inloggen</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h2>Welkom bij Ontkoking</h2>
            <p>Ontdek heerlijke recepten, doe inspiratie op en deel jouw favoriete gerechten met anderen.</p>
            <div class="actions">
                <a class="button" href="pages/recepten.php">Ontdek recepten</a>
                <a class="button button-secondary" href="pages/invoer.php">Deel een recept</a>
            </div>
        </section>

        <section class="featured" aria-labelledby="featured-heading">
            <h2 id="featured-heading">Recepten om mee te beginnen</h2>
            <p class="featured-intro">Een paar lekkere ideeën voor je volgende maaltijd.</p>
            <div class="recipe-grid">
                <article class="recipe-card">
                    <img src="assets/images/pasta.jpg" alt="Pasta Carbonara">
                    <h3>Pasta Carbonara</h3>
                    <p>25 minuten · Makkelijk</p>
                    <a href="pages/receptenDetail/pasta.html">Bekijk recept</a>
                </article>
                <article class="recipe-card">
                    <img src="assets/images/poke-bowl.jpeg" alt="Poké bowl">
                    <h3>Poké Bowl</h3>
                    <p>15 minuten · Makkelijk</p>
                    <a href="pages/receptenDetail/pokeBowl.html">Bekijk recept</a>
                </article>
                <article class="recipe-card">
                    <img src="assets/images/pancakes.jpeg" alt="Pancakes">
                    <h3>Pancakes</h3>
                    <p>15 minuten · Makkelijk</p>
                    <a href="pages/receptenDetail/pancakes.html">Bekijk recept</a>
                </article>
            </div>
        </section>
    </main>

    <footer>
        <p>Ontkoking</p>
    </footer>
</body>
</html>
