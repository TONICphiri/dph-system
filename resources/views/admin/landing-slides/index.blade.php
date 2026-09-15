<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">National administration</p>
            <h2 class="text-2xl font-extrabold leading-tight">Landing page slideshow</h2>
            <p class="text-sm text-dhp-100">One image shows at a time on the public landing page. Only the main (national) admin manages these — not the facility admin.</p>
        </div>
    </x-slot>

    <section class="dhp-card dhp-card-pad mb-6 max-w-2xl">
        <h3 class="dhp-section-title">Rotation interval</h3>
        <p class="dhp-section-sub">Seconds each image stays on screen before the next appears.</p>
        <form method="POST" action="{{ route('admin.landing-slides.interval') }}" class="mt-3 flex max-w-sm items-end gap-2">
            @csrf
            <div class="flex-1">
                <label class="dhp-label" for="landing_slide_interval">Seconds (2–60)</label>
                <input id="landing_slide_interval" type="number" name="landing_slide_interval" min="2" max="60" value="{{ $interval }}" required class="dhp-input" />
            </div>
            <button class="btn-primary">Save</button>
        </form>
    </section>

    <section class="dhp-card dhp-card-pad mb-6 max-w-2xl">
        <h3 class="dhp-section-title">Add slide image</h3>
        <p class="dhp-section-sub">JPG, PNG, SVG or WebP, max 2 MB.</p>
        <form method="POST" action="{{ route('admin.landing-slides.store') }}" enctype="multipart/form-data" class="mt-3 grid gap-3">
            @csrf
            <div>
                <label class="dhp-label" for="image">Image file</label>
                <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.svg,.webp" required class="dhp-input" />
                @error('image')<p class="dhp-field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="dhp-label" for="caption">Caption (optional)</label>
                <input id="caption" type="text" name="caption" maxlength="255" class="dhp-input" placeholder="Short description of the image" />
            </div>
            <div><button class="btn-primary">Upload slide</button></div>
        </form>
    </section>

    <section class="dhp-card overflow-hidden">
        <div class="dhp-card-pad border-b border-[#E7F0F0]">
            <h3 class="dhp-section-title">Current slides</h3>
            <p class="dhp-section-sub">Untick "Show" to hide a slide without deleting it. Lower order shows first.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="dhp-table">
                <thead><tr><th>Image</th><th>Caption</th><th>Order</th><th>Show</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($slides as $slide)
                        <tr>
                            <td><img src="{{ $slide->image_url }}" alt="{{ $slide->caption ?? 'Slide' }}" style="width:120px;height:auto;border:1px solid #ccc"></td>
                            <td class="text-sm">{{ $slide->caption ?? '—' }}</td>
                            <td colspan="3">
                                <form method="POST" action="{{ route('admin.landing-slides.update', $slide) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="caption" value="{{ $slide->caption }}" maxlength="255" class="dhp-input !w-40" placeholder="Caption" />
                                    <input type="number" name="sort_order" value="{{ $slide->sort_order }}" min="0" max="1000" class="dhp-input !w-20" title="Order" />
                                    <label class="text-sm"><input type="checkbox" name="is_active" value="1" {{ $slide->is_active ? 'checked' : '' }} class="dhp-check" /> Show</label>
                                    <button class="btn-secondary !min-h-[40px] !px-3 !py-1.5 !text-xs">Save</button>
                                </form>
                                <form method="POST" action="{{ route('admin.landing-slides.destroy', $slide) }}" class="mt-2" onsubmit="return confirm('Delete this slide image?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="font-bold text-rose-700 hover:underline text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-slate-500">No custom slides yet — the two default illustrations show on the landing page until you upload your own.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
