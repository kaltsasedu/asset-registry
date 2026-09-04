<x-filament-panels::page>
    <style>
        .help-layout{display:grid;grid-template-columns:280px minmax(0,1fr);gap:1.25rem;align-items:start}.help-nav{position:sticky;top:5.5rem;border:1px solid rgb(226 232 240);border-radius:1rem;background:white;padding:1rem;max-height:calc(100vh - 7rem);overflow:auto}.dark .help-nav,.dark .help-section{background:rgb(24 24 27);border-color:rgb(63 63 70)}.help-search{width:100%;border:1px solid rgb(203 213 225);border-radius:.65rem;padding:.7rem .8rem;background:white;color:inherit}.dark .help-search{background:rgb(39 39 42);border-color:rgb(82 82 91)}.help-nav ol{list-style:none;padding:0;margin:1rem 0 0}.help-nav a{display:block;padding:.5rem .55rem;border-radius:.5rem;color:rgb(51 65 85);font-size:.88rem;line-height:1.25;text-decoration:none}.dark .help-nav a{color:rgb(212 212 216)}.help-nav a:hover{background:rgb(241 245 249);color:rgb(3 105 161)}.dark .help-nav a:hover{background:rgb(39 39 42)}.help-tools{display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem}.help-button{border:1px solid rgb(203 213 225);border-radius:.65rem;padding:.65rem .85rem;background:white;font-weight:650;cursor:pointer}.dark .help-button{background:rgb(39 39 42);border-color:rgb(82 82 91)}.help-section{scroll-margin-top:6rem;background:white;border:1px solid rgb(226 232 240);border-radius:1rem;padding:1.35rem;margin-bottom:1rem}.help-section h2{font-size:1.25rem;font-weight:750;margin:0;color:rgb(15 23 42)}.dark .help-section h2{color:white}.help-summary{color:rgb(71 85 105);margin:.45rem 0 1rem}.dark .help-summary{color:rgb(161 161 170)}.help-steps{counter-reset:help-step;display:grid;gap:.65rem;padding:0;margin:0 0 1.2rem;list-style:none}.help-steps li{counter-increment:help-step;position:relative;padding:.72rem .8rem .72rem 3rem;border-radius:.75rem;background:rgb(248 250 252);line-height:1.5}.dark .help-steps li{background:rgb(39 39 42)}.help-steps li:before{content:counter(help-step);position:absolute;left:.8rem;top:.65rem;width:1.55rem;height:1.55rem;display:grid;place-items:center;border-radius:999px;background:rgb(3 105 161);color:white;font-weight:750;font-size:.78rem}.help-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}.help-detail{border-left:3px solid rgb(14 116 144);padding:.25rem .75rem}.help-detail h3{font-size:.95rem;font-weight:750;margin:0 0 .25rem}.help-detail p{margin:0;color:rgb(71 85 105);line-height:1.5;font-size:.9rem}.dark .help-detail p{color:rgb(161 161 170)}.help-tips{margin:1rem 0 0;padding:.8rem 1rem .8rem 2rem;border-radius:.75rem;background:rgb(255 251 235);color:rgb(120 53 15)}.dark .help-tips{background:rgb(69 26 3);color:rgb(254 215 170)}.help-empty{display:none;padding:2rem;text-align:center;color:rgb(100 116 139)}@media(max-width:900px){.help-layout{grid-template-columns:1fr}.help-nav{position:static;max-height:none}.help-nav ol{display:none}.help-details{grid-template-columns:1fr}}@media print{.fi-sidebar,.fi-topbar,.help-nav,.help-tools{display:none!important}.help-layout{display:block}.help-section{break-inside:avoid;border:0;padding:0;margin:0 0 1.2rem}.help-section[hidden]{display:block!important}}
    </style>

    <div class="help-tools">
        <button class="help-button" type="button" onclick="window.print()">Εκτύπωση εγχειριδίου</button>
        <button class="help-button" type="button" id="helpExpandAll">Εμφάνιση όλων</button>
        <span id="helpResultCount" style="align-self:center;color:rgb(100 116 139)"></span>
    </div>

    <div class="help-layout">
        <aside class="help-nav">
            <label for="helpSearch" style="display:block;font-weight:700;margin-bottom:.45rem">Αναζήτηση στο εγχειρίδιο</label>
            <input class="help-search" id="helpSearch" type="search" placeholder="π.χ. απόσβεση, QR, χρήστης">
            <ol>
                @foreach($sections as $key => $section)
                    <li><a href="#section-{{ $key }}">{{ $section['title'] }}</a></li>
                @endforeach
            </ol>
        </aside>

        <div id="helpSections">
            @foreach($sections as $key => $section)
                <section class="help-section" id="section-{{ $key }}" data-help-section>
                    <h2>{{ $section['title'] }}</h2>
                    <p class="help-summary">{{ $section['summary'] }}</p>
                    <ol class="help-steps">
                        @foreach($section['steps'] as $step)<li>{{ $step }}</li>@endforeach
                    </ol>
                    <div class="help-details">
                        @foreach($section['details'] as $detail)
                            <div class="help-detail"><h3>{{ $detail['title'] }}</h3><p>{{ $detail['text'] }}</p></div>
                        @endforeach
                    </div>
                    @if($section['tips'])
                        <ul class="help-tips">@foreach($section['tips'] as $tip)<li>{{ $tip }}</li>@endforeach</ul>
                    @endif
                </section>
            @endforeach
            <div class="help-empty" id="helpEmpty">Δεν βρέθηκε ενότητα με αυτούς τους όρους.</div>
        </div>
    </div>

    <script>
        (() => {
            const input = document.getElementById('helpSearch');
            const sections = [...document.querySelectorAll('[data-help-section]')];
            const empty = document.getElementById('helpEmpty');
            const counter = document.getElementById('helpResultCount');
            const normalize = value => value.toLocaleLowerCase('el-GR').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const filter = () => {
                const terms = normalize(input.value).trim().split(/\s+/).filter(Boolean);
                let visible = 0;
                sections.forEach(section => {
                    const text = normalize(section.textContent);
                    const show = terms.every(term => text.includes(term));
                    section.hidden = !show;
                    if (show) visible++;
                });
                empty.style.display = visible ? 'none' : 'block';
                counter.textContent = terms.length ? `${visible} ενότητες βρέθηκαν` : `${sections.length} ενότητες`;
            };
            input.addEventListener('input', filter);
            document.getElementById('helpExpandAll').addEventListener('click', () => { input.value = ''; filter(); window.scrollTo({top: 0, behavior: 'smooth'}); });
            filter();
            if (location.hash) setTimeout(() => document.querySelector(location.hash)?.scrollIntoView({behavior:'smooth'}), 100);
        })();
    </script>
</x-filament-panels::page>
