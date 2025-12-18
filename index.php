<?php
// Iniciar sesión al principio del archivo
session_start();

// Verificar si el usuario ya está logueado
$logged_in = false;
$telegram_id = '';
$user_name = '';

if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
    $logged_in = true;
    $telegram_id = $_SESSION['telegram_id'] ?? '';
    $user_name = $_SESSION['user_name'] ?? $telegram_id;
}

// Si está logueado, redirigir automáticamente al contenido
if ($logged_in && basename($_SERVER['PHP_SELF']) == 'index.php') {
    // Ya está en el contenido, no hacer nada
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>888Wallet</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">

    <style>
        /* (Todos los estilos se mantienen igual) */
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background: #020617;
        }

        /* ⭐ Fondo de estrellas siempre detrás de todo */
        #starfield {
            position: fixed;
            inset: 0;
            z-index: -2;
            display: block;
            background: radial-gradient(circle at center, #020617, #000814);
            transition: transform 0.2s ease-out, background 0.6s ease;
        }

        /* Contenedor de partículas */
        #particlesContainer {
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
        }

        .click-particle {
            position: absolute;
            width: 10px;
            height: 10px;
            border-radius: 9999px;
            background: rgba(16, 185, 129, 0.9);
            transform: translate(-50%, -50%);
            pointer-events: none;
            animation: particleAnim 0.6s ease-out forwards;
        }

        @keyframes particleAnim {
            0% { transform: translate(-50%, -50%) scale(0.2); opacity: 1; }
            100% { transform: translate(-50%, -50%) scale(2); opacity: 0; }
        }

        /* Tarjeta del login */
        .login-card {
            backdrop-filter: blur(14px);
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 211, 165, 0.4);
            box-shadow: 0 0 35px rgba(34, 197, 94, 0.45);
            opacity: 0;
            transform: translateY(30px);
            animation: fadeSlide 0.8s forwards;
        }

        @keyframes fadeSlide {
            to { opacity: 1; transform: translateY(0); }
        }

        .btn-glow {
            transition: 0.3s;
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.5);
        }
        .btn-glow:hover {
            box-shadow: 0 0 28px rgba(34, 197, 94, 0.9);
        }

        .shake { animation: shakeAnim 0.3s; }
        @keyframes shakeAnim {
            0% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            50% { transform: translateX(6px); }
            75% { transform: translateX(-6px); }
            100% { transform: translateX(0); }
        }

        /* Tarjeta 3D */
        .card-container { perspective: 1000px; }
        .card-inner {
            transition: transform 0.8s;
            transform-style: preserve-3d;
        }
        .card-flipped .card-inner { transform: rotateY(180deg); }
        .card-front, .card-back { backface-visibility: hidden; }
        .card-back { transform: rotateY(180deg); }

        /* Sombra dinámica al hacer hover */
        .card-container:hover #card {
            box-shadow: 0 0 45px rgba(34, 197, 94, 0.8);
        }

        /* Animación entrada app */
        .app-enter {
            animation: appEnter 0.7s ease-out;
        }
        @keyframes appEnter {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Splash Screen */
        #splashScreen {
            position: fixed;
            inset: 0;
            z-index: 50;
            background: radial-gradient(circle at center, #020617, #000814);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.6s ease;
        }

        #splashLogo {
            font-size: 3rem;
            font-weight: 900;
            letter-spacing: -0.05em;
        }

        .splash-sub {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #a7f3d0;
        }

        .splash-pulse {
            animation: splashPulse 1.4s infinite ease-in-out;
        }

        @keyframes splashPulse {
            0% { transform: scale(1); text-shadow: 0 0 10px rgba(34, 197, 94, 0.7); }
            50% { transform: scale(1.05); text-shadow: 0 0 25px rgba(34, 197, 94, 1); }
            100% { transform: scale(1); text-shadow: 0 0 10px rgba(34, 197, 94, 0.7); }
        }

        .splash-hide {
            opacity: 0;
            pointer-events: none;
        }
        
        /* Clase para ocultar elementos */
        .hidden {
            display: none !important;
        }
    </style>
