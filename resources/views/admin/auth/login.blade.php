<!doctype html>
<html lang="it" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <title>Accesso — HostUp CRM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-white/90 bg-navy-950 min-h-screen grid place-items-center">
    <div class="hu-glow"></div>

    <div class="glass w-full max-w-sm rounded-3xl p-8">
        <div class="flex justify-center"><x-logo :size="24" :badge="48" /></div>
        <p class="mt-4 text-center text-sm text-white/60">Accesso area gestione</p>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-white/60">Email</label>
                <input name="email" type="email" required autofocus value="{{ old('email') }}"
                       class="mt-1 w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white focus:border-cyan focus:ring-1 focus:ring-cyan">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-white/60">Password</label>
                <input name="password" type="password" required
                       class="mt-1 w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white focus:border-cyan focus:ring-1 focus:ring-cyan">
            </div>
            <label class="flex items-center gap-2 text-sm text-white/70">
                <input type="checkbox" name="remember" class="rounded border-white/20 bg-white/5"> Ricordami
            </label>
            <button type="submit"
                    class="brand-gradient w-full rounded-xl py-3 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:opacity-90">
                Entra
            </button>
        </form>
    </div>
</body>
</html>
