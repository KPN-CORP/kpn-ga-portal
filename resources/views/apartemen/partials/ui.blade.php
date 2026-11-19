{{-- Gaya bersama halaman admin Monitoring & Riwayat. Di-include lewat @push('styles'). Semua kelas berawalan "ap-" agar tidak bentrok. --}}
<style>
    .t-gray{--c:#64748b;--soft:#f1f5f9}
    .t-blue{--c:#2563eb;--soft:#eff6ff}
    .t-green{--c:#16a34a;--soft:#f0fdf4}
    .t-yellow{--c:#d97706;--soft:#fffbeb}
    .t-red{--c:#dc2626;--soft:#fef2f2}
    .t-indigo{--c:#4f46e5;--soft:#eef2ff}

    .ap-card{background:#fff;border:1px solid #e6eaf0;border-radius:14px}
    .ap-flash{display:flex;align-items:flex-start;gap:.6rem;border-radius:12px;padding:.75rem 1rem;font-size:.875rem;margin-bottom:1rem;border:1px solid}
    .ap-flash.ok{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
    .ap-flash.err{background:#fef2f2;border-color:#fecaca;color:#991b1b}

    /* KPI */
    .ap-kpi{position:relative;display:block;padding:.85rem 1rem 1.05rem;overflow:hidden;transition:border-color .15s,box-shadow .15s,background-color .15s}
    a.ap-kpi:hover{border-color:#cbd5e1;box-shadow:0 6px 18px rgba(15,23,42,.06)}
    .ap-kpi.is-active{border-color:var(--c);box-shadow:0 0 0 1px var(--c);background:var(--soft)}
    .ap-kpi-label{display:flex;align-items:center;gap:.5rem;font-size:.8rem;font-weight:500;color:#475569}
    .ap-kpi-label i{width:1.65rem;height:1.65rem;border-radius:.5rem;display:grid;place-items:center;background:var(--soft);color:var(--c);font-size:.72rem}
    .ap-kpi.is-active .ap-kpi-label i{background:#fff}
    .ap-kpi-val{margin-top:.55rem;font-size:1.7rem;line-height:1;font-weight:700;color:#0f172a;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
    .ap-kpi-pct{margin-left:.45rem;font-size:.75rem;font-weight:500;color:#94a3b8;letter-spacing:0}
    .ap-kpi-hint{margin-top:.3rem;font-size:.72rem;color:#94a3b8}
    .ap-kpis{display:flex;gap:.6rem;overflow-x:auto;scroll-snap-type:x proximity;margin:0 -1rem 1rem;padding:.1rem 1rem .4rem;scrollbar-width:none}
    .ap-kpis::-webkit-scrollbar{display:none}
    .ap-kpis>*{flex:0 0 8.75rem;scroll-snap-align:start}
    .ap-kpis .ap-kpi-val{font-size:1.45rem}
    @media (min-width:768px){
        .ap-kpis{display:grid;grid-template-columns:repeat(var(--n,4),minmax(0,1fr));gap:.75rem;overflow:visible;margin:0 0 1rem;padding:0}
        .ap-kpis>*{flex:none}
        .ap-kpis .ap-kpi-val{font-size:1.7rem}
        .ap-kpis.c5{--n:3}
    }
    @media (min-width:1280px){ .ap-kpis.c5{--n:5} }
    .ap-kpi-bar{position:absolute;left:0;right:0;bottom:0;height:3px;background:#eef1f5}
    .ap-kpi-bar span{display:block;height:100%;width:var(--w);background:var(--c);border-radius:0 3px 3px 0}

    /* Filter */
    .ap-input{width:100%;height:2.5rem;padding:0 .85rem;border:1px solid #dfe4ec;border-radius:.65rem;background:#fff;font-size:.875rem;color:#0f172a;transition:border-color .15s,box-shadow .15s}
    .ap-input::placeholder{color:#94a3b8}
    .ap-input:hover{border-color:#cbd5e1}
    .ap-input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
    select.ap-input{appearance:none;padding-right:2.1rem;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .8rem center}
    textarea.ap-input{height:auto;padding:.6rem .85rem;line-height:1.45;resize:vertical}
    .ap-search{position:relative}
    .ap-search>i{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem;pointer-events:none}
    .ap-search .ap-input{padding-left:2.3rem;padding-right:4.3rem}
    .ap-search-go{position:absolute;right:.3rem;top:.3rem;bottom:.3rem;padding:0 .8rem;border-radius:.5rem;background:#0f172a;color:#fff;font-size:.78rem;font-weight:600;cursor:pointer}
    .ap-search-go:hover{background:#1e293b}
    .ap-fbtn{display:none;flex:none;align-items:center;gap:.45rem;height:2.5rem;padding:0 .85rem;border:1px solid #dfe4ec;border-radius:.65rem;background:#fff;font-size:.85rem;font-weight:600;color:#334155;cursor:pointer}
    .ap-fbtn .n{min-width:1.15rem;height:1.15rem;padding:0 .25rem;border-radius:999px;background:#2563eb;color:#fff;font-size:.65rem;display:grid;place-items:center}
    .ap-filters.open .ap-fbtn{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
    @media (max-width:1023px){
        .ap-fbtn{display:inline-flex}
        .ap-fld{display:none}
        .ap-filters.open .ap-fld{display:block}
        .ap-filters .ap-input{height:2.7rem;font-size:1rem}
    }
    .ap-filter-row{display:flex;flex-wrap:wrap;align-items:center;gap:.4rem;margin-top:.8rem;padding-top:.8rem;border-top:1px dashed #e6eaf0}
    .chip{display:inline-flex;align-items:center;gap:.4rem;padding:.2rem .6rem;border-radius:999px;background:#fff;border:1px solid #dbe3ef;color:#1e3a8a;font-size:.75rem;font-weight:500;transition:background-color .15s,border-color .15s}
    .chip:hover{background:#eff6ff;border-color:#bfdbfe}
    .chip i{font-size:.6rem;color:#64748b}
    .ap-link{font-size:.75rem;font-weight:600;color:#2563eb}
    .ap-link:hover{text-decoration:underline}

    /* Tabel / kartu */
    .ap-listhead{display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:.25rem 1rem;padding:1rem 1.25rem;border-bottom:1px solid #e6eaf0}
    .ap-listhead h3{font-size:1rem;font-weight:600;color:#0f172a}
    .ap-listhead p{font-size:.8rem;color:#64748b}
    .ap-listhead b{color:#0f172a;font-weight:600}
    .ap-table{width:100%;font-size:.875rem;border-collapse:collapse}
    .ap-table thead th{padding:.65rem 1rem;text-align:left;font-size:.75rem;font-weight:600;color:#64748b;background:#f8fafc;border-bottom:1px solid #e6eaf0;white-space:nowrap}
    .ap-table td{padding:.85rem 1rem;vertical-align:middle}
    .ap-table tbody tr+tr td{border-top:1px solid #eef1f5}
    .ap-table tbody tr.ap-row{transition:background-color .12s}
    .ap-table tbody tr.ap-row:hover{background:#f8fafc}
    .ap-table tbody tr.ap-late td:first-child{box-shadow:inset 3px 0 0 #ef4444}
    .ap-table tbody tr.ap-daysep td{padding:.5rem 1rem;background:#f8fafc;border-top:1px solid #e6eaf0;font-size:.75rem;font-weight:600;color:#475569}
    .ap-xl-only{display:none!important}
    @media (min-width:1280px){ .ap-xl-only{display:table-cell!important} .ap-xl-hide{display:none!important} }

    .ap-person{display:flex;align-items:center;gap:.75rem;min-width:0}
    .ap-avatar{flex:none;width:2.25rem;height:2.25rem;border-radius:.65rem;display:grid;place-items:center;font-size:.8rem;font-weight:700}
    .ap-name{font-weight:600;color:#0f172a;line-height:1.25}
    .ap-sub{font-size:.75rem;color:#64748b;line-height:1.35}
    .ap-sub i{font-size:.65rem;margin-right:.25rem}
    .ap-strong{font-weight:500;color:#1e293b}
    .ap-pill{display:inline-flex;align-items:center;gap:.3rem;margin-left:.4rem;padding:.05rem .45rem;border-radius:999px;background:#f1f5f9;color:#475569;font-size:.68rem;font-weight:500}
    .ap-rel{font-size:.75rem;color:#64748b}
    .ap-rel.soon{color:#b45309}
    .ap-rel.late{color:#b91c1c;font-weight:600}
    .ap-note{display:flex;gap:.35rem;margin-top:.3rem;font-size:.75rem;color:#64748b;line-height:1.35}
    .ap-note i{margin-top:.15rem;font-size:.65rem;color:#94a3b8}
    .ap-time{font-variant-numeric:tabular-nums;font-weight:500;color:#1e293b}

    .ap-cell-ico{display:flex;align-items:flex-start;gap:.55rem}
    .ap-ico{display:none;flex:none;width:1rem;margin-top:.2rem;text-align:center;font-size:.8rem;color:#94a3b8}

    /* HP: tiap baris jadi kartu ringkas → [penghuni | status] / info bergambar ikon / aksi */
    @media (max-width:767px){
        .ap-table,.ap-table tbody{display:block;width:100%}
        .ap-table thead{display:none}
        .ap-table tbody{padding:.7rem}
        .ap-table tbody tr.ap-row{display:grid;grid-template-columns:minmax(0,1fr) auto;column-gap:.75rem;align-items:center;margin-bottom:.65rem;padding:.85rem .9rem;border:1px solid #e6eaf0;border-radius:14px;background:#fff}
        .ap-table tbody tr.ap-row:hover{background:#fff}
        .ap-table tbody tr.ap-late{border-left:3px solid #ef4444}
        .ap-table tbody tr.ap-late td:first-child{box-shadow:none}
        .ap-table tbody tr+tr td{border-top:0}
        .ap-table td{display:block;min-width:0;padding:0;border:0}
        .ap-table td.c-person{grid-column:1;grid-row:1}
        .ap-table td.c-status{grid-column:2;grid-row:1;text-align:right}
        .ap-table td.c-status .ap-sub{margin-top:.25rem}
        .ap-table td.c-apt{grid-column:1/-1;margin-top:.7rem}
        .ap-table td.c-period{grid-column:1/-1;margin-top:.45rem}
        .ap-hist td.c-period{grid-column:1}
        .ap-hist td.c-time{grid-column:2;margin-top:.45rem;text-align:right}
        .ap-table td.c-act{grid-column:1/-1;margin-top:.8rem;padding-top:.8rem;border-top:1px solid #eef1f5}
        .ap-ico{display:block}
        .ap-table tbody tr.ap-daysep{display:block;margin:.2rem 0 .55rem}
        .ap-table tbody tr.ap-daysep td{display:block;padding:.4rem .75rem;border:0;border-radius:8px}
    }

    /* Pagination & empty state */
    .ap-pager{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;padding:.8rem 1.25rem;border-top:1px solid #e6eaf0;font-size:.8rem;color:#64748b}
    .ap-pager b{color:#0f172a;font-weight:600}
    .ap-pager-nav{display:flex;align-items:center;gap:.4rem}
    .ap-pager-page{padding:0 .5rem;font-variant-numeric:tabular-nums}
    @media (max-width:639px){
        .ap-pager{flex-direction:column;align-items:stretch;padding:.8rem .9rem;text-align:center}
        .ap-pager-nav{justify-content:space-between}
        .ap-pager-nav .ap-btn{flex:1;height:2.5rem}
        .ap-pager-page{flex:none}
    }
    .ap-empty{padding:3rem 1rem;text-align:center}
    .ap-empty-ico{width:3.5rem;height:3.5rem;margin:0 auto 1rem;display:grid;place-items:center;border-radius:1rem;background:#f1f5f9;color:#94a3b8;font-size:1.3rem}
    .ap-empty h3{font-size:1rem;font-weight:600;color:#0f172a}
    .ap-empty p{max-width:28rem;margin:.3rem auto 0;font-size:.85rem;color:#64748b}

    /* Tombol */
    .ap-actions{display:flex;flex-wrap:wrap;align-items:center;gap:.35rem}
    .ap-btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;height:2rem;padding:0 .8rem;border-radius:.6rem;border:1px solid transparent;font-size:.78rem;font-weight:600;white-space:nowrap;cursor:pointer;transition:background-color .15s,border-color .15s}
    .ap-btn-lg{height:2.3rem;padding:0 1rem;font-size:.85rem}
    .ap-btn-blue{background:#2563eb;color:#fff}.ap-btn-blue:hover{background:#1d4ed8}
    .ap-btn-green{background:#16a34a;color:#fff}.ap-btn-green:hover{background:#15803d}
    .ap-btn-red{background:#dc2626;color:#fff}.ap-btn-red:hover{background:#b91c1c}
    .ap-btn-indigo{background:#4f46e5;color:#fff}.ap-btn-indigo:hover{background:#4338ca}
    .ap-btn-ghost{background:#fff;color:#334155;border-color:#dfe4ec}.ap-btn-ghost:hover{background:#f8fafc}
    .ap-btn-off{background:#f1f5f9;color:#94a3b8;cursor:not-allowed}
    .ap-btn.is-loading{pointer-events:none;opacity:.75}
    .ap-ibtn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;height:2rem;min-width:2rem;border-radius:.6rem;border:1px solid transparent;background:transparent;color:#64748b;font-size:.8rem;cursor:pointer;transition:background-color .15s,color .15s}
    .ap-ibtn:hover{background:#f1f5f9;color:#0f172a}
    .ap-ibtn.indigo:hover{background:#eef2ff;color:#4338ca}
    .ap-ibtn.danger:hover{background:#fef2f2;color:#b91c1c}
    .ap-ibtn .ap-lbl{display:none;font-size:.78rem;font-weight:500}
    @media (max-width:767px){
        .ap-actions{flex-wrap:nowrap;gap:.4rem}
        .ap-actions .ap-btn{flex:1;height:2.5rem;font-size:.85rem}
        .ap-ibtn{flex:none;width:2.5rem;height:2.5rem;border-color:#dfe4ec;background:#fff}
    }
    .ap-btn:focus-visible,.ap-ibtn:focus-visible,.chip:focus-visible,.ap-kpi:focus-visible,.ap-link:focus-visible,.ap-search-go:focus-visible{outline:2px solid #2563eb;outline-offset:2px}

    /* Modal */
    .ap-modal{position:fixed;inset:0;z-index:80;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.45);backdrop-filter:blur(2px)}
    .ap-modal.is-open{display:flex}
    .ap-panel{width:100%;max-width:28rem;max-height:calc(100vh - 2rem);overflow:auto;background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(15,23,42,.28)}
    .ap-panel.sm{max-width:24rem}
    .ap-modal.is-open .ap-panel{animation:ap-pop .16s ease-out}
    @media (max-width:639px){
        .ap-modal{align-items:flex-end;padding:0}
        .ap-panel,.ap-panel.sm{max-width:none;max-height:92vh;border-radius:18px 18px 0 0}
        .ap-modal.is-open .ap-panel{animation:ap-sheet .2s ease-out}
        .ap-panel-f{position:sticky;bottom:0;border-radius:0}
        .ap-panel-f .ap-btn{flex:1;height:2.6rem}
    }
    @keyframes ap-sheet{from{transform:translateY(24px);opacity:.6}to{transform:none;opacity:1}}
    @keyframes ap-pop{from{opacity:0;transform:translateY(8px) scale(.98)}to{opacity:1;transform:none}}
    .ap-panel-h{display:flex;align-items:center;gap:.8rem;padding:1.1rem 1.25rem .4rem}
    .ap-panel-h h3{font-size:1rem;font-weight:700;color:#0f172a;line-height:1.3}
    .ap-panel-h p{font-size:.78rem;color:#64748b}
    .ap-tile{flex:none;width:2.4rem;height:2.4rem;border-radius:.7rem;display:grid;place-items:center;background:var(--soft);color:var(--c)}
    .ap-x{margin-left:auto;width:2rem;height:2rem;border-radius:.5rem;color:#94a3b8;cursor:pointer}
    .ap-x:hover{background:#f1f5f9;color:#475569}
    .ap-panel-b{padding:.75rem 1.25rem 1rem}
    .ap-panel-f{display:flex;justify-content:flex-end;gap:.5rem;padding:.9rem 1.25rem;background:#f8fafc;border-top:1px solid #eef1f5;border-radius:0 0 16px 16px}
    .ap-label{display:block;margin-bottom:.35rem;font-size:.78rem;font-weight:600;color:#334155}
    .ap-field+.ap-field{margin-top:.9rem}
    .ap-alert{padding:.6rem .8rem;border-radius:.65rem;font-size:.78rem;line-height:1.45}
    .ap-alert.amber{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
    .ap-alert.red{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
    .ap-dl{display:grid;grid-template-columns:auto 1fr;gap:.5rem 1.25rem;font-size:.85rem}
    .ap-dl dt{color:#64748b}
    .ap-dl dd{text-align:right;font-weight:500;color:#0f172a;word-break:break-word}
    .ap-dl-title{margin:.9rem 0 .4rem;font-size:.78rem;font-weight:600;color:#475569}
    .ap-dl-title:first-child{margin-top:.2rem}

    @media (prefers-reduced-motion:reduce){
        .ap-kpi,.ap-btn,.ap-ibtn,.chip,.ap-input,.ap-table tr{transition:none}
        .ap-kpis{scroll-behavior:auto}
        .ap-modal.is-open .ap-panel{animation:none!important}
    }
</style>