</head>

<body class="text-white">

<!-- ⭐ FONDO ESTRELLADO -->
<canvas id="starfield"></canvas>

<!-- Contenedor de partículas de clic -->
<div id="particlesContainer"></div>

<!-- Splash Screen -->
<div id="splashScreen">
    <div class="splash-pulse text-center flex flex-col items-center">
        <img src="logo.png.jpg"
             alt="888 Carding"
             class="w-40 h-40 mb-4 drop-shadow-[0_0_25px_rgba(34,197,94,0.9)]">
        <p class="splash-sub">Cargando espacio privado…</p>
    </div>
</div>

<?php if (!$logged_in): ?>
<!-- ============================================================
                        LOGIN PRIVADO
============================================================ -->
<div id="loginScreen" class="min-h-screen flex items-center justify-center px-4">
    <div class="login-card w-full max-w-sm rounded-2xl p-6">
       <div class="flex justify-center mb-4">
            <img src="logo.png.jpg"
                 alt="888 Carding"
                 class="w-24 h-24 drop-shadow-[0_0_20px_rgba(34,197,94,0.8)]">
        </div>

        <p class="text-center text-emerald-200 text-sm mt-1">Acceso exclusivo</p>

        <form id="loginForm" class="mt-6 space-y-4">
            <div>
                <label class="text-sm text-emerald-200">ID de Telegram</label>
                <input id="loginTelegramId" type="text"
                       class="mt-1 w-full bg-[#02141c] border border-emerald-500 rounded-md px-3 py-2 text-white"
                       placeholder=""
                       autocomplete="off"
                       required>
            </div>

            <div>
                <label class="text-sm text-emerald-200">Contraseña</label>
                <input id="loginPassword" type="password"
                       class="mt-1 w-full bg-[#02141c] border border-emerald-500 rounded-md px-3 py-2 text-white"
                       placeholder=""
                       autocomplete="off"
                       required>
            </div>

            <p id="loginError" class="text-sm text-red-400 hidden text-center">
                Acceso denegado
            </p>

            <button type="submit"
                    class="w-full bg-emerald-400 text-gray-900 font-bold py-2 rounded-xl btn-glow">
                Acceder
            </button>
        </form>

        <p class="text-center text-emerald-300 text-xs mt-4">
            Acceso privado @Macrzz6 🌌
        </p>
    </div>
</div>
<?php else: ?>
<!-- ============================================================
                CONTENIDO REAL DESPUÉS DEL LOGIN
