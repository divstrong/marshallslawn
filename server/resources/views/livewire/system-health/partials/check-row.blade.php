<div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid #f3f4f6; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
        @if($check['passed'])
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 999px; background: #d1fae5; color: #065f46; font-size: 13px; font-weight: 700; flex-shrink: 0;">&#10003;</span>
        @else
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 999px; background: #fee2e2; color: #991b1b; font-size: 13px; font-weight: 700; flex-shrink: 0;">&#10005;</span>
        @endif
        <span style="font-size: 13px; font-weight: 600; color: #111827; white-space: nowrap;">{{ $check['name'] }}</span>
        <span style="font-size: 12px; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $check['message'] ?? '' }}</span>
    </div>
    <span style="font-size: 11px; color: #9ca3af; flex-shrink: 0;">{{ $check['duration_ms'] ?? 0 }}ms</span>
</div>
