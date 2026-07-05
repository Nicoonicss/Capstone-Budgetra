<span wire:poll.30s>
    @if ($count > 0)
    <span style="background:var(--danger);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;">{{ $count > 99 ? '99+' : $count }}</span>
    @endif
</span>