============================================================ -->
<div id="appContent" class="min-h-screen">
    <div class="relative min-h-screen flex flex-col items-center justify-center p-4">
        <!-- Notificación -->
        <div id="notification" class="hidden fixed top-5 z-40 w-full max-w-sm">
            <div class="flex items-center bg-emerald-900/70 border border-emerald-500 rounded-xl p-4 shadow-lg">
                <span class="text-emerald-200 font-bold mr-3">✓</span>
                <span class="text-white">Tarjeta agregada correctamente</span>
            </div>
        </div>

        <main class="relative z-10 text-center flex flex-col items-center w-full">
            <div class="flex flex-col items-center mb-6">
                <img src="logo.png.jpg"
                     alt="888 Carding"
                     class="w-28 h-28 drop-shadow-[0_0_30px_rgba(34,197,94,0.9)]">
            </div>

            <!-- Texto Bienvenidos -->
            <p id="welcomeText" class="mt-2 max-w-md text-base md:text-lg text-emerald-100">
                Bienvenido, <?php echo htmlspecialchars($user_name); ?>! 🌟 Tu espacio privado está listo.
            </p>

            <!-- Tarjeta visual -->
            <div class="mt-8 w-full max-w-sm card-container">
                <div id="card" class="relative aspect-[1.586/1] w-full rounded-xl">
                    <div id="cardInner" class="card-inner">
                        <!-- Frente -->
                        <div class="card-front bg-[#022c22] rounded-xl shadow-2xl border border-emerald-500 p-6 flex flex-col justify-between">
                            <span class="font-bold text-xl text-white">TARJETA</span>
                            <div class="text-left mt-6">
                                <p id="cardDisplayNumber" class="font-mono text-xl tracking-wider text-white">
                                    **** **** **** ****
                                </p>
                                <div class="flex justify-between mt-4">
                                    <div>
                                        <p class="text-emerald-300 text-xs">Titular</p>
                                        <p id="cardDisplayName" class="font-mono text-sm text-white">NOMBRE APELLIDO</p>
                                    </div>
                                    <div>
                                        <p class="text-emerald-300 text-xs">Vence</p>
                                        <p id="cardDisplayExpiry" class="font-mono text-sm text-white">MM/YY</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reverso -->
                        <div class="card-back bg-[#012026] rounded-xl shadow-2xl p-4">
                            <div class="w-full h-12 bg-black mt-4"></div>
                            <div class="text-right w-full mt-4 pr-2">
                                <p class="text-emerald-300 text-xs">CVV</p>
                                <div class="bg-white h-8 w-full mt-1 flex items-center justify-end pr-4">
                                    <p id="cardDisplayCvv" class="font-mono text-black"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button id="addCardButton"
                    class="mt-8 px-8 py-4 bg-emerald-400 text-gray-900 font-bold rounded-xl text-lg btn-glow">
                Agregar Tarjeta
            </button>

            <!-- Botón Actualizar CC -->
            <a href="https://45keys.com/checkouts/cn/hWN6A5Lex9rdBDeUh8ioJoFG/en-de?_r=AQABLKG9UmicTVMADhm4Zg6i71zwAnlv9tpd3yymxOh9kVU&auto_redirect=false&edge_redirect=true&skip_shop_pay=true"
               target="_blank"
               class="mt-4 inline-block px-8 py-3 bg-blue-400 text-gray-900 font-bold rounded-xl text-lg btn-glow">
                Actualizar CC
            </a>

            <!-- Botón Cerrar Sesión -->
            <form method="POST" action="logout.php" class="mt-4">
                <button type="submit"
                        class="px-6 py-2 bg-red-500 text-white font-bold rounded-xl text-sm hover:bg-red-600 transition">
                    Cerrar Sesión
            </button>
            </form>

            <!-- Formulario (solo visual) -->
            <form id="cardForm" class="hidden w-full max-w-md mt-8">
                <div class="bg-[#02141c]/70 backdrop-blur-sm p-6 rounded-xl border border-emerald-500">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm text-emerald-200">Nombre del Titular</label>
                            <input id="cardName" type="text"
                                   class="w-full bg-[#020c11] border border-emerald-500 rounded-md py-2 px-3 text-white">
                        </div>

                        <div>
                            <label class="text-sm text-emerald-200">Número de Tarjeta</label>
                            <input id="cardNumber" type="text" maxlength="19"
                                   class="w-full bg-[#020c11] border border-emerald-500 rounded-md py-2 px-3 text-white">
                        </div>

                        <div class="flex space-x-4">
                            <div class="flex-1">
                                <label class="text-sm text-emerald-200">Vencimiento</label>
                                <input id="cardExpiry" type="text" maxlength="5"
                                       class="w-full bg-[#020c11] border border-emerald-500 rounded-md py-2 px-3 text-white">
                            </div>
                            <div class="flex-1">
                                <label class="text-sm text-emerald-200">CVV</label>
                                <input id="cardCvv" type="password" maxlength="4"
                                       class="w-full bg-[#020c11] border border-emerald-500 rounded-md py-2 px-3 text-white">
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="mt-6 w-full py-3 bg-emerald-400 text-gray-900 font-bold rounded-xl">
                        Guardar Tarjeta
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
                        SCRIPTS
