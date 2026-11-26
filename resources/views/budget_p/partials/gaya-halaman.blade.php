{{-- Gaya bersama halaman pengaturan (Master Data, Hak Akses / PIC Saya, Tugas PIC). Dipakai lewat @push('styles') @include(...) @endpush. Semua kelas berawalan pg-. --}}
<style>
    .bud .pg-head{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-start;justify-content:space-between;margin-bottom:16px}
    .bud .pg-head h2{margin:0;font-size:22px;font-weight:700}
    .bud .pg-head p{margin:3px 0 0;color:var(--mu);font-size:13.5px;max-width:760px}
    .bud .pg-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px}
    .bud .pg-stat{background:var(--card);border:1px solid var(--ln);border-radius:12px;padding:12px 14px;display:flex;gap:12px;align-items:center}
    .bud .pg-stat .ic{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;background:#eff6ff;color:#2563eb;flex:none}
    .bud .pg-stat .ic.g{background:#ecfdf5;color:#059669}.bud .pg-stat .ic.v{background:#f5f3ff;color:#7c3aed}.bud .pg-stat .ic.a{background:#fffbeb;color:#d97706}
    .bud .pg-stat b{display:block;font-size:20px;line-height:1.1}
    .bud .pg-stat span{font-size:12px;color:var(--mu)}

    .bud .pg-tabs{display:flex;gap:4px;border-bottom:1px solid var(--ln);margin-bottom:16px;overflow-x:auto}
    .bud .pg-tab{font:inherit;background:none;border:0;border-bottom:3px solid transparent;padding:9px 14px;cursor:pointer;color:var(--mu);font-weight:600;white-space:nowrap}
    .bud .pg-tab:hover{color:rgb(var(--ink))}
    .bud .pg-tab.on{color:var(--ac);border-bottom-color:var(--ac)}
    .bud .pg-tab .n{display:inline-block;min-width:20px;text-align:center;background:var(--bar);color:var(--mu);border-radius:999px;font-size:11px;padding:0 6px;margin-left:5px}
    .bud .pg-pane{display:none}.bud .pg-pane.on{display:block}

    .bud .pg-card{background:var(--card);border:1px solid var(--ln);border-radius:12px;margin-bottom:16px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
    .bud .pg-card>.hd{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--ln)}
    .bud .pg-card>.hd h3{margin:0;font-size:15px;font-weight:700}
    .bud .pg-card>.bd2{padding:14px 16px}
    .bud .pg-tool{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
    .bud .pg-tool input[type=search]{min-width:240px}

    .bud table.pg-t{width:100%;border-collapse:collapse}
    .bud table.pg-t th{text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--mu);font-weight:700;background:#f8fafc;padding:9px 14px;border-bottom:1px solid var(--ln);white-space:nowrap}
    .bud table.pg-t td{padding:10px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
    .bud table.pg-t tr:last-child td{border-bottom:0}
    .bud table.pg-t tbody tr:hover td{background:#fafcff}
    .bud table.pg-t .r{text-align:right;white-space:nowrap}

    .bud .pg-av{width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;background:#64748b;flex:none}
    .bud .pg-who{display:flex;gap:10px;align-items:center}
    .bud .pg-who b{display:block;line-height:1.2}.bud .pg-who small{color:var(--mu);font-size:12px}
    .bud .pg-pill{display:inline-block;font-size:11.5px;font-weight:600;padding:2px 9px;border-radius:999px;background:var(--bar);color:#475569;margin:1px 3px 1px 0;white-space:nowrap}
    .bud .pg-pill.admin{background:#fee2e2;color:#b91c1c}.bud .pg-pill.dept_head{background:#dbeafe;color:#1d4ed8}.bud .pg-pill.pic{background:#d1fae5;color:#047857}
    .bud .pg-pill.ok{background:#dcfce7;color:#15803d}.bud .pg-pill.off{background:#f1f5f9;color:#64748b}.bud .pg-pill.wn{background:#fef3c7;color:#b45309}
    .bud .pg-pill.pc{background:#ede9fe;color:#6d28d9}

    .bud .pg-sw{position:relative;display:inline-block;width:40px;height:22px;flex:none}
    .bud .pg-sw input{opacity:0;width:0;height:0;position:absolute}
    .bud .pg-sw i{position:absolute;inset:0;background:#cbd5e1;border-radius:999px;transition:.15s;cursor:pointer}
    .bud .pg-sw i:before{content:"";position:absolute;width:16px;height:16px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.15s;box-shadow:0 1px 2px rgba(0,0,0,.3)}
    .bud .pg-sw input:checked+i{background:var(--ac)}.bud .pg-sw input:checked+i:before{transform:translateX(18px)}
    .bud .pg-sw input:focus-visible+i{outline:2px solid var(--ac);outline-offset:2px}

    .bud .pg-empty{text-align:center;color:var(--mu);padding:34px 16px}
    .bud .pg-empty b{display:block;color:rgb(var(--ink));margin-bottom:4px}
    .bud .pg-note{background:#f8fafc;border:1px solid var(--ln);border-radius:10px;padding:10px 14px;color:#475569;font-size:13px;margin-bottom:14px}
    .bud .pg-note.wn{background:#fffbeb;border-color:#fde68a;color:#92400e}
    .bud .pg-note.dg{background:#fef2f2;border-color:#fecaca;color:#991b1b}

    .bud .pg-f{display:grid;gap:12px}
    .bud .pg-f label>span{display:block;font-size:12.5px;color:var(--mu);margin-bottom:3px;font-weight:600}
    .bud .pg-f input[type=text],.bud .pg-f input[type=number],.bud .pg-f input[type=search],.bud .pg-f select{width:100%}
    .bud .pg-f .two{display:grid;grid-template-columns:1fr 110px;gap:12px}
    .bud .pg-acts{display:flex;gap:8px;justify-content:flex-end;margin-top:6px}
    .bud .pg-found{border:1px solid var(--ln);border-radius:8px;margin-top:4px}
    .bud .pg-found:empty{display:none}
    .bud dialog.pg-dlg{width:min(520px,94vw)}

    /* Kategori / subkategori */
    .bud details.pg-kat{border:1px solid var(--ln);border-left:5px solid var(--kc,#4a6485);border-radius:10px;margin-bottom:10px;background:var(--card);overflow:hidden}
    .bud details.pg-kat>summary{list-style:none;cursor:pointer;display:flex;flex-wrap:wrap;gap:8px 12px;align-items:center;padding:11px 14px;background:color-mix(in srgb,var(--kc,#4a6485) 7%,#fff)}
    .bud details.pg-kat>summary::-webkit-details-marker{display:none}
    .bud details.pg-kat>summary:before{content:"\25B8";color:var(--kc,#4a6485);font-size:14px;transition:.12s}
    .bud details.pg-kat[open]>summary:before{transform:rotate(90deg)}
    .bud details.pg-kat .nm{font-weight:700;font-size:14.5px}
    .bud details.pg-kat .sp{flex:1}
    .bud details.pg-kat.c1{--kc:#4f8a83}.bud details.pg-kat.c2{--kc:#7b6fa6}.bud details.pg-kat.c3{--kc:#a8814a}.bud details.pg-kat.c4{--kc:#a8566b}
    .bud .pg-sub{display:grid;grid-template-columns:minmax(180px,1.4fr) 70px 90px minmax(160px,1.4fr) minmax(120px,1fr) auto;gap:8px 12px;align-items:center;padding:9px 14px;border-top:1px solid #f1f5f9}
    .bud .pg-sub.hd{background:#f8fafc;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--mu);font-weight:700}
    @media(max-width:900px){.bud .pg-sub{grid-template-columns:1fr 1fr}.bud .pg-sub.hd{display:none}}
    .bud .pg-foot{padding:10px 14px;border-top:1px solid var(--ln);background:#fcfdff;display:flex;gap:8px;flex-wrap:wrap;align-items:center}

    /* Kartu tahun */
    .bud .pg-years{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
    .bud .pg-year{border:1px solid var(--ln);border-top:5px solid var(--yc);border-radius:12px;padding:14px;background:var(--card)}
    .bud .pg-year .y{font-size:26px;font-weight:800;color:var(--yc);line-height:1}
    .bud .pg-year .st{margin:8px 0 10px;font-size:13px}
</style>
<script>
    /* Tab sederhana: tombol .pg-tab[data-tab] membuka .pg-pane#pane-{nama}; tab terakhir diingat lewat #hash */
    function pgTab(nama){
        document.querySelectorAll('.pg-tab').forEach(function(b){b.classList.toggle('on', b.dataset.tab===nama)});
        document.querySelectorAll('.pg-pane').forEach(function(p){p.classList.toggle('on', p.id==='pane-'+nama)});
        try{history.replaceState(null,'','#tab-'+nama)}catch(e){}
    }
    document.addEventListener('DOMContentLoaded', function(){
        var m=(location.hash||'').match(/^#tab-(.+)$/);
        var ada=m && document.getElementById('pane-'+m[1]);
        var awal=ada ? m[1] : (document.querySelector('.pg-tab')||{dataset:{}}).dataset.tab;
        if(awal) pgTab(awal);
    });
    /* Saring baris/kartu berdasarkan teks (untuk kotak cari) */
    function pgCari(inp, selektor){
        var q=inp.value.trim().toLowerCase();
        document.querySelectorAll(selektor).forEach(function(el){
            el.style.display = (!q || el.textContent.toLowerCase().indexOf(q)>-1) ? '' : 'none';
        });
    }
</script>
