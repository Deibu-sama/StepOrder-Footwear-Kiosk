<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StepOrder — Tap to Start</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#fff3c9] text-black">
    <main class="grid min-h-screen md:grid-cols-2">
        <section class="relative min-h-[55vh] overflow-hidden bg-[#e9dfd1] md:min-h-screen">
            <div class="absolute inset-0 grid grid-cols-2 grid-rows-3 gap-2 p-2">
                <img class="h-full w-full object-cover" src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1000&q=80" alt="Footwear">
                <img class="h-full w-full object-cover" src="https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=1000&q=80" alt="Footwear">
                <img class="h-full w-full object-cover" src="https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=1000&q=80" alt="Footwear">
                <img class="h-full w-full object-cover" src="https://images.unsplash.com/photo-1560769629-975ec94e6a86?auto=format&fit=crop&w=1000&q=80" alt="Footwear">
                <img class="h-full w-full object-cover" src="https://images.unsplash.com/photo-1603487742131-4160ec999306?auto=format&fit=crop&w=1000&q=80" alt="Footwear">
                <div class="flex items-center justify-center bg-black/85 p-6 text-center text-white">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.3em] text-lime-300">SELF-SERVICE</p>
                        <p class="mt-2 text-4xl font-black">FIND YOUR<br>PERFECT STEP.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="flex min-h-[45vh] items-center justify-center p-8 md:min-h-screen">
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

                <p class="mt-8 text-sm font-bold text-black/40">Please select “Tap to Start” to begin your order.</p>
            </div>
        </section>
    </main>
</body>
</html>