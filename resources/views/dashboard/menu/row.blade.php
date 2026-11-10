<div class="d-flex align-items-center gap-3 border rounded px-3 py-2 bg-white {{ $item->is_active ? '' : 'opacity-50' }}">
    <span class="menu-handle cursor-move text-gray-500" title="Seret untuk mengurutkan"><i class="fa fa-grip-vertical"></i></span>
    <div class="flex-grow-1" style="min-width: 0">
        <div class="fw-bold text-truncate">{{ $item->label }} @unless ($item->is_active)<span class="badge badge-light ms-1">nonaktif</span>@endunless</div>
        <div class="text-muted fs-8 text-truncate">{{ App\Models\NavMenu::TYPES[$item->type] }}: {{ $item->type === 'page' ? $item->page?->title : ($item->type === 'route' ? (config('mantaaba.menu_routes')[$item->route_name] ?? $item->route_name) : $item->url) }}</div>
    </div>
    @can('cms-update')<a href="{{ route('menu.edit', $item) }}" class="btn btn-sm btn-icon btn-light-success" title="Ubah"><i class="fa fa-pen"></i></a>@endcan
    @can('cms-delete')
        <button type="button" class="btn btn-sm btn-icon btn-light-danger" title="Hapus" data-delete-url="{{ route('menu.destroy', $item) }}"
            @if ($item->children?->isNotEmpty()) data-confirm="Sub-menu di bawahnya ikut terhapus." @endif><i class="fa fa-trash"></i></button>
    @endcan
</div>
