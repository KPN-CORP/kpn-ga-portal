{{--
    Komponen Bell Notifikasi (mirip ikon notifikasi di toolbar Android).
    Pasang dengan: @include('components.notification-bell')
    Butuh Font Awesome (sudah di-load di dashboard.blade.php).
--}}
<div class="ga-notif-wrapper" id="gaNotifWrapper">
    <button type="button" class="ga-notif-btn" id="gaNotifBtn" aria-label="Notifikasi">
        <i class="fas fa-bell"></i>
        <span class="ga-notif-badge" id="gaNotifBadge" style="display:none;">0</span>
    </button>

    <div class="ga-notif-panel" id="gaNotifPanel">
        <div class="ga-notif-panel-header">
            <span>Notifikasi</span>
            <button type="button" id="gaNotifMarkAll" class="ga-notif-markall">Tandai semua dibaca</button>
        </div>

        <div class="ga-notif-list" id="gaNotifList">
            <div class="ga-notif-empty">Memuat notifikasi...</div>
        </div>

        <a href="{{ route('notifications.index') }}" class="ga-notif-panel-footer">
            Lihat Semua Notifikasi
        </a>
    </div>
</div>

<style>
    .ga-notif-wrapper {
        position: relative;
        display: inline-block;
    }

    .ga-notif-btn {
        position: relative;
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: #fff;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.05rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s ease;
    }

    .ga-notif-btn:hover {
        background: rgba(255, 255, 255, 0.28);
    }

    .ga-notif-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #e74c3c;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        line-height: 18px;
        border-radius: 999px;
        text-align: center;
        padding: 0 4px;
        border: 2px solid var(--primary-color, #2c3e50);
    }

    .ga-notif-panel {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 12px);
        width: 340px;
        max-width: 90vw;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.18);
        overflow: hidden;
        z-index: 1050;
        color: #2c3e50;
    }

    .ga-notif-panel.show {
        display: block;
        animation: gaNotifFadeIn 0.15s ease-out;
    }

    @keyframes gaNotifFadeIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .ga-notif-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        font-weight: 700;
        border-bottom: 1px solid #eef1f5;
    }

    .ga-notif-markall {
        background: none;
        border: none;
        color: #3498db;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
    }

    .ga-notif-markall:hover {
        text-decoration: underline;
    }

    .ga-notif-list {
        max-height: 380px;
        overflow-y: auto;
    }

    .ga-notif-empty {
        padding: 28px 16px;
        text-align: center;
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .ga-notif-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 16px;
        border-bottom: 1px solid #f4f6f8;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        transition: background 0.15s ease;
    }

    .ga-notif-item:hover {
        background: #f8fafc;
    }

    .ga-notif-item.unread {
        background: #eef6ff;
    }

    .ga-notif-item.unread:hover {
        background: #e3f0fd;
    }

    .ga-notif-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .ga-notif-body {
        flex: 1;
        min-width: 0;
    }

    .ga-notif-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        color: #64748b;
        margin-bottom: 2px;
    }

    .ga-notif-message {
        font-size: 0.85rem;
        line-height: 1.35;
        color: #334155;
        word-break: break-word;
    }

    .ga-notif-time {
        font-size: 0.72rem;
        color: #94a3b8;
        margin-top: 4px;
    }

    .ga-notif-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #3498db;
        margin-top: 6px;
        flex-shrink: 0;
    }

    .ga-notif-panel-footer {
        display: block;
        text-align: center;
        padding: 12px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #3498db;
        text-decoration: none;
        border-top: 1px solid #eef1f5;
        background: #fafbfc;
    }

    .ga-notif-panel-footer:hover {
        background: #f0f4f8;
        color: #2c81ba;
    }
</style>

<script>
(function () {
    const wrapper   = document.getElementById('gaNotifWrapper');
    const btn       = document.getElementById('gaNotifBtn');
    const panel     = document.getElementById('gaNotifPanel');
    const list      = document.getElementById('gaNotifList');
    const badge     = document.getElementById('gaNotifBadge');
    const markAllBtn = document.getElementById('gaNotifMarkAll');

    const FETCH_URL    = "{{ route('notifications.fetch') }}";
    const READ_URL     = "{{ url('notifications') }}"; // + /{id}/read
    const READ_ALL_URL = "{{ route('notifications.read-all') }}";
    const CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let pollTimer = null;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.innerText = str ?? '';
        return div.innerHTML;
    }

    function renderNotifications(data) {
        if (!data.notifications || data.notifications.length === 0) {
            list.innerHTML = '<div class="ga-notif-empty">Belum ada notifikasi.</div>';
            return;
        }

        list.innerHTML = data.notifications.map(function (n) {
            return `
                <a href="javascript:void(0)" class="ga-notif-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}" data-url="${escapeHtml(n.url)}">
                    <div class="ga-notif-icon" style="background:${n.color}">
                        <i class="fas ${n.icon}"></i>
                    </div>
                    <div class="ga-notif-body">
                        <div class="ga-notif-label">${escapeHtml(n.label)}</div>
                        <div class="ga-notif-message">${escapeHtml(n.message)}</div>
                        <div class="ga-notif-time">${escapeHtml(n.time_ago ?? '')}</div>
                    </div>
                    ${n.is_read ? '' : '<span class="ga-notif-dot"></span>'}
                </a>
            `;
        }).join('');
    }

    function updateBadge(count) {
        if (count > 0) {
            badge.style.display = 'inline-block';
            badge.textContent = count > 99 ? '99+' : count;
        } else {
            badge.style.display = 'none';
        }
    }

    function loadNotifications() {
        fetch(FETCH_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) return;
                updateBadge(data.unread_count);
                renderNotifications(data);
            })
            .catch(function () {
                list.innerHTML = '<div class="ga-notif-empty">Gagal memuat notifikasi.</div>';
            });
    }

    function togglePanel(show) {
        const willShow = show ?? !panel.classList.contains('show');
        panel.classList.toggle('show', willShow);
        if (willShow) {
            loadNotifications();
        }
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        togglePanel();
    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            togglePanel(false);
        }
    });

    list.addEventListener('click', function (e) {
        const item = e.target.closest('.ga-notif-item');
        if (!item) return;

        const id  = item.getAttribute('data-id');
        const url = item.getAttribute('data-url');

        fetch(`${READ_URL}/${id}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
        })
        .finally(function () {
            if (url) window.location.href = url;
        });
    });

    markAllBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        fetch(READ_ALL_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
        })
        .then(function () { loadNotifications(); })
        .catch(function () {});
    });

    // Muat sekali di awal supaya badge langsung terisi, lalu polling berkala
    // (mirip status bar Android yang auto-update jumlah notifikasi baru).
    loadNotifications();
    pollTimer = setInterval(loadNotifications, 30000);
})();
</script>
