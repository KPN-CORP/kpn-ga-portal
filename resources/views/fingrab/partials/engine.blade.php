<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
@verbatim
    const $=s=>document.querySelector(s),esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const idr=n=>'Rp '+Math.round(n).toLocaleString('id-ID'),num=n=>n.toLocaleString('id-ID'),sh=v=>{const a=Math.abs(v),U=[[1e12,' T',1],[1e9,' M',1],[1e6,' jt',1],[1e3,' rb',0]];for(let i=0;i<U.length;i++){if(a>=U[i][0]){let x=+(v/U[i][0]).toFixed(U[i][2]);if(Math.abs(x)>=1000&&i>0)return +(v/U[i-1][0]).toFixed(U[i-1][2])+U[i-1][1];return x+U[i][1]}}return v};
    const CO=X.co,VN=['GrabFood','Perjalanan','GrabExpress'],MN={};
    /* === KONFIGURASI: sesuaikan dengan tb_bisnis_unit dan akun Grab === */
    const MNAMA=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    [...new Set(X.rows.map(r=>r[0].slice(0,7)))].sort().forEach(m=>MN[m]=MNAMA[+m.slice(5)-1]+' '+m.slice(0,4));
    const BUS=['KPN Corporation','Cement','Property','Plantations','Downstream'];
    const SERVER=true;
    const ROLES={all:{n:'Admin Corporate (semua Bisnis unit)',bu:null}};
    BUS.slice(1).forEach(b=>ROLES[b]={n:'Admin '+b,bu:b});
    /* PT penagih di file Grab (COMPANY_NAME) ke Bisnis unit. HCIS tidak punya data PT, jadi BU dikonversi dari sini. */
    const PTBU=[[/thip/,'Plantations'],[/cemindo|cement|semen/,'Cement'],[/cempaka/,'Property'],[/energi\s*unggul/,'Downstream']];
    const buOfPT=n=>{
      const t=(n||'').toLowerCase(),m=PTBU.find(p=>p[0].test(t));
      return m?m[1]:'Belum terpetakan'
    };
    const guess=t=>{
      t=(t||'').toLowerCase();
      const m=PTBU.find(p=>p[0].test(t));
      return m?m[1]:/plantation|sawit|palm|agro|kebun/.test(t)?'Plantations':/propert|realty|estate/.test(t)?'Property':/downstream|hilir|oleo|refiner/.test(t)?'Downstream':/kpn|corporate|holding/.test(t)?'KPN Corporation':null
    };
    /* === STATE === */
    let role='all',mode='rp',tab=PAGE,RW=[],SP=null;
    const HC=new Map(),HCE=new Map(),GCM={};
    const SORT={dt:{c:0,d:1},et:{c:5,d:-1}};
    const F={c:'',t:'',m:'',v:'',g:'',b:'',u:'',o:'',e:''};
    /* Filter tidak dibawa antar halaman. Hanya saat halaman dimuat ulang (refresh, atau setelah simpan koreksi ID) filter halaman itu dipulihkan. */
    try{
      const nav=(performance.getEntriesByType('navigation')[0]||{}).type;
      if(nav=='reload')Object.assign(F,JSON.parse(sessionStorage.getItem('fg_F_'+PAGE)||'{}'))
    }catch(e){
    }

    const FL_ALL=[['b','Bisnis unit'],['c','Perusahaan'],['u','Unit'],['o','Office area'],['t','Tahun'],['m','Bulan'],['v','Layanan'],['g','Grup'],['e','Karyawan']];
    /* Filter per halaman: hanya yang berguna untuk isi halaman itu (dashboard memakai semuanya) */
    const FK={ep:['b','c','u','o','t','m','v','e'],mt:['b','c','u','t','m','e']};
    const FL=FL_ALL.filter(([k])=>!FK[PAGE]||FK[PAGE].includes(k));
    FL_ALL.forEach(([k])=>{
      if(!FL.some(x=>x[0]==k))F[k]=''
    });
    const G={c:r=>[r.c,CO[r.c]],t:r=>[r.d.slice(0,4),r.d.slice(0,4)],m:r=>[r.d.slice(5,7),MNAMA[+r.d.slice(5,7)-1]],v:r=>[r.v,VN[r.v]],g:r=>[r.g,X.gr[r.g]],b:r=>[r.bu,r.bu],u:r=>[r.un,r.un],o:r=>[r.of,r.of],e:r=>[r.e,r.en+(r.id?' ('+r.id+')':'')]};
    /* === PENCOCOKAN DATA: BCR Grab ke api_emp_hcis (employee_id, lalu email) dan penentuan Bisnis unit === */
    /* Kode di dalam kurung pada nama unit HCIS, mis. "Finance (FIN01)", tidak ditampilkan */
    const cu=s=>String(s||'').replace(/\s*\([^)]*\)/g,' ').replace(/\s+/g,' ').trim()||'-';
    function enrich(){
      RW=X.rows.map(r=>{
        const e=X.em[r[3]],h0=e[0]&&HC.get(e[0]),h1=h0||(e[2]&&HCE.get(e[2]))||null,h=e[8]?{group_company:e[5],unit:e[6],office_area:e[7]}:h1, mt=e[8]?(e[8]=='id'?'Cocok via ID':e[8]=='email'?'Cocok via email':'Tidak ada di HCIS'):h1?(h0?'Cocok via ID':'Cocok via email'):(HC.size?'Tidak ada di HCIS':'HCIS belum dimuat'), ok=mt.startsWith('Cocok'),gc=ok?(h.group_company||'-'):'-',bu=r[10]||buOfPT(CO[r[1]]),gbu=ok?(GCM[gc]||guess(gc)):null;
        return{d:r[0],c:r[1],g:r[2],e:r[3],v:r[4],a:r[5],h:r[6],y:r[7],k:r[8],x:r[9],en:e[1],id:e[0],bu,gc,un:ok?cu(h.unit):'Belum cocok',of:ok?h.office_area||'-':'Belum cocok',st:mt,mis:!!gbu&&gbu!='KPN Corporation'&&gbu!=bu}
      })
    }

    /* === FILTER DAN CAKUPAN AKSES (scope per Bisnis unit; di Laravel lakukan di server) === */
    const scope=()=>RW.filter(r=>ROLES[role].bu==null||r.bu==ROLES[role].bu);
    const test=(r,skip)=>Object.keys(F).every(k=>k==skip||F[k]===''||String(G[k](r)[0])==F[k]);
    const cv=n=>getComputedStyle($('#efg')).getPropertyValue(n).trim();
    const CMP=new Intl.Collator('id').compare;
    const OPT={};
    function refill(){
      const S=scope();
      let pool=S;
      if(tab=='mt'){
        /* Kecocokan: pilihan filter hanya berisi yang muncul di daftar "perlu dicek" */
        const bad=new Set(S.filter(r=>r.st!='Cocok via ID'&&r.st!='Cocok via email').map(r=>r.e));
        pool=S.filter(r=>bad.has(r.e))
      }
      FL.forEach(([k])=>{
        const m=new Map();
        pool.filter(r=>test(r,k)).forEach(r=>{
          const x=G[k](r);
          m.set(String(x[0]),x[1])
        });
        if(F[k]!==''&&!m.has(F[k]))F[k]='';
        OPT[k]=[...m.entries()].sort(k=='m'||k=='t'?(a,b)=>String(a[0]).localeCompare(String(b[0])):(a,b)=>CMP(String(a[1]),String(b[1]))); /* Bulan: urut kalender (nilainya 01-12), Tahun urut angka, lainnya urut abjad */
        $('#f'+k).value=F[k]===''?'':m.get(F[k])
      })
    }

    /* === GRAFIK === */
    Chart.defaults.font.size=10;
    /* Plugin kecil: tampilkan nilai di ujung batang (aktif per grafik lewat plugins:{vlabel:{on:1}}) */
    Chart.register({id:'vlabel',afterDatasetsDraw(ch,a,op){
      if(!op||!op.on)return;
      const ctx=ch.ctx,hz=ch.options.indexAxis=='y';
      ctx.save();
      ctx.font='600 10px '+Chart.defaults.font.family;
      ctx.fillStyle=cv('--ink');
      ctx.textBaseline='middle';
      ctx.textAlign=hz?'left':'center';
      ch.getDatasetMeta(0).data.forEach((el,i)=>{
        const v=ch.data.datasets[0].data[i];
        if(v==null)return;
        let tx=String(sh(v));
        if(op.tot){
          const p=v/op.tot*100,ps=(p>=10?Math.round(p):+p.toFixed(1))+'%';
          tx+=hz?' · '+ps:' ('+ps+')'
        }
        ctx.fillText(tx,hz?el.x+6:el.x,hz?el.y:el.y-9)
      });
      ctx.restore()
    }});
    /* Tooltip berformat: Rp / jumlah transaksi, plus persentase untuk donat */
    const TTf=fm=>({callbacks:{label:c=>{
      const v=typeof c.parsed=='number'?c.parsed:(c.chart.options.indexAxis=='y'?c.parsed.x:c.parsed.y);
      if(c.chart.config.type=='doughnut'){
        const t=c.dataset.data.reduce((p,q)=>p+q,0);
        return c.label+': '+fm(v)+(t?' ('+(v/t*100).toFixed(1)+'%)':'')
      }
      return (c.dataset.label?c.dataset.label+': ':'')+fm(v)
    }}});
    const CH={},PEND={};
    function mk(id,type,labels,sets,o={}){
      const box=$('#'+id).closest('.p');
      if(CH[id]){
        CH[id].destroy();
        delete CH[id]
      }
      if(box&&box.classList.contains('hid')){
        PEND[id]=[id,type,labels,sets,o];
        return
      }
      delete PEND[id];
      const c=[cv('--cf'),cv('--ct'),cv('--cx')];
      CH[id]=new Chart($('#'+id),{type,data:{labels,datasets:sets.map((s,i)=>({backgroundColor:type=='doughnut'?c:c[i],...s}))},options:{responsive:true,maintainAspectRatio:false,animation:false,onHover:(e,els)=>{
        if(e.native)e.native.target.style.cursor=els.length?'pointer':'default'
      },...o,plugins:{legend:{display:type=='doughnut'||sets.length>1,position:'bottom'},...(o.plugins||{})}}})
    }

    const mon={ticks:{callback:sh}};
    /* panel samping */
    const PG=10;
    function openSP(t,pred){
      SP={t,pred,q:'',pg:0};
      drawSP();
      $('#efg').classList.add('so')
    }

    function closeSP(){
      SP=null;
      $('#efg').classList.remove('so');
      $('#sp').hidden=true
    }

    const cut=(s,n)=>!s?'-':s.length>n?s.slice(0,n)+'…':s;
    function openDet(r){
      const x=r.x,rw=(l,v)=>`<tr><td class="mu">${l}</td><td>${v}</td></tr>`;
      const fare=x[6]==null?rw('Total tarif',`<b>${idr(r.a)}</b><br><span class="mu">BCR GrabFood tidak memuat rincian tarif</span>`):rw('Tarif dasar',idr(x[6]))+rw('Promo',idr(x[7]))+rw('Tol dan surcharge',idr(x[8]))+rw('Late fee',idr(x[9]))+rw('Biaya lain',idr(x[10]))+rw('Total tarif',`<b>${idr(r.a)}</b>`);
      const d=$('#dlg');
      d.innerHTML=`<div class="sh"><b>${VN[r.v]} · ${esc(r.k)}${x[11]?' · '+esc(x[11]):''}</b><button id="dx" aria-label="Tutup">&times;</button></div><table>${rw('Waktu',r.d.slice(8)+'/'+r.d.slice(5,7)+'/'+r.d.slice(0,4)+', jam '+String(r.h).padStart(2,'0'))}${rw('Booking / Trip code',esc(r.k)+' / '+(x[11]?esc(x[11]):'-'))}${rw('Karyawan',esc(r.en)+'<br><span class="mu">'+esc(r.id||'tanpa ID')+' · '+esc(r.st)+'</span>')}${rw('Bisnis unit › Unit',esc(r.bu)+' › '+esc(r.un)+'<br><span class="mu">BU dari PT penagih: '+esc(CO[r.c])+'</span>')}${rw('Office area',esc(r.of))+rw('Grup HCIS',esc(r.gc))}${rw('Grup',esc(X.gr[r.g])+(x[5]?' · '+esc(x[5]):''))}${rw('Keterangan',esc(x[4]||'-'))}${rw('Jarak',x[3]+' km')}${rw('Dari',esc(x[0]||'-'))}${x[2]?rw('Singgah',esc(x[2])):''}${rw('Tujuan',esc(x[1]||'-'))}${fare}</table>`;
      $('#dx').onclick=()=>d.close();
      d.onclick=e=>{
        if(e.target==d)d.close()
      };
      d.showModal()
    }

    function drawSP(){
      if(!SP)return;
      const all=scope().filter(r=>test(r,'')&&SP.pred(r)).sort((x,y)=>y.d.localeCompare(x.d)),q=SP.q.trim().toLowerCase(),
        a=q?all.filter(r=>(r.en||'').toLowerCase().includes(q)||String(r.id||'').toLowerCase().includes(q)):all,
        tot=a.reduce((s,r)=>s+r.a,0),emp=new Set(a.map(r=>r.e)).size,pages=Math.max(1,Math.ceil(a.length/PG));
      SP.pg=Math.min(Math.max(0,SP.pg),pages-1);
      SP.list=a.slice(SP.pg*PG,(SP.pg+1)*PG);
      $('#sp').hidden=false;
      $('#sp').innerHTML=`<div class="sh"><b>${esc(SP.t)}</b><button id="sx" aria-label="Tutup">&times;</button></div><input id="sq" type="search" class="sq" placeholder="Cari nama atau ID karyawan" value="${esc(SP.q)}" aria-label="Cari karyawan"><div class="ss"><div><span class="mu">Transaksi</span><b>${num(a.length)}</b></div><div><span class="mu">Total biaya</span><b>${idr(tot)}</b></div><div><span class="mu">Karyawan</span><b>${emp}</b></div><div><span class="mu">Rata-rata/trx</span><b>${a.length?idr(tot/a.length):'-'}</b></div></div>
    <div class="w"><table><tr><th>Tanggal</th><th>Karyawan</th><th>Rute (klik untuk lengkap)</th><th class="r">Tarif</th></tr>${SP.list.length?SP.list.map((r,i)=>`<tr class="dr" tabindex="0" data-i="${i}"><td>${r.d.slice(8)}/${r.d.slice(5,7)}<br><span class="mu">${String(r.h).padStart(2,'0')}:00 · ${VN[r.v]}</span></td><td>${esc(r.en)}<br><span class="mu">${esc(r.bu)} › ${esc(r.un)}</span></td><td class="ad" title="${esc(r.x[0]+' → '+r.x[1])}">${esc(cut(r.x[0],30))}<br><span class="mu">${esc(cut(r.x[1],30))}</span></td><td class="r">${idr(r.a)}<br><span class="mu">${r.x[6]==null?'tarif food':'dasar '+idr(r.x[6])}</span></td></tr>`).join(''):'<tr><td colspan="4" class="mu">Tidak ada transaksi yang cocok.</td></tr>'}</table></div>
    <div class="pgn"><button type="button" id="spp"${SP.pg?'':' disabled'}>‹ Sebelumnya</button><span class="mu">Halaman ${SP.pg+1} dari ${pages}${a.length?' · '+(SP.pg*PG+1)+'-'+Math.min(a.length,(SP.pg+1)*PG)+' dari '+num(a.length):''}</span><button type="button" id="spn"${SP.pg<pages-1?'':' disabled'}>Berikutnya ›</button></div>`;
      $('#sx').onclick=closeSP;
      const sq=$('#sq');
      sq.oninput=()=>{
        SP.q=sq.value;
        SP.pg=0;
        drawSP();
        const n=$('#sq');
        n.focus();
        n.setSelectionRange(n.value.length,n.value.length)
      };
      $('#spp').onclick=()=>{
        SP.pg--;
        drawSP()
      };
      $('#spn').onclick=()=>{
        SP.pg++;
        drawSP()
      };
      $('#sp').querySelectorAll('tr.dr').forEach(tr=>{
        const go=()=>openDet(SP.list[+tr.dataset.i]);
        tr.onclick=go;
        tr.onkeydown=e=>{
          if(e.key=='Enter')go()
        }
      })
    }

    /* Daftar panjang: tampil 15 baris per halaman, sisanya lewat tombol halaman */
    function pager(host,size){
      size=size||PG;
      host.querySelectorAll('table').forEach(t=>{
        const rs=[...t.querySelectorAll('tr')].slice(1);
        if(rs.length<=size)return;
        let pg=0;
        const pages=Math.ceil(rs.length/size),bar=document.createElement('div');
        bar.className='pgn';
        const show=()=>{
          rs.forEach((r,i)=>{
            r.hidden=!(i>=pg*size&&i<(pg+1)*size)
          });
          bar.innerHTML=`<button type="button" data-d="-1"${pg?'':' disabled'}>‹ Sebelumnya</button><span class="mu">Halaman ${pg+1} dari ${pages} · ${pg*size+1}-${Math.min(rs.length,(pg+1)*size)} dari ${num(rs.length)}</span><button type="button" data-d="1"${pg<pages-1?'':' disabled'}>Berikutnya ›</button>`;
          bar.querySelectorAll('button').forEach(b=>b.onclick=()=>{
            pg+=+b.dataset.d;
            show()
          })
        };
        t.insertAdjacentElement('afterend',bar);
        show()
      })
    }

    /* === RENDER HALAMAN === */
    function render(){
      try{
        sessionStorage.setItem('fg_F_'+PAGE,JSON.stringify(F))
      }catch(e){
      }
      const S=scope(),rows=S.filter(r=>test(r,'')),base=S.filter(r=>test(r,'v')),w=r=>mode=='rp'?r.a:1,sm=a=>a.reduce((s,r)=>s+w(r),0),sm0=a=>a.reduce((s,r)=>s+r.a,0),RB=ROLES[role].bu;
      $('#bd').textContent=SERVER?SERVER_LABEL:RB==null?'Admin Corporate: semua Bisnis unit':'Admin: '+RB;
      tab=PAGE;
      const ch=FL.filter(([k])=>F[k]!=='').map(([k,l])=>[k,l+': '+((OPT[k].find(x=>x[0]==F[k])||['',''])[1])]);
      $('#ch').innerHTML=ch.length?'<span class="mu">Filter aktif:</span>'+ch.map(c=>`<i>${esc(c[1])}<b data-k="${c[0]}" role="button" tabindex="0">&times;</b></i>`).join(''):'';
      $('#ch').querySelectorAll('b').forEach(b=>b.onclick=()=>{
        F[b.dataset.k]='';
        refill();
        render()
      });
      $('#hc').textContent=SERVER?'':HC.size?`HCIS dimuat: ${num(HC.size)} karyawan`:'HCIS belum dimuat (unit dan office area belum bisa dicocokkan)';
      if(tab=='ep'){
        const gp=f=>{
          const m=new Map();
          rows.forEach(r=>{
            const k=f(r),o=m.get(k)||{k,n:0,s:0,p:new Set(),r};
            o.n++;
            o.s+=r.a;
            o.p.add(r.e);
            m.set(k,o)
          });
          return[...m.values()]
        };
        const T={dt:{h:['Bisnis unit','Unit','Karyawan','Transaksi','Total biaya','Rata-rata per karyawan'],al:2,d:gp(r=>r.bu+'|'+r.un),v:o=>[o.r.bu,o.r.un,o.p.size,o.n,o.s,o.s/o.p.size],row:(o,v)=>`<tr><td>${esc(v[0])}</td><td>${esc(v[1])}</td><td class='r'>${num(v[2])}</td><td class='r'>${num(v[3])}</td><td class='r'>${idr(v[4])}</td><td class='r'>${idr(v[5])}</td></tr>`}, et:{h:['Karyawan','ID','Bisnis unit','Unit','Office area','Transaksi','Total biaya'],al:5,d:gp(r=>r.e),v:o=>[o.r.en,o.r.id||'-',o.r.bu,o.r.un,o.r.of,o.n,o.s],row:(o,v)=>`<tr class='kr' tabindex='0' data-e='${o.k}' style='cursor:pointer'><td>${esc(v[0])}</td><td>${esc(v[1])}</td><td>${esc(v[2])}</td><td>${esc(v[3])}</td><td>${esc(v[4])}</td><td class='r'>${num(v[5])}</td><td class='r'>${idr(v[6])}</td></tr>`}};
        Object.keys(T).forEach(id=>{
          const t=T[id],s=SORT[id],rs=t.d.map(o=>[o,t.v(o)]).sort((a,b)=>{
            const x=a[1][s.c],y=b[1][s.c];
            return(typeof x=='string'?CMP(x,y):x-y)*s.d||CMP(String(a[1][1]),String(b[1][1]))
          });
          $('#'+id).innerHTML='<table><tr>'+t.h.map((h,i)=>`<th class='${i>=t.al?'r':''}' tabindex='0' role='button' data-c='${i}' style='cursor:pointer'>${h}${s.c==i?(s.d>0?' ▲':' ▼'):''}</th>`).join('')+'</tr>'+rs.map(x=>t.row(x[0],x[1])).join('')+'</table>';
          $('#'+id).querySelectorAll('th').forEach(th=>{
            const go=()=>{
              const c=+th.dataset.c;
              SORT[id]=SORT[id].c==c?{c,d:-SORT[id].d}:{c,d:c<t.al?1:-1};
              render()
            };
            th.onclick=go;
            th.onkeydown=e=>{
              if(e.key=='Enter')go()
            }
          })
        });
        pager($('#dt'));
        pager($('#et'));
        $('#et').querySelectorAll('tr.kr').forEach(tr=>{
          const go=()=>{
            const e=+tr.dataset.e;
            openSP(X.em[e][1],r=>r.e==e)
          };
          tr.onclick=go;
          tr.onkeydown=e=>{
            if(e.key=='Enter')go()
          }
        });
        drawSP();
        return
      }
      if(tab=='mt'){
        const em=new Map();
        rows.forEach(r=>{
          const o=em.get(r.e)||{r,n:0};
          o.n++;
          em.set(r.e,o)
        });
        const L=[...em.values()],cnt=f=>L.filter(f).length,bad=L.filter(o=>o.r.st!='Cocok via ID'&&o.r.st!='Cocok via email').sort((a,b)=>b.n-a.n),gcs=[...new Set(rows.filter(r=>r.gc!='-').map(r=>r.gc))].sort();
        const FX=SERVER&&typeof CAN_FIX!=='undefined'&&CAN_FIX;
        const fxCell=o=>{
          const e=X.em[o.r.e],kind=e[0]?'id':e[2]?'email':'name',val=e[0]||e[2]||e[1]||'';
          return`<td><div class="fx"><input type="text" inputmode="numeric" maxlength="12" placeholder="ID HCIS 11-12 digit" aria-label="ID karyawan HCIS"><button type="button" data-fx data-kind="${kind}" data-val="${esc(val)}" data-name="${esc(o.r.en)}">Simpan</button><span class="fm"></span></div></td>`
        };
        const why=o=>{
          const e=X.em[o.r.e],s=e[3];
          return(s=='kosong'?'ID kosong':s=='teks'?'Kolom ID berisi teks "'+e[4]+'", bukan nomor karyawan':s=='email'?'Kolom ID berisi email':s=='suffix'?'ID bersuffix "'+e[4]+'" (dibersihkan jadi '+e[0]+')':'ID '+e[0])+(o.r.st=='Tidak ada di HCIS'?', tidak ada di api_emp_hcis':'')+(e[2]?'; email Grab '+e[2]:'')
        };
        $('#mt').innerHTML=`<div class="kp"><div class="p k"><span class="mu">Karyawan di data Grab</span><b>${L.length}</b></div><div class="p k" style="--c:var(--g)"><span class="mu">Cocok via ID</span><b>${cnt(o=>o.r.st=='Cocok via ID')}</b></div><div class="p k" style="--c:var(--cx)"><span class="mu">Cocok via email</span><b>${cnt(o=>o.r.st=='Cocok via email')}</b></div><div class="p k" style="--c:var(--cm)"><span class="mu">Belum cocok</span><b>${bad.length}</b></div><div class="p k" style="--c:var(--a3)"><span class="mu">Grup HCIS beda BU dengan PT penagih</span><b>${cnt(o=>o.r.mis)}</b></div></div>
    <div class="p" id="up"><h2>Muat data karyawan (export api_emp_hcis: CSV, JSON, atau SQL INSERT)</h2><input type="file" id="hf" accept=".csv,.json,.sql,.txt"> <span class="mu">Diproses di browser, tidak dikirim ke mana pun. Kolom: employee_id, fullname, email, group_company, office_area, unit.</span></div>
    <div class="p" id="gm"><h2>Pemetaan group_company HCIS ke Bisnis unit (hanya untuk pengecekan silang; BU transaksi dari PT penagih)</h2>${gcs.length?'<div class="w"><table><tr><th>group_company</th><th>Bisnis unit</th></tr>'+gcs.map(g=>`<tr><td>${esc(g)}</td><td><select data-g="${esc(g)}">${BUS.map(b=>`<option${(GCM[g]||guess(g))==b?' selected':''}>${b}</option>`).join('')}<option value=""${!(GCM[g]||guess(g))?' selected':''}>(belum terpetakan)</option></select></td></tr>`).join('')+'</table></div>':'<span class="mu">Muncul setelah data HCIS dimuat.</span>'}</div>
    <div class="p"><h2>Karyawan yang perlu dicek (${bad.length})</h2><div class="w"><table><tr><th>Karyawan</th><th>Alasan</th><th>Perusahaan Grab</th><th class="r">Transaksi</th>${FX?'<th>Perbaiki ID karyawan</th>':''}</tr>${bad.map(o=>`<tr><td>${esc(o.r.en)}</td><td style="white-space:normal">${esc(why(o))}</td><td>${CO[o.r.c]}</td><td class="r">${o.n}</td>${FX?fxCell(o):''}</tr>`).join('')}</table></div></div>`;
        if(SERVER){
          $('#up').remove();
          $('#gm').remove()
        }else $('#hf').onchange=e=>loadHC(e.target.files[0]);
        pager($('#mt'));
        if(FX)$('#mt').querySelectorAll('button[data-fx]').forEach(b=>b.onclick=async()=>{
          const box=b.closest('.fx'),inp=box.querySelector('input'),msg=box.querySelector('.fm'),id=inp.value.trim();
          const send=dry=>fetch(FIX_URL,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({kind:b.dataset.kind,value:b.dataset.val,employee_id:id,dry:dry?1:0})}).then(r=>r.json().then(j=>({ok:r.ok,j})));
          msg.textContent='';
          if(!id){
            msg.textContent='Isi ID karyawan dulu';
            return
          }
          b.disabled=true;
          try{
            const a=await send(true);
            if(!a.ok){
              msg.textContent=a.j.message||'Gagal memeriksa ID';
              return
            }
            const k=a.j;
            if(!confirm('Pasangkan "'+b.dataset.name+'" ke '+k.fullname+' ('+k.employee_id+')\nGroup company: '+(k.group_company||'-')+'\nUnit: '+(k.unit||'-')+'\n\n'+k.count+' transaksi akan diperbarui. Lanjutkan?'))return;
            const c=await send(false);
            if(!c.ok){
              msg.textContent=c.j.message||'Gagal menyimpan';
              return
            }
            location.reload()
          }catch(err){
            msg.textContent='Gagal terhubung ke server'
          }finally{
            b.disabled=false
          }
        });
        if(!SERVER)$('#mt').querySelectorAll('select[data-g]').forEach(s=>s.onchange=()=>{
          GCM[s.dataset.g]=s.value;
          enrich();
          refill();
          render()
        });
        drawSP();
        return
      }
      if(tab=='rc'){
        const R=REKON;
        $('#rt').innerHTML='<table><tr>'+['Periode','Transaksi BCR','Biaya BCR','Reimbursement invoice','Total tagihan (dengan fee dan PPN)','Transaksi dashboard','Biaya dashboard','Selisih dashboard dan BCR'].map((h,i)=>`<th class="${i?'r':''}">${h}</th>`).join('')+'</tr>'+R.map(q=>{
          const b=RW.filter(r=>r.c==q[0]&&r.d.slice(0,7)==q[1]),bs=sm0(b),d=q[5]-bs;
          return`<tr><td>${CO[q[0]]}, ${MN[q[1]]}</td><td class="r">${num(b.length)}</td><td class="r">${idr(bs)}</td><td class="r">${idr(q[2])}</td><td class="r">${idr(q[3])}</td><td class="r">${num(q[4])}</td><td class="r">${idr(q[5])}</td><td class="r">${idr(d)} (${(d/bs*100).toFixed(2)}%)</td></tr>`
        }).join('')+'</table>';
        return
      }
      const col=[cv('--cf'),cv('--ct'),cv('--cx')],tot=sm0(rows),byD={},TT=TTf(v=>mode=='rp'?idr(v):num(v));
      const lgPct=pos=>({display:true,position:pos,labels:{usePointStyle:true,boxWidth:10,generateLabels:ch=>{
        const d=ch.data.datasets[0].data,t=d.reduce((p,q)=>p+q,0);
        return Chart.overrides.doughnut.plugins.legend.labels.generateLabels(ch).map(l=>({...l,text:l.text+' · '+(t?Math.round(d[l.index]/t*100):0)+'%'}))
      }}});
      rows.forEach(r=>byD[r.d]=(byD[r.d]||0)+r.a);
      const da=Object.entries(byD).sort((a,b)=>b[1]-a[1]);
      $('#kp').innerHTML=`<div class="p k" style="--c:${cv('--ln')}"><span class="mu">Total transaksi</span><b>${num(base.length)}</b><span class="mu">${idr(sm0(base))}</span></div>`+[0,1,2].map(v=>{
        const a=base.filter(r=>r.v==v);
        return`<button class="p k${F.v==v?' sel':''}" style="--c:${col[v]}" data-v="${v}"><span class="mu">${VN[v]}</span><b>${num(a.length)}</b><span class="mu">${idr(sm0(a))}</span></button>`
      }).join('')+`<div class="p k" style="--c:${cv('--cm')}"><span class="mu">Hari tertinggi</span><b style="font-size:18px">${da.length?idr(da[0][1]):'-'}</b><span class="mu">${da.length?da[0][0]:''}</span></div>`;
      document.querySelectorAll('#kp button').forEach(b=>b.onclick=()=>{
        F.v=F.v===b.dataset.v?'':b.dataset.v;
        refill();
        render()
      });
      $('#em').hidden=rows.length>0;
      const ms=[...new Set(rows.map(r=>r.d.slice(0,7)))].sort(),dim=m=>new Date(+m.slice(0,4),+m.slice(5),0).getDate(),sM=m=>sm0(rows.filter(r=>r.d.slice(0,7)==m));
      const rM=S.filter(r=>test(r,'m')),mkeys=Object.keys(MN).filter(m=>!F.t||m.slice(0,4)==F.t),PAL=['#6C4DFF','#E040FB','#14B8A6','#F59E0B','#64748B','#EF4444'],pc9=c=>c<0?cv('--cm'):PAL[c%PAL.length],cs=F.c===''?[...new Set(rM.map(r=>r.c))].sort((a,b)=>a-b):[-1];
      const MNS=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],h9=$('#h9');
      if(h9)h9.textContent='Tren biaya per bulan, tahun '+([...new Set(mkeys.map(m=>m.slice(0,4)))].join(', ')||'-');
      const grad=k=>x=>{
        const a=x.chart.chartArea;
        if(!a)return k+'22';
        const g=x.chart.ctx.createLinearGradient(0,a.top,0,a.bottom);
        g.addColorStop(0,k+'66');
        g.addColorStop(1,k+'05');
        return g
      };
      mk('c9','line',mkeys.map(m=>MNS[+m.slice(5)-1]+' '+m.slice(0,4)),cs.map(c=>({label:c<0?'Total biaya':CO[c],borderColor:pc9(c),backgroundColor:cs.length==1?grad(pc9(c)):pc9(c),fill:cs.length==1,borderWidth:3,tension:.35,pointRadius:5,pointHoverRadius:7,pointBackgroundColor:pc9(c),pointBorderColor:'#fff',pointBorderWidth:2,data:mkeys.map(m=>{
        const a=rM.filter(r=>r.d.slice(0,7)==m&&(c<0||r.c==c));
        return a.length?sm0(a):null
      })})),{scales:{x:{ticks:{autoSkip:false,maxRotation:60},grid:{display:false}},y:{beginAtZero:true,...mon}},plugins:{tooltip:TTf(idr)},onClick:(e,els)=>{
        if(els.length){
          const m=mkeys[els[0].index],c=cs[els[0].datasetIndex];
          openSP((c<0?'Semua perusahaan':CO[c])+', '+MN[m],r=>r.d.slice(0,7)==m&&(c<0||r.c==c))
        }
      }
      });
      $('#mtn').innerHTML=cs.map(c=>{
        const pts=mkeys.map(m=>{
          const a=rM.filter(r=>r.d.slice(0,7)==m&&(c<0||r.c==c));
          return a.length?[m,sm0(a)]:null
        }).filter(Boolean),nm=c<0?'Total biaya':CO[c];
        if(!pts.length)return'';
        if(pts.length<2)return`<div>${esc(nm)}: ${esc(MN[pts[0][0]])} ${idr(pts[0][1])}. Tren terlihat setelah ada data bulan berikutnya.</div>`;
        const[a,b]=pts.slice(-2),pc=a[1]?(b[1]/a[1]-1)*100:null;
        return pc===null?'':`<div>${esc(nm)}: ${esc(MN[b[0]])} <b class="${pc>=0?'up':'dn'}">${pc>=0?'▲ naik':'▼ turun'} ${Math.abs(pc).toFixed(1)}%</b> dibanding ${esc(MN[a[0]])} (${idr(a[1])} ke ${idr(b[1])})</div>`
      }).join('')||'<div>Belum ada data bulanan.</div>';
      const mm=F.t&&F.m?F.t+'-'+F.m:'',keys=mm?[...Array(dim(mm))].map((_,i)=>mm+'-'+String(i+1).padStart(2,'0')):Object.keys(MN).filter(k=>(!F.t||k.slice(0,4)==F.t)&&(!F.m||k.slice(5)==F.m)),pk=r=>mm?r.d:r.d.slice(0,7);
      $('#h1').textContent=(mode=='rp'?'Biaya':'Transaksi')+' per '+(mm?'hari':'bulan');
      mk('c1','bar',keys.map(k=>mm?+k.slice(8):MN[k]),[0,1,2].map(v=>({label:VN[v],data:keys.map(k=>sm(rows.filter(r=>r.v==v&&pk(r)==k)))})),{scales:{x:{stacked:true,grid:{display:false}},y:{stacked:true,...mon}},plugins:{tooltip:TT},onClick:(e,els)=>{
        if(els.length){
          const k=keys[els[0].index],v=els[0].datasetIndex;
          openSP(VN[v]+', '+(mm?k:MN[k]),r=>r.v==v&&pk(r)==k)
        }
      }
      });
      /* Komposisi layanan mengikuti semua filter, termasuk Layanan: dengan filter Perjalanan hanya Perjalanan yang tampil */
      const vl=[0,1,2].filter(v=>F.v===''||String(v)==F.v);
      mk('c2','doughnut',vl.map(v=>VN[v]),[{data:vl.map(v=>sm(rows.filter(r=>r.v==v))),backgroundColor:vl.map(v=>col[v]),borderColor:cv('--sf')}],{cutout:'60%',plugins:{tooltip:TT,legend:lgPct('bottom')},onClick:(e,els)=>{
        if(els.length){
          const v=vl[els[0].index];
          openSP(VN[v],r=>r.v==v)
        }
      }
      });
      const DPAL=[...PAL,'#0EA5E9','#84CC16','#F43F5E','#A855F7'];
      const top=(f,n)=>{
        const m={};
        rows.forEach(r=>{
          const k=f(r);
          m[k]=(m[k]||0)+w(r)
        });
        return Object.entries(m).sort((x,y)=>y[1]-x[1]).slice(0,n)
      };
      const clk=(a,f,lab,ttl)=>(e,els)=>{
        if(els.length){
          const k=a[els[0].index][0];
          openSP(ttl+': '+lab(k),r=>String(f(r))==k)
        }
      };
      /* batang horizontal + nilai di ujung batang (rank=true: beri nomor peringkat) */
      const TOT=sm(rows);
      const hb=(id,f,lab,n,cl,ttl,rank,pc)=>{
        const a=top(f,n);
        mk(id,'bar',a.map((x,i)=>(rank?(i+1)+'. ':'')+lab(x[0])),[{data:a.map(x=>x[1]),backgroundColor:cv(cl),borderRadius:4,barPercentage:.72}],{indexAxis:'y',scales:{x:mon,y:{grid:{display:false}}},layout:{padding:{right:pc?96:52}},plugins:{vlabel:{on:1,tot:pc?TOT:0},tooltip:TT},onClick:clk(a,f,lab,ttl)})
      };
      /* batang vertikal berwarna per item */
      const vb=(id,f,lab,n,ttl)=>{
        const a=top(f,n);
        mk(id,'bar',a.map(x=>lab(x[0])),[{data:a.map(x=>x[1]),backgroundColor:a.map((x,i)=>DPAL[i%DPAL.length]),borderRadius:6,barPercentage:.6}],{scales:{x:{grid:{display:false}},y:mon},layout:{padding:{top:20}},plugins:{vlabel:{on:1,tot:TOT},tooltip:TT},onClick:clk(a,f,lab,ttl)})
      };
      /* donat dengan legenda di kanan */
      const dn=(id,f,lab,n,ttl)=>{
        const a=top(f,n);
        mk(id,'doughnut',a.map(x=>lab(x[0])),[{data:a.map(x=>x[1]),backgroundColor:a.map((x,i)=>DPAL[i%DPAL.length]),borderColor:cv('--sf'),borderWidth:2}],{cutout:'62%',plugins:{tooltip:TT,legend:lgPct('right')},onClick:clk(a,f,lab,ttl)})
      };
      hb('c4',r=>r.e,k=>X.em[k][1],10,'--ce','Karyawan',true,true);
      hb('c5',r=>r.bu+'|'+r.un,k=>k.replace('|',' › '),10,'--cd','Unit');
      hb('c10',r=>r.of,k=>k,8,'--cg','Office area');
      vb('c11',r=>r.bu,k=>k,5,'Bisnis unit');
      dn('c6',r=>r.g,k=>X.gr[k],8,'Grup');
      hb('c7',r=>r.y,k=>X.ci[k],8,'--cy','Kota');
      drawSP()
    }

    /* muat HCIS */
    function parseCSV(t){
      const R=[];
      let r=[],f='',q=0;
      for(let i=0;i<t.length;i++){
        const c=t[i];
        if(q){
          if(c=='"'){
            if(t[i+1]=='"'){
              f+='"';
              i++
            }else q=0
          }else f+=c
        }else if(c=='"')q=1;
        else if(c==','){
          r.push(f);
          f=''
        }else if(c=='\n'||c=='\r'){
          if(c=='\r'&&t[i+1]=='\n')i++;
          r.push(f);
          R.push(r);
          r=[];
          f=''
        }else f+=c
      }
      if(f||r.length){
        r.push(f);
        R.push(r)
      }
      return R
    }

    function parseSQL(t){
      const D=['id','employee_id','fullname','email','group_company','office_area','unit','manager_l1_id','manager_l2_id','created_at','updated_at'],out=[],re=/INSERT INTO\s+`?\w+`?\s*(\([^)]*\))?\s*VALUES\s*/gi;
      let m;
      while(m=re.exec(t)){
        const cols=m[1]?m[1].slice(1,-1).split(',').map(x=>x.replace(/[`\s]/g,'')):D;
        let i=re.lastIndex;
        for(;;){
          while(/[\s,]/.test(t[i]))i++;
          if(t[i]!='(')break;
          i++;
          const row={};
          let c=0;
          for(;;){
            while(t[i]==' ')i++;
            let v;
            if(t[i]=="'"){
              i++;
              v='';
              while(i<t.length&&(t[i]!="'"||t[i+1]=="'")){
                if(t[i]=='\\'){
                  v+=t[i+1];
                  i+=2
                }else if(t[i]=="'"){
                  v+="'";
                  i+=2
                }else v+=t[i++]
              }
              i++
            }else{
              let j=i;
              while(t[j]!=','&&t[j]!=')')j++;
              v=t.slice(i,j).trim();
              if(v=='NULL')v=null;
              i=j
            }
            row[cols[c++]]=v;
            if(t[i]==','){
              i++;
              continue
            }
            if(t[i]==')'){
              i++;
              break
            }
          }
          out.push(row)
        }
        re.lastIndex=i
      }
      return out
    }

    async function loadHC(f){
      if(!f)return;
      const t=await f.text();
      let L=[];
      try{
        if(/\.json$/i.test(f.name)){
          const j=JSON.parse(t);
          L=Array.isArray(j)?j:(j.data||[])
        }else if(/INSERT INTO/i.test(t))L=parseSQL(t);
        else{
          const R=parseCSV(t),h=R[0].map(x=>x.trim().toLowerCase().replace(/^\ufeff/,''));
          L=R.slice(1).map(r=>Object.fromEntries(h.map((k,i)=>[k,r[i]])))
        }
      }catch(e){
        alert('Gagal membaca file: '+e.message);
        return
      }
      HC.clear();
      HCE.clear();
      L.forEach(o=>{
        const id=String(o.employee_id||'').trim();
        if(!id)return;
        HC.set(id,o);
        if(o.email)HCE.set(String(o.email).trim().toLowerCase(),o)
      });
      if(!HC.size)alert('Tidak ada baris dengan kolom employee_id.');
      enrich();
      refill();
      render()
    }

    /* UI */
    if(SERVER)$('.sim').remove();
    else $('#role').innerHTML=Object.entries(ROLES).map(([k,v])=>`<option value="${k}">${v.n}</option>`).join('');
    $('#fbox').innerHTML=FL.map(([k,l])=>`<label>${l}<span class="cb"><input id="f${k}" type="text" role="combobox" aria-expanded="false" aria-autocomplete="list" autocomplete="off" placeholder="Semua"><ul class="cl" role="listbox" hidden></ul></span></label>`).join('');
    function setRole(k){
      role=k;
      Object.keys(F).forEach(x=>F[x]='');
      closeSP();
      refill();
      render()
    }

    if(!SERVER)$('#role').onchange=e=>setRole(e.target.value);
    /* Dropdown yang bisa diketik: saran hanya berisi nilai yang memang ada */
    function combo(k){
      const inp=$('#f'+k),ul=inp.nextElementSibling;
      let items=[],hi=-1;
      const lab=()=>F[k]===''?'':((OPT[k].find(x=>x[0]==F[k])||['',''])[1]);
      const close=()=>{
        ul.hidden=true;
        inp.setAttribute('aria-expanded','false');
        hi=-1
      };
      const choose=v=>{
        F[k]=v;
        close();
        refill();
        render()
      };
      const draw=()=>{
        const cap=Math.min(items.length,100);
        ul.innerHTML=items.length?items.slice(0,cap).map((x,i)=>`<li role="option" data-i="${i}" class="${i==hi?'on':''}${String(x[0])==F[k]?' cur':''}">${esc(x[1])}</li>`).join('')+(items.length>cap?`<li class="no">+${items.length-cap} lagi, ketik untuk mempersempit</li>`:''):'<li class="no">Tidak ada yang cocok</li>';
        ul.hidden=false;
        inp.setAttribute('aria-expanded','true');
        const el=ul.children[hi];
        if(el)el.scrollIntoView({block:'nearest'})
      };
      const open=all=>{
        const q=all?'':inp.value.trim().toLowerCase();
        items=q?OPT[k].filter(x=>String(x[1]).toLowerCase().includes(q)):[['','Semua'],...OPT[k]];
        if(!all)hi=items.length?0:-1;
        draw()
      };
      inp.onfocus=()=>{
        inp.select();
        open(true)
      };
      inp.onclick=()=>{
        if(ul.hidden)open(true)
      };
      inp.oninput=()=>open(false);
      inp.onkeydown=e=>{
        if(e.key=='ArrowDown'||e.key=='ArrowUp'){
          e.preventDefault();
          if(ul.hidden)open(true);
          if(!items.length)return;
          hi=e.key=='ArrowDown'?Math.min(Math.min(items.length,100)-1,hi+1):Math.max(0,hi-1);
          draw()
        }else if(e.key=='Enter'){
          e.preventDefault();
          if(!ul.hidden&&items[hi])choose(items[hi][0])
        }else if(e.key=='Escape'){
          close();
          inp.value=lab()
        }
      };
      ul.onmousedown=e=>{
        e.preventDefault();
        const li=e.target.closest('li[data-i]');
        if(li)choose(items[+li.dataset.i][0])
      };
      inp.onblur=()=>{
        if(ul.hidden)return;
        close();
        const t=inp.value.trim().toLowerCase(),m=OPT[k].find(x=>String(x[1]).toLowerCase()==t);
        if(t===''&&F[k]!=='')choose('');
        else if(m&&String(m[0])!=F[k])choose(m[0]);
        else inp.value=lab()
      }
    }
    FL.forEach(([k])=>combo(k));
    $('#rs').onclick=()=>setRole(role);
    $('#m1').onclick=()=>{
      mode='rp';
      $('#m1').classList.add('on');
      $('#m2').classList.remove('on');
      render()
    };
    $('#m2').onclick=()=>{
      mode='trx';
      $('#m2').classList.add('on');
      $('#m1').classList.remove('on');
      render()
    };
    enrich();
    refill();
    /* === Grafik terlipat seperti daftar; klik judul atau tombol untuk membuka (pilihan diingat di browser) === */
    (function(){
      const KEY='fg_hid',boxes=[...document.querySelectorAll('#ov .p')].filter(p=>p.querySelector('.cv canvas'));
      if(!boxes.length)return;
      const idOf=p=>p.querySelector('canvas').id;
      let hid,saved=null;
      try{
        saved=localStorage.getItem(KEY)
      }catch(e){
      }
      try{
        hid=saved===null?new Set(boxes.map(idOf)):new Set(JSON.parse(saved))
      }catch(e){
        hid=new Set(boxes.map(idOf))
      }
      const save=()=>{
        try{
          localStorage.setItem(KEY,JSON.stringify([...hid]))
        }catch(e){
        }
      };
      const all=document.createElement('button');
      all.type='button';
      const label=()=>{
        all.textContent=boxes.every(p=>hid.has(idOf(p)))?'Buka semua grafik':'Lipat semua grafik'
      };
      const apply=p=>{
        const id=idOf(p),h=hid.has(id),b=p.querySelector('.tb');
        p.classList.toggle('hid',h);
        b.textContent=h?'Buka':'Lipat';
        b.setAttribute('aria-expanded',h?'false':'true');
        if(!h){
          if(PEND[id]){
            const a=PEND[id];
            delete PEND[id];
            mk(...a)
          }else if(CH[id])CH[id].resize()
        }
      };
      boxes.forEach(p=>{
        const b=document.createElement('button'),h2=p.querySelector('h2');
        b.type='button';
        b.className='tb';
        p.classList.add('hb');
        p.appendChild(b);
        const flip=()=>{
          const id=idOf(p);
          hid.has(id)?hid.delete(id):hid.add(id);
          save();
          apply(p);
          label()
        };
        b.onclick=flip;
        if(h2)h2.onclick=flip;
        apply(p)
      });
      all.onclick=()=>{
        const every=boxes.every(p=>hid.has(idOf(p)));
        boxes.forEach(p=>every?hid.delete(idOf(p)):hid.add(idOf(p)));
        save();
        boxes.forEach(apply);
        label()
      };
      label();
      $('#rs').insertAdjacentElement('beforebegin',all)
    })();
    render();
    {
      const mx=document.querySelector('.mx');
      if(mx)pager(mx)
    }
@endverbatim
</script>