<style>
@verbatim
    #efg {
      --bg:#EEF2F6;
      --sf:#fff;
      --ink:#13263F;
      --mu:#64748B;
      --ln:#E2E8F0;
      --a1:#2A9D8F;
      --a2:#E9A23B;
      --a3:#3D5A80;
      --a4:#D64545;
      --on:#fff;
      --cf:#1F3A5F;
      --ct:#2A9D8F;
      --cx:#E9A23B;
      --c0:#3D5A80;
      --c1:#7C93B0;
      --ce:#1F3A5F;
      --cd:#3D5A80;
      --cg:#2A9D8F;
      --cy:#5B7083;
      --ch:#9CA3AF;
      --cm:#D64545;
      --ca:#1F2937;
      --g:#13263F;
      --gon:#fff;
      color-scheme:light;
      box-sizing:border-box;
      padding-top:env(safe-area-inset-top,0px);
      padding-bottom:env(safe-area-inset-bottom,0px);
    }
    #efg {
      scroll-padding-top:env(safe-area-inset-top,0px);
    }
    #efg * {
      box-sizing:border-box;
    }
    #efg {
      margin:0;
      background:var(--bg);
      color:var(--ink);
      font:14px/1.45 'IBM Plex Sans',system-ui,sans-serif;
    }
    #efg .w0 {
      max-width:1280px;
      margin:0 auto;
      padding:14px clamp(12px,3vw,24px) 32px;
      display:grid;
      gap:14px;
    }
    #efg .sim {
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      align-items:center;
      padding:10px 12px;
      border:1px dashed var(--mu);
      border-radius:6px;
      background:var(--sf);
    }
    #efg .sim span {
      color:var(--mu);
      font-size:12px;
      flex:1 1 260px;
    }
    #efg h1 {
      font-size:20px;
      font-weight:600;
      margin:0;
    }
    #efg .hd {
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      align-items:end;
      justify-content:space-between;
      background:linear-gradient(90deg,#13263F,#2F5D8A);
      color:var(--gon);
      padding:20px;
      border-radius:15px;
      box-shadow:0 8px 20px rgba(0,0,0,.12);
    }
    #efg .hd h1 {
      color:var(--gon);
      font-size:22px;
    }
    #efg .hd .mu {
      color:var(--gon);
      opacity:.9;
    }
    #efg .bd {
      display:inline-block;
      color:var(--ink);
      background:var(--sf);
      padding:2px 9px;
      border-radius:99px;
      font-size:12px;
      font-weight:600;
      border:1px solid var(--ln);
      margin-right:6px;
    }
    #efg .mu {
      color:var(--mu);
      font-size:12px;
    }
    #efg .p {
      background:var(--sf);
      border:1px solid var(--ln);
      border-radius:10px;
      padding:14px;
      min-width:0;
    }
    #efg .p h2 {
      font-size:13px;
      font-weight:600;
      margin:0 0 8px;
    }
    #efg .fb {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
      gap:10px;
    }
    #efg label {
      display:grid;
      gap:3px;
      font-size:12px;
      color:var(--mu);
    }
    #efg select, #efg button {
      font:inherit;
      color:var(--ink);
      background:var(--sf);
      border:1px solid var(--ln);
      border-radius:6px;
      padding:6px 8px;
      min-width:0;
    }
    #efg select:disabled {
      background:var(--bg);
      opacity:.8;
    }
    #efg button {
      cursor:pointer;
    }
    #efg :focus-visible {
      outline:2px solid var(--ink);
      outline-offset:1px;
    }
    #efg .tg button {
      border-radius:0;
    }
    #efg .tg button:first-child {
      border-radius:6px 0 0 6px;
    }
    #efg .tg button:last-child {
      border-radius:0 6px 6px 0;
      border-left:0;
    }
    #efg .on {
      background:var(--g)!important;
      color:var(--gon)!important;
      border-color:var(--g)!important;
    }
    #efg .hd .on {
      background:#fff!important;
      color:#13263F!important;
      border-color:#fff!important;
    }
    #efg .ch {
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      align-items:center;
    }
    #efg .ch i {
      font-style:normal;
      background:var(--bg);
      border:1px solid var(--ln);
      border-radius:99px;
      padding:1px 4px 1px 9px;
      font-size:12px;
    }
    #efg .ch i b {
      cursor:pointer;
      margin-left:4px;
      padding:0 5px;
      font-weight:400;
    }
    #efg .kp {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
      gap:10px;
    }
    #efg .k {
      text-align:left;
      border-left:4px solid var(--c);
      padding:12px 14px;
    }
    #efg .k b {
      display:block;
      font-size:21px;
      font-weight:600;
      font-variant-numeric:tabular-nums;
      margin-top:2px;
    }
    #efg .k.sel {
      outline:2px solid var(--c);
    }
    #efg .g {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(min(100%,380px),1fr));
      gap:14px;
    }
    #efg .cv {
      position:relative;
      zoom:1.25;
      height:200px;
    }
    #efg .mtn {
      display:grid;
      gap:4px;
      font-size:13px;
      margin:0 0 8px;
    }
    #efg .mtn .up {
      color:var(--cm);
    }
    #efg .mtn .dn {
      color:#16A34A;
    }
    #efg details.p > summary {
      cursor:pointer;
      font-weight:700;
      font-size:13px;
      list-style:none;
    }
    #efg details.p > summary::-webkit-details-marker {
      display:none;
    }
    #efg details.p > summary::before {
      content:'▸ ';
    }
    #efg details.p[open] > summary::before {
      content:'▾ ';
    }
    #efg details.p[open] > summary {
      margin-bottom:8px;
    }
    #efg tr[hidden] {
      display:none;
    }
    #efg .pgn {
      display:flex;
      gap:10px;
      align-items:center;
      justify-content:space-between;
      flex-wrap:wrap;
      margin-top:10px;
      font-size:12px;
      position:sticky;
      left:0;
    }
    #efg .pgn button {
      font-size:12px;
      padding:4px 10px;
    }
    #efg .pgn button:disabled {
      opacity:.45;
      cursor:default;
    }
    #efg .sq {
      width:100%;
      margin:0 0 10px;
      padding:8px 10px;
      border:1px solid var(--ln);
      border-radius:8px;
      font-size:13px;
    }
    #efg .cb {
      position:relative;
      display:block;
    }
    #efg .cb::after {
      content:'▾';
      position:absolute;
      right:9px;
      top:50%;
      transform:translateY(-50%);
      pointer-events:none;
      color:var(--mu);
      font-size:11px;
    }
    #efg .cb input {
      width:100%;
      font:inherit;
      color:var(--ink);
      background:var(--sf);
      border:1px solid var(--ln);
      border-radius:6px;
      padding:6px 24px 6px 8px;
      min-width:0;
    }
    #efg .cl {
      position:absolute;
      left:0;
      right:0;
      top:calc(100% + 2px);
      z-index:15;
      margin:0;
      padding:4px;
      list-style:none;
      background:var(--sf);
      border:1px solid var(--ln);
      border-radius:8px;
      box-shadow:0 8px 24px #0002;
      max-height:260px;
      overflow:auto;
      color:var(--ink);
    }
    #efg .cl[hidden] {
      display:none;
    }
    #efg .cl li {
      padding:6px 8px;
      border-radius:6px;
      cursor:pointer;
      font-size:13px;
    }
    #efg .cl li.on, #efg .cl li:hover {
      background:var(--bg);
    }
    #efg .cl li.cur {
      font-weight:700;
    }
    #efg .cl li.no {
      color:var(--mu);
      cursor:default;
    }
    #efg .fx {
      display:flex;
      gap:6px;
      align-items:center;
      flex-wrap:wrap;
    }
    #efg .fx input {
      width:130px;
      padding:4px 8px;
      border:1px solid var(--ln, #d1d5db);
      border-radius:6px;
      font-size:12px;
    }
    #efg .fx button {
      font-size:12px;
      padding:4px 10px;
    }
    #efg .fx .fm {
      font-size:12px;
      color:var(--cm);
    }
    #efg .p.hb {
      position:relative;
    }
    #efg .p.hb h2 {
      padding-right:84px;
      cursor:pointer;
    }
    #efg .tb {
      position:absolute;
      top:10px;
      right:10px;
      font-size:12px;
      line-height:1.4;
      padding:2px 10px;
    }
    #efg .p.hid {
      grid-column:1/-1;
      padding:10px 14px;
    }
    #efg .p.hid h2 {
      margin:0;
      padding-top:2px;
      padding-bottom:2px;
    }
    #efg .p.hid .tb {
      top:50%;
      transform:translateY(-50%);
    }
    #efg .p.hid:hover {
      background:var(--bg);
    }
    #efg .p.hid .cv {
      display:none;
    }
    #efg .w {
      overflow-x:auto;
    }
    #efg table {
      border-collapse:collapse;
      width:100%;
      font-variant-numeric:tabular-nums;
    }
    #efg th, #efg td {
      padding:6px 8px;
      border-bottom:1px solid var(--ln);
      white-space:nowrap;
      text-align:left;
    }
    #efg th {
      color:var(--mu);
      font-weight:500;
    }
    #efg .r {
      text-align:right;
    }
    #efg [hidden] {
      display:none!important;
    }
    #efg #sp {
      position:fixed;
      top:0;
      right:0;
      bottom:0;
      width:min(520px,100vw);
      background:var(--sf);
      border-left:1px solid var(--ln);
      box-shadow:-6px 0 24px #0003;
      padding:calc(14px + env(safe-area-inset-top,0px)) 14px 14px;
      z-index:20;
      overflow:auto;
      overscroll-behavior:contain;
    }
    #efg .sh {
      display:flex;
      justify-content:space-between;
      gap:8px;
      align-items:start;
      margin-bottom:10px;
    }
    #efg .sh b {
      font-size:15px;
    }
    #efg .sh button {
      font-size:20px;
      line-height:1;
      padding:2px 9px;
    }
    #efg .ss {
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:8px;
      margin-bottom:10px;
    }
    #efg .ss div {
      border:1px solid var(--ln);
      border-radius:8px;
      padding:8px 10px;
    }
    #efg .ss b {
      display:block;
      font-size:16px;
    }
    @media(min-width:1100px) {
      #efg.so .w0 {
        margin-right:540px;
      }
    }
    #efg td .mu {
      white-space:normal;
    }
    #efg .ad {
      cursor:pointer;
      white-space:normal;
    }
    #efg .dr {
      cursor:pointer;
    }
    #efg .dr:hover {
      background:var(--bg);
    }
    #efg dialog {
      width:min(580px,94vw);
      border:1px solid var(--ln);
      border-radius:12px;
      background:var(--sf);
      color:var(--ink);
      padding:16px;
    }
    #efg dialog::backdrop {
      background:#0006;
    }
    #efg dialog td {
      white-space:normal;
      word-break:break-word;
      vertical-align:top;
    }
    #efg dialog td:first-child {
      width:120px;
    }
    #efg { background: transparent; }
    #efg .w0 { padding: 0; max-width: none; }
    #efg dialog::backdrop { background: #0006; }
    #efg .g2,#efg .gh,#efg .g3 {
      display:grid;
      gap:14px;
    }
    #efg .g2 {
      grid-template-columns:minmax(0,2fr) minmax(0,1fr);
    }
    #efg .gh {
      grid-template-columns:repeat(2,minmax(0,1fr));
    }
    #efg .g3 {
      grid-template-columns:repeat(3,minmax(0,1fr));
    }
    @media(max-width:1100px) {
      #efg .g3 {
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
    }
    @media(max-width:820px) {
      #efg .g2, #efg .gh, #efg .g3 {
        grid-template-columns:minmax(0,1fr);
      }
    }
    #efg .pr {
      font-weight:500;
      color:var(--mu);
    }
@endverbatim
</style>