============================================================ -->
<script>
<?php if ($logged_in): ?>
// Si ya está logueado, ocultar splash inmediatamente
document.addEventListener('DOMContentLoaded', function() {
    const splash = document.getElementById("splashScreen");
    if (splash) {
        splash.classList.add("splash-hide");
        setTimeout(() => {
            splash.style.display = "none";
        }, 100);
    }
    
    // Aplicar animación de entrada
    const appContent = document.getElementById("appContent");
    if (appContent) {
        appContent.classList.add("app-enter");
    }
});
<?php endif; ?>

const loginScreen = document.getElementById("loginScreen");
const appContent = document.getElementById("appContent");
const loginForm = document.getElementById("loginForm");
const loginTelegramId = document.getElementById("loginTelegramId");
const loginPassword = document.getElementById("loginPassword");
const loginError = document.getElementById("loginError");

/* ⭐ STARFIELD / ESTRELLAS ANIMADAS */
const canvas = document.getElementById("starfield");
const ctx = canvas.getContext("2d");

let stars = [];
const NUM_STARS = 150;

function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
resizeCanvas();
window.addEventListener("resize", () => {
    resizeCanvas();
    initStars();
});

function initStars() {
    stars = [];
    for (let i = 0; i < NUM_STARS; i++) {
        stars.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            size: Math.random() * 1.6 + 0.4,
            speed: Math.random() * 0.4 + 0.2,
            alpha: Math.random() * 0.6 + 0.2
        });
    }
}
initStars();

function drawStars() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    for (let s of stars) {
        ctx.globalAlpha = s.alpha;
        ctx.fillStyle = "#e5f9ff";
        ctx.beginPath();
        ctx.arc(s.x, s.y, s.size, 0, Math.PI * 2);
        ctx.fill();

        s.y += s.speed;
        if (s.y > canvas.height) {
            s.y = 0;
            s.x = Math.random() * canvas.width;
        }
    }

    requestAnimationFrame(drawStars);
}
drawStars();

/* Fondo según hora del día */
function updateBackgroundByTime() {
    const hour = new Date().getHours();
    let bg;
    if (hour >= 6 && hour < 11) {
        bg = "radial-gradient(circle at top, #1e293b, #020617)";
    } else if (hour >= 11 && hour < 17) {
        bg = "radial-gradient(circle at center, #0f172a, #020617)";
    } else if (hour >= 17 && hour < 20) {
        bg = "radial-gradient(circle at bottom, #1f2937, #020617)";
    } else {
        bg = "radial-gradient(circle at center, #020617, #000814)";
    }
    canvas.style.background = bg;
}
updateBackgroundByTime();

/* Parallax del fondo con el mouse */
window.addEventListener("mousemove", (e) => {
    const x = (e.clientX / window.innerWidth - 0.5) * 20;
    const y = (e.clientY / window.innerHeight - 0.5) * 20;
    canvas.style.transform = `translate(${x}px, ${y}px)`;
});

/* Efecto de partículas al hacer clic */
const particlesContainer = document.getElementById("particlesContainer");
document.addEventListener("click", (e) => {
    if (!particlesContainer) return;
    const particle = document.createElement("div");
    particle.className = "click-particle";
    particle.style.left = e.clientX + "px";
    particle.style.top = e.clientY + "px";
    particlesContainer.appendChild(particle);
    setTimeout(() => particle.remove(), 600);
});

<?php if (!$logged_in): ?>
/* LOGIN PRIVADO */
if (loginForm) {
    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const telegramId = loginTelegramId.value.trim();
        const password = loginPassword.value.trim();

        if (!telegramId || !password) {
            loginError.textContent = "Completa todos los campos";
            loginError.classList.remove("hidden");
            return;
        }

        try {
            const res = await fetch("login.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    telegram_id: telegramId,
                    password: password
                })
            });

            const data = await res.json();

            if (data.success) {
                loginError.classList.add("hidden");
                
                // Recargar la página completa para que PHP detecte la sesión
                window.location.reload();
                
            } else {
                loginError.textContent = data.message || "Acceso denegado";
                loginError.classList.remove("hidden");
                if (document.querySelector(".login-card")) {
                    document.querySelector(".login-card").classList.add("shake");
                    setTimeout(() => {
                        document.querySelector(".login-card").classList.remove("shake");
                    }, 300);
                }
            }

        } catch (error) {
            console.error("Error en login:", error);
            loginError.textContent = "Error de conexión";
            loginError.classList.remove("hidden");
        }
    });
}
<?php endif; ?>

