<div x-data="{ copied: false }" style="display:grid;gap:12px;min-width:0;">
    <div style="font-size:13px;font-weight:600;color:rgb(107 114 128);">
        {{ \Dth\Marketing\Support\UiText::get('landing.public_url', 'Public URL') }}
    </div>

    <div style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:stretch;min-width:0;">
        <div
            style="min-width:0;border:1px solid rgb(209 213 219);border-radius:10px;background:rgb(249 250 251);padding:10px 12px;font:500 13px/1.55 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;word-break:break-all;overflow-wrap:anywhere;color:rgb(17 24 39);"
        >{{ $url }}</div>

        <button
            type="button"
            style="border:0;border-radius:10px;padding:10px 16px;background:rgb(37 99 235);color:white;font-weight:700;cursor:pointer;white-space:nowrap;"
            x-on:click="navigator.clipboard.writeText(@js($url)).then(() => { copied = true; setTimeout(() => copied = false, 1800) })"
        >
            {{ \Dth\Marketing\Support\UiText::get('landing.copy', 'Copy') }}
        </button>
    </div>

    <div
        x-show="copied"
        x-transition.opacity
        style="font-size:13px;font-weight:600;color:rgb(22 163 74);"
    >
        {{ \Dth\Marketing\Support\UiText::get('landing.copied', 'Copied to clipboard') }}
    </div>
</div>
