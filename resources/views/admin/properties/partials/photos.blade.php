<div class="glass mt-6 rounded-2xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold">Foto</h2>
            <p class="text-xs text-white/50">Trascina per riordinare. La prima (★) è la copertina.</p>
        </div>
        <form method="post" action="{{ route('admin.photos.store', $property) }}" enctype="multipart/form-data" class="flex items-center gap-2">
            @csrf
            <input type="file" name="photos[]" multiple accept="image/*" required
                   class="text-sm text-white/70 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-white">
            <button class="brand-gradient rounded-xl px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:opacity-90">Carica</button>
        </form>
    </div>

    <div id="photo-grid" data-reorder-url="{{ route('admin.photos.reorder', $property) }}"
         class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @forelse ($property->photos as $photo)
            <div class="group relative overflow-hidden rounded-xl ring-1 ring-white/10 cursor-move" draggable="true" data-id="{{ $photo->id }}">
                <img src="{{ $photo->url }}" class="aspect-[4/3] w-full object-cover" alt="">
                @if ($photo->is_cover)
                    <span class="absolute left-2 top-2 rounded-full bg-cyan/90 px-2 py-0.5 text-xs font-semibold text-navy-950">★ Copertina</span>
                @endif
                <div class="absolute inset-x-0 bottom-0 flex gap-1 bg-navy-950/70 p-2 opacity-0 transition group-hover:opacity-100">
                    @unless ($photo->is_cover)
                        <form method="post" action="{{ route('admin.photos.cover', $photo) }}" class="flex-1">
                            @csrf
                            <button class="w-full rounded-lg bg-white/15 py-1.5 text-xs hover:bg-white/25">Imposta copertina</button>
                        </form>
                    @endunless
                    <form method="post" action="{{ route('admin.photos.destroy', $photo) }}" onsubmit="return confirm('Eliminare questa foto?')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg bg-red-500/20 px-3 py-1.5 text-xs text-red-200 hover:bg-red-500/30">🗑</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="col-span-full text-sm text-white/50">Nessuna foto caricata.</p>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const grid = document.getElementById('photo-grid');
        if (!grid) return;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        let dragEl = null;

        grid.querySelectorAll('[draggable="true"]').forEach(card => {
            card.addEventListener('dragstart', () => { dragEl = card; card.classList.add('opacity-50'); });
            card.addEventListener('dragend', () => { card.classList.remove('opacity-50'); persist(); });
        });

        grid.addEventListener('dragover', (e) => {
            e.preventDefault();
            const after = getAfter(grid, e.clientX, e.clientY);
            if (!dragEl) return;
            if (after == null) grid.appendChild(dragEl);
            else grid.insertBefore(dragEl, after);
        });

        function getAfter(container, x, y) {
            const els = [...container.querySelectorAll('[draggable="true"]:not(.opacity-50)')];
            return els.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) return { offset, element: child };
                return closest;
            }, { offset: Number.NEGATIVE_INFINITY }).element || null;
        }

        async function persist() {
            const order = [...grid.querySelectorAll('[draggable="true"]')].map(el => el.dataset.id);
            await fetch(grid.dataset.reorderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ order }),
            });
        }
    })();
</script>
@endpush
