@once
<style>
    .dashboard-live-clock{position:fixed;right:22px;bottom:20px;z-index:850;display:flex;align-items:center;gap:12px;min-width:245px;padding:12px 16px;border:1px solid rgba(255,255,255,.58);border-radius:18px;background:linear-gradient(135deg,rgba(20,45,100,.96),rgba(48,93,171,.94));box-shadow:0 16px 40px rgba(20,45,100,.25);color:#fff;backdrop-filter:blur(14px);font-family:Inter,system-ui,-apple-system,sans-serif;cursor:grab;touch-action:none;user-select:none;transition:box-shadow .18s ease,transform .18s ease}
    .dashboard-live-clock:hover{box-shadow:0 20px 48px rgba(20,45,100,.34)}.dashboard-live-clock.is-dragging{cursor:grabbing;transform:scale(1.015);box-shadow:0 24px 54px rgba(20,45,100,.4)}
    .dashboard-live-clock__icon{display:grid;place-items:center;width:42px;height:42px;flex:0 0 42px;border-radius:13px;background:rgba(255,255,255,.14);box-shadow:inset 0 0 0 1px rgba(255,255,255,.13);font-size:18px}
    .dashboard-live-clock__body{min-width:0;line-height:1.15}.dashboard-live-clock__time{display:flex;align-items:baseline;gap:3px;font-size:25px;font-weight:800;letter-spacing:.5px;font-variant-numeric:tabular-nums}.dashboard-live-clock__seconds{font-size:14px;font-weight:700;color:#bfdbfe}.dashboard-live-clock__date{margin-top:5px;font-size:12px;font-weight:600;color:#e5efff;white-space:nowrap}.dashboard-live-clock__zone{margin-left:auto;padding:4px 7px;border-radius:999px;background:rgba(255,255,255,.13);font-size:9px;font-weight:800;letter-spacing:.08em}
    @media(max-width:700px){.dashboard-live-clock{right:10px;bottom:10px;min-width:218px;padding:10px 12px;border-radius:15px}.dashboard-live-clock__icon{width:36px;height:36px;flex-basis:36px}.dashboard-live-clock__time{font-size:21px}.dashboard-live-clock__date{font-size:10px}}
    @media print{.dashboard-live-clock{display:none!important}}
</style>
<aside class="dashboard-live-clock" aria-label="Waktu sekarang. Widget dapat digeser." title="Geser untuk memindahkan posisi · klik dua kali untuk reset" data-dashboard-clock>
    <div class="dashboard-live-clock__icon" aria-hidden="true"><i class="fa-regular fa-clock"></i></div>
    <div class="dashboard-live-clock__body">
        <div class="dashboard-live-clock__time"><span data-clock-hour>{{ now('Asia/Jakarta')->format('H:i') }}</span><span class="dashboard-live-clock__seconds" data-clock-seconds>:{{ now('Asia/Jakarta')->format('s') }}</span></div>
        <div class="dashboard-live-clock__date" data-clock-date>{{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}</div>
    </div>
    <span class="dashboard-live-clock__zone">WIB</span>
</aside>
<script>
(() => {
    const root = document.querySelector('[data-dashboard-clock]');
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';
    const parts = new Intl.DateTimeFormat('id-ID',{timeZone:'Asia/Jakarta',weekday:'long',day:'2-digit',month:'long',year:'numeric'});
    const clock = new Intl.DateTimeFormat('id-ID',{timeZone:'Asia/Jakarta',hour:'2-digit',minute:'2-digit',second:'2-digit',hourCycle:'h23'});
    const update = () => {
        const now = new Date(), values = clock.formatToParts(now).reduce((a,p)=>(a[p.type]=p.value,a),{});
        root.querySelector('[data-clock-hour]').textContent = `${values.hour}:${values.minute}`;
        root.querySelector('[data-clock-seconds]').textContent = `:${values.second}`;
        const date = parts.format(now); root.querySelector('[data-clock-date]').textContent = date.charAt(0).toUpperCase()+date.slice(1);
        root.setAttribute('aria-label', `${values.hour}:${values.minute}:${values.second}, ${date}`);
    };
    const storageKey = 'dashboard-clock-position-v1';
    const clamp = (value,min,max) => Math.min(Math.max(value,min),Math.max(min,max));
    const applyPosition = (left,top) => {
        const margin=8, maxLeft=window.innerWidth-root.offsetWidth-margin, maxTop=window.innerHeight-root.offsetHeight-margin;
        root.style.left=`${clamp(left,margin,maxLeft)}px`; root.style.top=`${clamp(top,margin,maxTop)}px`;
        root.style.right='auto'; root.style.bottom='auto';
    };
    try { const saved=JSON.parse(localStorage.getItem(storageKey)); if(Number.isFinite(saved?.left)&&Number.isFinite(saved?.top)) requestAnimationFrame(()=>applyPosition(saved.left,saved.top)); } catch (_) {}
    let drag=null;
    root.addEventListener('pointerdown',event=>{
        if(event.button!==0) return;
        const rect=root.getBoundingClientRect(); drag={id:event.pointerId,dx:event.clientX-rect.left,dy:event.clientY-rect.top};
        root.setPointerCapture(event.pointerId); root.classList.add('is-dragging'); event.preventDefault();
    });
    root.addEventListener('pointermove',event=>{ if(!drag||drag.id!==event.pointerId)return; applyPosition(event.clientX-drag.dx,event.clientY-drag.dy); });
    const stopDrag=event=>{
        if(!drag||drag.id!==event.pointerId)return; drag=null; root.classList.remove('is-dragging');
        const rect=root.getBoundingClientRect(); localStorage.setItem(storageKey,JSON.stringify({left:rect.left,top:rect.top}));
    };
    root.addEventListener('pointerup',stopDrag); root.addEventListener('pointercancel',stopDrag);
    root.addEventListener('dblclick',()=>{ localStorage.removeItem(storageKey); root.removeAttribute('style'); });
    window.addEventListener('resize',()=>{ const rect=root.getBoundingClientRect(); applyPosition(rect.left,rect.top); });
    update(); window.setInterval(update,1000);
})();
</script>
@endonce
