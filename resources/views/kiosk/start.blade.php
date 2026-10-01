<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StepOrder — Tap to Start</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#fff3c9] text-black">
    <main class="grid min-h-screen lg:grid-cols-[1.15fr_0.85fr]">
        <section class="relative min-h-[48vh] overflow-hidden bg-stone-200 lg:min-h-screen">
            <div id="hero-slides" class="absolute inset-0">
                <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1400&q=85" class="slide absolute inset-0 h-full w-full object-cover opacity-100 transition-opacity duration-700" alt="Footwear">
                <img src="https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=1400&q=85" class="slide absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-700" alt="Footwear">
                <img src="https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=1400&q=85" class="slide absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-700" alt="Footwear">
                <img src="https://images.unsplash.com/photo-1560769629-975ec94e6a86?auto=format&fit=crop&w=1400&q=85" class="slide absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-700" alt="Footwear">
                <img src="https://images.unsplash.com/photo-1603487742131-4160ec999306?auto=format&fit=crop&w=1400&q=85" class="slide absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-700" alt="Footwear">
            </div>

            <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/10 to-transparent"></div>

            <div class="absolute bottom-8 left-8 right-8 text-white sm:bottom-12 sm:left-12">
                <p class="text-sm font-black uppercase tracking-[0.3em] text-lime-300">SELF-SERVICE FOOTWEAR KIOSK</p>
                <p class="mt-2 text-4xl font-black sm:text-6xl">FIND YOUR<br>PERFECT STEP.</p>
                <div id="slide-dots" class="mt-6 flex gap-2">
                    <span class="h-2.5 w-8 rounded-full bg-white"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-white/40"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-white/40"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-white/40"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-white/40"></span>
                </div>
            </div>
        </section>

        <section class="flex min-h-[52vh] items-center justify-center p-8 lg:min-h-screen">
            <div class="w-full max-w-xl text-center">
                <p class="text-lg font-bold uppercase tracking-[0.25em] text-black/50">FOOTWEAR ORDERING KIOSK</p>

                <h1 class="mt-4 text-6xl font-black leading-none sm:text-7xl">
                    <span class="text-lime-600">STEP</span>ORDER
                </h1>

                <p class="mx-auto mt-5 max-w-md text-lg font-bold text-black/60">
                    Browse footwear, build your order, and proceed to the cashier when you're ready to pay.
                </p>

                <a href="{{ url('/menu') }}"
                   class="mx-auto mt-10 flex min-h-28 w-full max-w-lg items-center justify-center rounded-[2rem] border-4 border-black bg-lime-300 px-8 text-3xl font-black shadow-[8px_8px_0_#111] transition hover:-translate-y-1 active:translate-y-1 active:shadow-[3px_3px_0_#111]">
                    TAP TO START
                </a>

                <p class="mt-8 text-sm font-bold text-black/40">
                    Tap the button to begin your order.
                </p>
            </div>
        </section>
    </main>

    <script>
        const slides = Array.from(document.querySelectorAll('.slide'));
        const dots = Array.from(document.querySelectorAll('#slide-dots span'));
        let current = 0;

        setInterval(() => {
            slides[current].classList.replace('opacity-100', 'opacity-0');
            dots[current].classList.remove('w-8');
            dots[current].classList.add('w-2.5');
            dots[current].classList.remove('bg-white');
            dots[current].classList.add('bg-white/40');

            current = (current + 1) % slides.length;

            slides[current].classList.replace('opacity-0', 'opacity-100');
            dots[current].classList.remove('w-2.5');
            dots[current].classList.add('w-8');
            dots[current].classList.remove('bg-white/40');
            dots[current].classList.add('bg-white');
        }, 3500);
    </script>
</body>
</html>
