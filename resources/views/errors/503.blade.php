<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="refresh" content="60">

        <title>503 - Service Unavailable</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet">

        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { background-color: #FAFAFA; font-family: 'Instrument Sans', sans-serif; color: #171717; -webkit-font-smoothing: antialiased; }
            .navbar { position: sticky; top: 0; z-index: 50; background: #fff; border-bottom: 0.5px solid #E5E5E5; }
            .navbar-inner { max-width: 72rem; margin: 0 auto; padding: 0 1.5rem; display: flex; align-items: center; height: 4rem; }
            .navbar-inner img { height: 2rem; }
            .container { position: relative; display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 12rem); padding: 0 1rem; }
            .blob { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); pointer-events: none; }
            .content { position: relative; z-index: 10; text-align: center; }
            .code { font-size: 44px; font-weight: 500; line-height: 1; color: #A3A3A3; }
            .headline { margin-top: 1rem; font-size: 22px; font-weight: 500; color: #171717; }
            .body { margin-top: 0.5rem; font-size: 16px; color: #737373; }
        </style>
    </head>
    <body>
        <nav class="navbar">
            <div class="navbar-inner">
                <img src="/images/greetup.png" alt="Greetup">
            </div>
        </nav>

        <div class="container">
            <svg class="blob" width="480" height="480" viewBox="0 0 80 80" style="opacity: 0.06;" aria-hidden="true">
                <path d="M40 5 C55 5, 70 15, 72 30 C78 32, 80 38, 78 45 C80 55, 72 68, 58 70 C52 78, 40 80, 32 74 C18 76, 5 66, 5 52 C0 42, 5 32, 15 28 C12 15, 25 5, 40 5Z" fill="#1FAF63" />
            </svg>

            <div class="content">
                <p class="code">503</p>
                <h1 class="headline">We'll be right back</h1>
                <p class="body">Greetup is undergoing maintenance. Please check back shortly.</p>
            </div>
        </div>
    </body>
</html>