/* SISTEMA DE TARJETAS (solo visual, sin guardar) */
const addCardButton = document.getElementById("addCardButton");
const cardForm = document.getElementById("cardForm");
const notification = document.getElementById("notification");

const cardNameInput = document.getElementById("cardName");
const cardNumberInput = document.getElementById("cardNumber");
const cardExpiryInput = document.getElementById("cardExpiry");
const cardCvvInput = document.getElementById("cardCvv");

const cardDisplayName = document.getElementById("cardDisplayName");
const cardDisplayNumber = document.getElementById("cardDisplayNumber");
const cardDisplayExpiry = document.getElementById("cardDisplayExpiry");
const cardDisplayCvv = document.getElementById("cardDisplayCvv");

const card = document.getElementById("card");

if (addCardButton && cardForm) {
    addCardButton.addEventListener("click", () => cardForm.classList.toggle("hidden"));

    cardForm.addEventListener("submit", e => {
        e.preventDefault();

        // Notificación
        if (notification) {
            notification.classList.remove("hidden");
            setTimeout(() => notification.classList.add("hidden"), 3000);
        }

        // Ocultar formulario
        cardForm.classList.add("hidden");
    });
}

/* Inputs en vivo */
if (cardNameInput) {
    cardNameInput.addEventListener("input", () =>
        cardDisplayName.textContent = cardNameInput.value || "NOMBRE APELLIDO"
    );
}

if (cardNumberInput) {
    cardNumberInput.addEventListener("input", e => {
        let v = e.target.value.replace(/\s/g, "");
        v = v.replace(/(\d{4})/g, "$1 ").trim();
        e.target.value = v;
        cardDisplayNumber.textContent = v || "**** **** **** ****";
    });
}

if (cardExpiryInput) {
    cardExpiryInput.addEventListener("input", e => {
        let v = e.target.value.replace(/\D/g, "");
        if (v.length > 2) v = v.slice(0,2) + "/" + v.slice(2,4);
        e.target.value = v;
        cardDisplayExpiry.textContent = v || "MM/YY";
    });
}

if (cardCvvInput) {
    cardCvvInput.addEventListener("input", () =>
        cardDisplayCvv.textContent = cardCvvInput.value
    );

    cardCvvInput.addEventListener("focus", () =>
        card.classList.add("card-flipped")
    );
    cardCvvInput.addEventListener("blur", () =>
        card.classList.remove("card-flipped")
    );
}

/* Efecto 3D con el mouse en la tarjeta */
const cardContainer = document.querySelector(".card-container");
const cardElement = document.getElementById("card");

if (cardContainer && cardElement) {
    cardContainer.addEventListener("mousemove", (e) => {
        const rect = cardContainer.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;

        const rotateX = (-y / rect.height) * 12;
        const rotateY = (x / rect.width) * 12;

        cardElement.style.transform = `rotateY(${rotateY}deg) rotateX(${rotateX}deg)`;
    });

    cardContainer.addEventListener("mouseleave", () => {
        cardElement.style.transform = "rotateY(0deg) rotateX(0deg)";
    });
}

/* Splash Screen: ocultar después de un tiempo */
window.addEventListener("load", () => {
    const splash = document.getElementById("splashScreen");
    if (!splash) return;
    setTimeout(() => {
        splash.classList.add("splash-hide");
        setTimeout(() => {
            splash.style.display = "none";
        }, 600);
    }, 1500);
});
</script>

</body>
</html>