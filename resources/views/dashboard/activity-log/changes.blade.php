@foreach ($log->changes['after'] ?? $log->changes['before'] ?? [] as $field => $value)
    <div class="fs-7">
        <span class="fw-bold">{{ $field }}</span>:
        @isset($log->changes['before'][$field], $log->changes['after'])
            <span class="text-muted text-decoration-line-through">{{ Str::limit(json_encode($log->changes['before'][$field]), 40) }}</span> →
        @endisset
        {{ Str::limit(json_encode($value), 60) }}
    </div>
@endforeach
