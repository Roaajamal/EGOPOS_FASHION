@php
    $egoU = auth()->user();
    $egoBid = $egoU ? ($egoU->business_id ?? session('user.business_id')) : null;
    $egoIsAdmin = false; $egoCan = false;
    try { if ($egoU) { $egoIsAdmin = app(\App\Utils\BusinessUtil::class)->is_admin($egoU); } } catch (\Throwable $e) {}
    try { if ($egoU && ! $egoIsAdmin) { $egoCan = $egoU->can('ego.notification_bell'); } } catch (\Throwable $e) {}
    $egoShowBell = $egoIsAdmin || $egoCan;

    // 🆕 توليد الإشعارات لمن يملك الصلاحية أو الأدمن (قبل جلبها)
    try { if ($egoShowBell && $egoBid) { \App\Utils\EgoNotifier::generate($egoU, $egoBid); } } catch (\Throwable $e) {}

    // 🆕 تُعرض فقط إشعارات آخر 24 ساعة (والأقدم يختفي)
    $all_notifications = $egoU
        ? $egoU->notifications()->where('created_at', '>=', \Carbon\Carbon::now()->subDay())->get()
        : collect();
    $unread_notifications = $all_notifications->whereNull('read_at');
    $total_unread = count($unread_notifications);
@endphp

@if($egoShowBell)
<style>
    .ego-bell-btn{position:relative;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.20);transition:.18s;cursor:pointer}
    .ego-bell-btn:hover{background:rgba(255,255,255,.26);color:#fff}
    .ego-bell-badge{position:absolute;top:-5px;right:-5px;min-width:18px;height:18px;padding:0 4px;border-radius:9px;background:#ef4444;color:#fff;font-size:11px;font-weight:800;display:none;align-items:center;justify-content:center;line-height:18px}
    .ego-bell-badge.show{display:inline-flex}
    .notifications-menu .dropdown-menu#ego_bell_menu{padding:6px !important}
    #ego_bell_menu .menu{list-style:none;margin:0;padding:0}
    #ego_bell_menu .notification-li{display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:9px;font-size:13px;color:#475569}
    #ego_bell_menu .notification-li:hover{background:#f1f5f9}
    #ego_bell_menu .notification-li.unread{background:#f0fdfa}
    #ego_bell_menu .notification-li a{color:inherit;display:flex;align-items:center;gap:8px;width:100%}
    #ego_bell_menu .no-notification{justify-content:center;color:#94a3b8;padding:22px}
</style>
<li class="dropdown notifications-menu tw-list-none">
    <a type="button" class="dropdown-toggle load_notifications ego-bell-btn" data-toggle="dropdown" id="show_unread_notifications" data-loaded="false" title="الإشعارات">
        <span class="tw-sr-only">Notifications</span>
        <svg aria-hidden="true" class="tw-size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
            <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
        </svg>
        <span class="ego-bell-badge notifications_count @if(!empty($total_unread)) show @endif">@if(!empty($total_unread)){{ $total_unread }}@endif</span>
    </a>
    <ul id="ego_bell_menu" class="dropdown-menu !tw-p-2 tw-absolute !tw-right-0 !tw-z-10 !tw-mt-2 !tw-origin-top-right !tw-bg-white !tw-rounded-lg !tw-shadow-lg !tw-ring-1 !tw-ring-gray-200"
        style="left:auto !important;width:340px;max-width:92vw;max-height:70vh;overflow-y:auto">
        <li>
            <ul class="menu" id="notifications_list"></ul>
        </li>
        @if (count($all_notifications) > 10)
            <li class="footer load_more_li" style="text-align:center;padding:8px;border-top:1px solid #f1f5f9">
                <a href="#" class="load_more_notifications" style="color:#0d9488;font-weight:700">@lang('lang_v1.load_more')</a>
            </li>
        @endif
    </ul>
</li>

<input type="hidden" id="notification_page" value="1">
@endif
