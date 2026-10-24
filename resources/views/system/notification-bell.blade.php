<?php $unreadNotifications = auth()->user()->unreadNotifications()->count(); ?>
<button type="button" class="system-notification-button" data-notification-toggle aria-expanded="false" aria-controls="system-notifications" aria-label="Notificações{{ $unreadNotifications ? ', '.$unreadNotifications.' não visualizadas' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
    <span class="system-notification-dot" data-notification-dot @if(!$unreadNotifications) hidden @endif aria-hidden="true"></span>
</button>
