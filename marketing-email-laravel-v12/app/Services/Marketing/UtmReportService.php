<?php

namespace App\Services\Marketing;

use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\LandingPageView;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UtmReportService
{
    public function render(?int $landingPageId): HtmlString
    {
        if (! $landingPageId) {
            return new HtmlString('');
        }

        $stats = LandingPageView::query()
            ->selectRaw('COALESCE(utm_source, \'direct\') as source, COUNT(*) as views')
            ->where('landing_page_id', $landingPageId)
            ->groupBy('source')
            ->orderByDesc('views')
            ->get()
            ->keyBy('source');
        $submissions = LandingPageSubmission::query()
            ->selectRaw('COALESCE(utm_source, \'direct\') as source, COUNT(*) as submissions')
            ->where('landing_page_id', $landingPageId)
            ->groupBy('source')
            ->orderByDesc('submissions')
            ->get()
            ->keyBy('source');
        $allSources = collect(array_unique(array_merge($stats->keys()->all(), $submissions->keys()->all())))->sort();
        if ($allSources->isEmpty()) {
            return new HtmlString(
                '<div style="padding:12px;background:#fef3cd;border:1px solid #fbbf24;border-radius:6px;color:#92400e;font-size:14px">'
                . e(__('field.report_no_data'))
                . '</div>'
            );
        }

        $colors = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#f97316', '#64748b'];
        $sources = $allSources->values()->all();
        $totalViews = (int) $stats->sum('views');
        $totalSubs = (int) $submissions->sum('submissions');
        $maxBar = max($totalViews, $totalSubs, 1);

        $pieStops = '';
        $pieLegend = '';
        $barCells = '';
        $cum = 0.0;
        foreach ($sources as $i => $source) {
            $views = (int) ($stats->get($source)?->views ?? 0);
            $subs = (int) ($submissions->get($source)?->submissions ?? 0);
            $color = $colors[$i % count($colors)];
            $sourceLabel = $source === 'direct' ? __('field.report_direct') : $source;
            if ($totalViews > 0 && $views > 0) {
                $start = $cum;
                $cum += ($views / $totalViews) * 100;
                $pieStops .= $color . ' ' . round($start, 2) . '% ' . round($cum, 2) . '%, ';
            }
            $pct = $totalViews > 0 ? round(($views / $totalViews) * 100, 1) : 0;
            $pieLegend .= '<div class="utm-tip" style="display:flex;align-items:center;gap:8px;font-size:13px;color:#111827;white-space:nowrap;cursor:default">'
                . '<span style="width:12px;height:12px;border-radius:3px;background:' . $color . ';flex-shrink:0"></span>'
                . e($sourceLabel) . ' <b>' . $views . '</b>'
                . '<div class="utm-tip-box">' . e($sourceLabel) . ': ' . $views . ' ' . e(__('field.report_views'))
                . ($totalViews > 0 && $views > 0 ? ' · ' . $pct . '%' : '')
                . '</div></div>';
            $vH = (int) max(4, round(($views / $maxBar) * 130));
            $sH = (int) max(4, round(($subs / $maxBar) * 130));
            $barCells .= '<div style="flex:1;min-width:80px;text-align:center" class="utm-tip">'
                . '<div class="utm-tip-box">' . e($sourceLabel) . ' — ' . e(__('field.report_views')) . ': ' . $views . ' · ' . e(__('field.report_submissions')) . ': ' . $subs . '</div>'
                . '<div style="display:flex;align-items:flex-end;justify-content:center;gap:4px;height:150px">'
                . '<div style="width:22px;background:#6366f1;border-radius:4px 4px 0 0;height:' . $vH . 'px;position:relative">'
                . '<span style="position:absolute;top:-18px;left:50%;transform:translateX(-50%);font-size:11px;font-weight:700;color:' . ($views > 0 ? '#4f46e5' : '#9ca3af') . '">' . $views . '</span></div>'
                . '<div style="width:22px;background:#10b981;border-radius:4px 4px 0 0;height:' . $sH . 'px;position:relative">'
                . '<span style="position:absolute;top:-18px;left:50%;transform:translateX(-50%);font-size:11px;font-weight:700;color:' . ($subs > 0 ? '#047857' : '#9ca3af') . '">' . $subs . '</span></div>'
                . '</div>'
                . '<div style="font-size:12px;color:#374151;font-weight:600;margin-top:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' . e($sourceLabel) . '</div>'
                . '</div>';
        }

        $pieChart = $totalViews > 0
            ? '<div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">'
                . '<div style="position:relative;width:168px;height:168px;border-radius:50%;background:conic-gradient(' . rtrim($pieStops, ' ,') . ');flex-shrink:0">'
                . '<div style="position:absolute;inset:38px;background:#ffffff;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center">'
                . '<div style="font-size:22px;font-weight:700;color:#111827">' . $totalViews . '</div>'
                . '<div style="font-size:11px;color:#6b7280">' . e(__('field.report_views')) . '</div>'
                . '</div></div>'
                . '<div style="display:grid;gap:7px">' . $pieLegend . '</div>'
                . '</div>'
            : '<div style="color:#6b7280;font-size:13px">' . e(__('field.report_no_data')) . '</div>';

        $days = 30;
        $from = now()->subDays($days - 1)->startOfDay();
        $viewsByDay = LandingPageView::query()
            ->where('landing_page_id', $landingPageId)
            ->where('viewed_at', '>=', $from)
            ->selectRaw('DATE(viewed_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');
        $subsByDay = LandingPageSubmission::query()
            ->where('landing_page_id', $landingPageId)
            ->where('submitted_at', '>=', $from)
            ->selectRaw('DATE(submitted_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');
        $maxY = max((int) ($viewsByDay->max() ?? 0), (int) ($subsByDay->max() ?? 0), 1);
        $w = 640; $h = 210; $pl = 36; $pr = 12; $pt = 16; $pb = 28;
        $plotW = $w - $pl - $pr;
        $plotH = $h - $pt - $pb;
        $vPts = [];
        $sPts = [];
        $vDots = '';
        $sDots = '';
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->toDateString();
            $x = $pl + ($i * $plotW / ($days - 1));
            $v = (int) ($viewsByDay[$key] ?? 0);
            $s = (int) ($subsByDay[$key] ?? 0);
            $yv = $pt + $plotH - (($v / $maxY) * $plotH);
            $ys = $pt + $plotH - (($s / $maxY) * $plotH);
            $vPts[] = round($x, 1) . ',' . round($yv, 1);
            $sPts[] = round($x, 1) . ',' . round($ys, 1);
            if ($v > 0) {
                $vDots .= '<g data-tip="' . e($day->format('d/m/Y') . ' — ' . __('field.report_views') . ': ' . $v) . '">'
                    . '<circle cx="' . round($x, 1) . '" cy="' . round($yv, 1) . '" r="4.5" fill="#6366f1" stroke="#ffffff" stroke-width="1.5"/>'
                    . '<text x="' . round($x, 1) . '" y="' . round($yv - 8, 1) . '" font-size="10" font-weight="600" fill="#4f46e5" text-anchor="middle">' . $v . '</text>'
                    . '</g>';
            }
            if ($s > 0) {
                $sDots .= '<g data-tip="' . e($day->format('d/m/Y') . ' — ' . __('field.report_submissions') . ': ' . $s) . '">'
                    . '<circle cx="' . round($x, 1) . '" cy="' . round($ys, 1) . '" r="4.5" fill="#10b981" stroke="#ffffff" stroke-width="1.5"/>'
                    . '<text x="' . round($x, 1) . '" y="' . round($ys - 8, 1) . '" font-size="10" font-weight="600" fill="#047857" text-anchor="middle">' . $s . '</text>'
                    . '</g>';
            }
        }
        $grid = '';
        for ($g = 0; $g <= 4; $g++) {
            $gy = $pt + ($plotH * $g / 4);
            $val = $maxY - (int) round($maxY * $g / 4);
            $grid .= '<line x1="' . $pl . '" y1="' . round($gy, 1) . '" x2="' . ($w - $pr) . '" y2="' . round($gy, 1) . '" stroke="#e5e7eb" stroke-width="1"/>'
                . '<text x="' . ($pl - 6) . '" y="' . round($gy + 4, 1) . '" text-anchor="end" font-size="10" fill="#9ca3af">' . $val . '</text>';
        }
        $xLabels = '';
        for ($i = 0; $i < $days; $i += 5) {
            $x = $pl + ($i * $plotW / ($days - 1));
            $day = $from->copy()->addDays($i);
            $xLabels .= '<text x="' . round($x, 1) . '" y="' . ($h - 8) . '" text-anchor="middle" font-size="10" fill="#9ca3af">' . $day->format('d/m') . '</text>';
        }
        $lineChart = '<div style="display:flex;gap:14px;margin-bottom:10px;font-size:11px;color:#6b7280">'
            . '<span><span style="display:inline-block;width:14px;height:3px;background:#6366f1;border-radius:2px;margin-right:4px;vertical-align:middle"></span>' . e(__('field.report_views')) . '</span>'
            . '<span><span style="display:inline-block;width:14px;height:3px;background:#10b981;border-radius:2px;margin-right:4px;vertical-align:middle"></span>' . e(__('field.report_submissions')) . '</span>'
            . '</div>'
            . '<svg viewBox="0 0 ' . $w . ' ' . $h . '" style="width:100%;max-width:640px;height:auto">'
            . $grid
            . '<polyline points="' . implode(' ', $vPts) . '" fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" data-tip="' . e(__('field.report_views')) . '"/>'
            . $vDots
            . '<polyline points="' . implode(' ', $sPts) . '" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" data-tip="' . e(__('field.report_submissions')) . '"/>'
            . $sDots
            . $xLabels
            . '</svg>';

        $rows = '';
        foreach ($sources as $i => $source) {
            $views = (int) ($stats->get($source)?->views ?? 0);
            $subs = (int) ($submissions->get($source)?->submissions ?? 0);
            $rate = $views > 0 ? round(($subs / $views) * 100, 1) : 0;
            $sourceLabel = $source === 'direct' ? __('field.report_direct') : $source;
            $color = $colors[$i % count($colors)];
            $rows .= '<tr class="' . ($i % 2 ? 'utm-row-b' : 'utm-row-a') . '">'
                . '<td style="padding:10px 14px"><span style="display:inline-flex;align-items:center;gap:8px;font-weight:600;color:#111827"><span style="width:10px;height:10px;border-radius:3px;background:' . $color . ';display:inline-block;flex-shrink:0"></span>' . e($sourceLabel) . '</span></td>'
                . '<td class="center" style="padding:10px 14px;font-weight:700;color:#111827">' . $views . '</td>'
                . '<td class="center" style="padding:10px 14px;font-weight:700;color:#111827">' . $subs . '</td>'
                . '<td class="center" style="padding:10px 14px;font-weight:700;color:' . ($rate > 0 ? '#047857' : '#9ca3af') . '">' . $rate . '%</td>'
                . '</tr>';
        }

        return new HtmlString(
            '<style>
.utm-tip{position:relative}
.utm-tip-box{position:absolute;bottom:calc(100% + 10px);left:50%;transform:translateX(-50%);background:#111827;color:#ffffff;font-size:12px;font-weight:600;line-height:1.5;padding:6px 10px;border-radius:6px;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity .15s ease;z-index:50;box-shadow:0 4px 12px rgba(0,0,0,.18)}
.utm-tip:hover .utm-tip-box{opacity:1}
.utm-tip-box::after{content:"";position:absolute;top:100%;left:50%;transform:translateX(-50%);border:5px solid transparent;border-top-color:#111827}
.utm-table{width:100%;font-size:14px;border:1px solid #d1d5db;border-radius:10px;overflow:hidden;margin-bottom:16px}
.utm-table thead th{background:#111827;color:#ffffff;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;padding:11px 14px;text-align:left;border-bottom:2px solid #111827}
.utm-table th.center,.utm-table td.center{text-align:center}
.utm-table tbody td{border-bottom:1px solid #e5e7eb;padding:10px 14px}
.utm-table tbody tr:last-child td{border-bottom:0}
.utm-row-a{background:#ffffff}
.utm-row-b{background:#f3f4f6}
.utm-table tbody tr:hover{background:#e0e7ff}
</style>'
            . '<table class="utm-table">'
            . '<thead><tr>'
            . '<th>' . e(__('field.report_source')) . '</th>'
            . '<th class="center">' . e(__('field.report_views')) . '</th>'
            . '<th class="center">' . e(__('field.report_submissions')) . '</th>'
            . '<th class="center">' . e(__('field.report_conversion_rate')) . '</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">'
            . '<div style="flex:1.1;min-width:290px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:14px">'
            . '<div style="font-weight:600;font-size:13px;color:#111827;margin-bottom:12px">' . e(__('chart.utm_pie')) . '</div>'
            . $pieChart . '</div>'
            . '<div style="flex:1.2;min-width:320px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:14px">'
            . '<div style="font-weight:600;font-size:13px;color:#111827;margin-bottom:12px">' . e(__('chart.utm_bar')) . '</div>'
            . '<div style="display:flex;gap:14px;margin-bottom:18px;font-size:11px;color:#6b7280">'
            . '<span><span style="display:inline-block;width:10px;height:10px;background:#6366f1;border-radius:2px;margin-right:4px"></span>' . e(__('field.report_views')) . '</span>'
            . '<span><span style="display:inline-block;width:10px;height:10px;background:#10b981;border-radius:2px;margin-right:4px"></span>' . e(__('field.report_submissions')) . '</span>'
            . '</div>'
            . '<div style="display:flex;align-items:flex-end;gap:6px;border-bottom:1px solid #e5e7eb">' . $barCells . '</div>'
            . '</div>'
            . '</div>'
            . '<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin-bottom:16px">'
            . '<div style="font-weight:600;font-size:13px;color:#111827;margin-bottom:12px">' . e(__('chart.utm_line')) . '</div>'
            . $lineChart . '</div>'
            . '<script>
(function () {
    if (window.__utmChartTip) { return; }
    window.__utmChartTip = true;
    var tip = null;
    document.addEventListener("mousemove", function (e) {
        var t = e.target && e.target.closest ? e.target.closest("[data-tip]") : null;
        if (!tip) {
            tip = document.createElement("div");
            tip.style.cssText = "position:fixed;background:#111827;color:#ffffff;font-size:12px;font-weight:600;padding:6px 10px;border-radius:6px;pointer-events:none;z-index:99999;display:none;white-space:nowrap;box-shadow:0 4px 12px rgba(0,0,0,.18)";
            document.body.appendChild(tip);
        }
        if (t) {
            tip.textContent = t.getAttribute("data-tip");
            tip.style.display = "block";
            tip.style.left = (e.clientX + 12) + "px";
            tip.style.top = (e.clientY + 12) + "px";
        } else if (tip.style.display !== "none") {
            tip.style.display = "none";
        }
    });
})();
</script>'
        );
    }

    public function exportCsv(int $landingPageId): StreamedResponse
    {
        $landingPage = LandingPage::findOrFail($landingPageId);
        $filename = 'utm-report-' . $landingPage->slug . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($landingPageId): void {
            $stats = LandingPageView::query()
                ->selectRaw('COALESCE(utm_source, \'direct\') as source, COUNT(*) as views')
                ->where('landing_page_id', $landingPageId)
                ->groupBy('source')
                ->get()
                ->keyBy('source');
            $submissions = LandingPageSubmission::query()
                ->selectRaw('COALESCE(utm_source, \'direct\') as source, COUNT(*) as submissions')
                ->where('landing_page_id', $landingPageId)
                ->groupBy('source')
                ->get()
                ->keyBy('source');
            $allSources = collect(array_unique(array_merge($stats->keys()->all(), $submissions->keys()->all())))->sort();

            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                __('field.report_source'),
                __('field.report_views'),
                __('field.report_submissions'),
                __('field.report_conversion_rate'),
            ]);
            foreach ($allSources as $source) {
                $views = $stats->get($source)?->views ?? 0;
                $subs = $submissions->get($source)?->submissions ?? 0;
                $rate = $views > 0 ? round(($subs / $views) * 100, 1) : 0;
                $sourceLabel = $source === 'direct' ? __('field.report_direct') : $source;
                fputcsv($output, [$sourceLabel, $views, $subs, $rate . '%']);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
