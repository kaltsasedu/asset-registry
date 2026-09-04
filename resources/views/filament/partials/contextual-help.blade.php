@php($helpContext = \App\Support\HelpContent::contextForRequest())
@if($helpContext)
<style>
    #app-help-button{position:fixed;right:1.2rem;bottom:1.2rem;z-index:60;display:flex;align-items:center;gap:.45rem;border:0;border-radius:999px;padding:.72rem 1rem;background:#075985;color:white;font:700 .9rem system-ui;box-shadow:0 8px 28px #0f172a45;cursor:pointer}#app-help-button:hover{background:#0e7490}#app-help-backdrop{position:fixed;inset:0;z-index:70;background:#0f172a66}#app-help-panel{position:fixed;right:0;top:0;bottom:0;z-index:71;width:min(430px,94vw);background:white;color:#172033;box-shadow:-10px 0 40px #0f172a33;padding:1.25rem;overflow:auto}#app-help-panel[hidden],#app-help-backdrop[hidden]{display:none}#app-help-panel h2{font:750 1.3rem system-ui;margin:.15rem 2rem .4rem 0}#app-help-panel p{color:#64748b;line-height:1.55}#app-help-panel ol{display:grid;gap:.7rem;padding-left:1.35rem;line-height:1.5}#app-help-close{position:absolute;right:1rem;top:1rem;border:0;background:#e2e8f0;border-radius:999px;width:2.2rem;height:2.2rem;cursor:pointer;font-size:1.2rem}#app-help-attention{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:.75rem;border-radius:.7rem;margin:1rem 0}#app-help-manual{display:block;background:#075985;color:white;text-align:center;text-decoration:none;padding:.75rem;border-radius:.7rem;font-weight:700;margin-top:1.1rem}.dark #app-help-panel{background:#18181b;color:#fafafa}.dark #app-help-panel p{color:#a1a1aa}.dark #app-help-close{background:#3f3f46;color:white}.dark #app-help-attention{background:#431407;border-color:#9a3412;color:#fed7aa}@media(max-width:600px){#app-help-button{right:.8rem;bottom:.8rem}#app-help-button span{display:none}}
</style>
<button id="app-help-button" type="button" aria-controls="app-help-panel" aria-expanded="false"><span aria-hidden="true">?</span><span>Βοήθεια</span></button>
<div id="app-help-backdrop" hidden></div>
<aside id="app-help-panel" role="dialog" aria-modal="true" aria-labelledby="app-help-title" hidden>
    <button id="app-help-close" type="button" aria-label="Κλείσιμο">×</button>
    <h2 id="app-help-title">{{ $helpContext['title'] }}</h2>
    <p>{{ $helpContext['summary'] }}</p>
    <h3 style="font:750 1rem system-ui;margin:1.1rem 0 .5rem">Τι πρέπει να κάνετε</h3>
    <ol>@foreach($helpContext['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
    @if($helpContext['attention'])<div id="app-help-attention"><strong>Προσοχή:</strong> {{ $helpContext['attention'] }}</div>@endif
    <a id="app-help-manual" href="{{ url('/admin/help-center') }}#section-{{ $helpContext['section'] }}" target="_blank" rel="noopener">Άνοιγμα αναλυτικού εγχειριδίου σε νέα καρτέλα</a>
</aside>
<script>
(() => {
    const button=document.getElementById('app-help-button'),panel=document.getElementById('app-help-panel'),backdrop=document.getElementById('app-help-backdrop'),close=document.getElementById('app-help-close');
    if(!button||button.dataset.ready)return;button.dataset.ready='1';
    const toggle=open=>{panel.hidden=!open;backdrop.hidden=!open;button.setAttribute('aria-expanded',open?'true':'false');document.body.style.overflow=open?'hidden':'';if(open)close.focus()};
    button.addEventListener('click',()=>toggle(true));close.addEventListener('click',()=>toggle(false));backdrop.addEventListener('click',()=>toggle(false));document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!panel.hidden)toggle(false)});
})();
</script>
@endif
