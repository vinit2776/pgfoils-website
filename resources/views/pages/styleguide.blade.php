<x-layouts.site title="Style guide" :noindex="true">
    <section class="block"><div class="wrap">
        <h1 class="h2">Design tokens</h1>
        <p>Reference page, hidden in production. Values come from DESIGN.md.</p>
        <h2 style="font-size:1.5rem;margin:32px 0 12px">Type</h2>
        <p class="label">One family: Archivo, widths 100–112, weights 400–800</p>
        <p style="font:800 3rem/1 var(--f-display);font-stretch:108%;color:var(--ink)">India’s third‑largest foil</p>
        <p>The quick brown fox jumps over the lazy dog. 0.02 mm to 0.03 mm, 60 mm to 1010 mm.</p>
        <h2 style="font-size:1.5rem;margin:32px 0 12px">Buttons</h2>
        <p><a class="btn" href="#">Default</a> <a class="btn solid" href="#">Solid</a> <a class="btn sm" href="#">Small</a></p>
        <h2 style="font-size:1.5rem;margin:32px 0 12px">Colour</h2>
        <p class="label">
            @foreach (['ink','ink-2','muted','bg','surface','line','steel','cyan','cyan-ink','mag','yel'] as $c)
                <span style="display:inline-block;width:92px;margin:0 8px 8px 0"><span style="display:block;height:48px;background:var(--{{ $c }});border:1px solid var(--line)"></span>{{ $c }}</span>
            @endforeach
        </p>
        <p><span class="tbs">TO BE SUPPLIED</span> marks content waiting on the owner.</p>
    </div></section>
</x-layouts.site>
