<x-filament-panels::page>
    @php
        $r = $this->report;
        $s = $r['summary'];
        $money = static fn ($v) => number_format((float) $v, 0, ',', '.').' ₫';
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Bộ lọc Revenue Attribution</x-slot>
            <x-slot name="description">Revenue Attribution V1 dùng mô hình Lead Origin: nguồn tại thời điểm tạo Lead được đóng băng khi Payment được Finance xác minh.</x-slot>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="text-sm font-medium">Từ ngày</label><x-filament::input.wrapper><x-filament::input type="date" wire:model.live="startDate" /></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">Đến ngày</label><x-filament::input.wrapper><x-filament::input type="date" wire:model.live="endDate" /></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">Chiến dịch Marketing</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="marketingCampaignId"><option value="">Tất cả</option>@foreach($this->options['marketing_campaigns'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">Chiến dịch Email</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="emailCampaignId"><option value="">Tất cả</option>@foreach($this->options['email_campaigns'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">Landing Page</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="landingPageId"><option value="">Tất cả</option>@foreach($this->options['landing_pages'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">Nguồn / UTM Source</label><x-filament::input.wrapper><x-filament::input wire:model.live.debounce.400ms="utmSource" placeholder="facebook, instagram, email..." /></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">UTM Medium</label><x-filament::input.wrapper><x-filament::input wire:model.live.debounce.400ms="utmMedium" placeholder="paid_social, organic, email..." /></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">UTM Campaign</label><x-filament::input.wrapper><x-filament::input wire:model.live.debounce.400ms="utmCampaign" placeholder="hosting_q3_2026" /></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">UTM Content</label><x-filament::input.wrapper><x-filament::input wire:model.live.debounce.400ms="utmContent" placeholder="video_a, banner_b..." /></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">Dịch vụ</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="serviceId"><option value="">Tất cả</option>@foreach($this->options['services'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">Gói dịch vụ</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="servicePackageId"><option value="">Tất cả</option>@foreach($this->options['packages'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="text-sm font-medium">Nhân viên Sales</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="salesStaffId"><option value="">Tất cả</option>@foreach($this->options['sales_staff'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
            </div>
            <div class="mt-4"><x-filament::button color="gray" wire:click="resetFilters">Đặt lại bộ lọc</x-filament::button></div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Thực thu', $money($s['gross_collected']), 'heroicon-o-banknotes'],
                ['Doanh thu trước VAT', $money($s['net_revenue']), 'heroicon-o-chart-bar-square'],
                ['VAT', $money($s['tax']), 'heroicon-o-receipt-percent'],
                ['Giao dịch Paid', number_format($s['payments']), 'heroicon-o-check-circle'],
                ['Khách hàng đã trả', number_format($s['customers']), 'heroicon-o-user-group'],
                ['Giá trị TB / Payment', $money($s['average_payment']), 'heroicon-o-calculator'],
                ['Chờ đối soát', $money($s['pending_verification']), 'heroicon-o-clock'],
                ['Accepted chưa thu', $money($s['outstanding_accepted']), 'heroicon-o-exclamation-triangle'],
            ] as [$label,$value,$icon])
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-3"><span class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</span><x-filament::icon :icon="$icon" class="h-5 w-5 text-gray-400" /></div>
                    <div class="mt-2 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <x-filament::section>
            <x-slot name="heading">Hiệu quả Chiến dịch Marketing</x-slot>
            <x-slot name="description">Budget hiện là ngân sách tổng khai báo của chiến dịch; ROAS so Budget này với doanh thu trước VAT trong khoảng ngày đang lọc. Với khoảng ngày chỉ chiếm một phần chiến dịch, hãy đọc ROAS như chỉ báo tham khảo cho tới khi có ledger chi phí theo ngày.</x-slot>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b dark:border-white/10 text-left"><th class="p-2">Chiến dịch</th><th class="p-2 text-right">Budget</th><th class="p-2 text-right">Leads</th><th class="p-2 text-right">Paid</th><th class="p-2 text-right">Conversion</th><th class="p-2 text-right">Net Revenue</th><th class="p-2 text-right">CAC</th><th class="p-2 text-right">ROAS</th></tr></thead><tbody>
            @forelse($r['marketing_campaigns'] as $row)<tr class="border-b dark:border-white/5"><td class="p-2 font-medium">{{ $row['name'] }}</td><td class="p-2 text-right">{{ $money($row['budget']) }}</td><td class="p-2 text-right">{{ $row['leads'] }}</td><td class="p-2 text-right">{{ $row['paid_customers'] }}</td><td class="p-2 text-right">{{ $row['conversion_rate'] }}%</td><td class="p-2 text-right font-semibold">{{ $money($row['net_revenue']) }}</td><td class="p-2 text-right">{{ $row['cac'] === null ? '—' : $money($row['cac']) }}</td><td class="p-2 text-right">{{ $row['roas'] === null ? '—' : number_format($row['roas'], 2).'x' }}</td></tr>@empty<tr><td colspan="8" class="p-6 text-center text-gray-500">Chưa có doanh thu gắn với Chiến dịch Marketing trong khoảng thời gian này.</td></tr>@endforelse
            </tbody></table></div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section><x-slot name="heading">Nguồn / UTM Source</x-slot><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b dark:border-white/10"><th class="p-2 text-left">Nguồn</th><th class="p-2 text-right">Paid</th><th class="p-2 text-right">Net Revenue</th><th class="p-2 text-right">Thực thu</th></tr></thead><tbody>@forelse($r['sources'] as $row)<tr class="border-b dark:border-white/5"><td class="p-2 font-medium">{{ $row['name'] }}</td><td class="p-2 text-right">{{ $row['paid_customers'] }}</td><td class="p-2 text-right">{{ $money($row['net_revenue']) }}</td><td class="p-2 text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="4" class="p-5 text-center text-gray-500">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></div></x-filament::section>
            <x-filament::section><x-slot name="heading">Landing Page</x-slot><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b dark:border-white/10"><th class="p-2 text-left">Landing Page</th><th class="p-2 text-right">Leads</th><th class="p-2 text-right">Paid</th><th class="p-2 text-right">Conversion</th><th class="p-2 text-right">Net Revenue</th></tr></thead><tbody>@forelse($r['landing_pages'] as $row)<tr class="border-b dark:border-white/5"><td class="p-2 font-medium">{{ $row['name'] }}</td><td class="p-2 text-right">{{ $row['leads'] }}</td><td class="p-2 text-right">{{ $row['paid_customers'] }}</td><td class="p-2 text-right">{{ $row['conversion_rate'] }}%</td><td class="p-2 text-right">{{ $money($row['net_revenue']) }}</td></tr>@empty<tr><td colspan="5" class="p-5 text-center text-gray-500">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></div></x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">UTM Campaign / Content tạo doanh thu</x-slot>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b dark:border-white/10"><th class="p-2 text-left">UTM Campaign</th><th class="p-2 text-left">Content</th><th class="p-2 text-right">Paid KH</th><th class="p-2 text-right">Payments</th><th class="p-2 text-right">Net Revenue</th><th class="p-2 text-right">Thực thu</th></tr></thead><tbody>@forelse($r['utm_campaigns'] as $row)<tr class="border-b dark:border-white/5"><td class="p-2 font-medium">{{ $row['name'] }}</td><td class="p-2">{{ $row['content'] }}</td><td class="p-2 text-right">{{ $row['paid_customers'] }}</td><td class="p-2 text-right">{{ $row['payments'] }}</td><td class="p-2 text-right">{{ $money($row['net_revenue']) }}</td><td class="p-2 text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="6" class="p-5 text-center text-gray-500">Chưa có UTM Campaign được attribution cho Payment.</td></tr>@endforelse</tbody></table></div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section><x-slot name="heading">Dịch vụ bán được</x-slot><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b dark:border-white/10"><th class="p-2 text-left">Dịch vụ</th><th class="p-2 text-right">Payments</th><th class="p-2 text-right">Net Revenue</th><th class="p-2 text-right">VAT</th><th class="p-2 text-right">Thực thu</th></tr></thead><tbody>@forelse($r['services'] as $row)<tr class="border-b dark:border-white/5"><td class="p-2 font-medium">{{ $row['name'] }}</td><td class="p-2 text-right">{{ $row['payments'] }}</td><td class="p-2 text-right">{{ $money($row['net_revenue']) }}</td><td class="p-2 text-right">{{ $money($row['tax']) }}</td><td class="p-2 text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="5" class="p-5 text-center text-gray-500">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></div></x-filament::section>
            <x-filament::section><x-slot name="heading">Gói dịch vụ bán được</x-slot><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b dark:border-white/10"><th class="p-2 text-left">Gói</th><th class="p-2 text-right">Payments</th><th class="p-2 text-right">Net Revenue</th><th class="p-2 text-right">VAT</th><th class="p-2 text-right">Thực thu</th></tr></thead><tbody>@forelse($r['packages'] as $row)<tr class="border-b dark:border-white/5"><td class="p-2 font-medium">{{ $row['name'] }}</td><td class="p-2 text-right">{{ $row['payments'] }}</td><td class="p-2 text-right">{{ $money($row['net_revenue']) }}</td><td class="p-2 text-right">{{ $money($row['tax']) }}</td><td class="p-2 text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="5" class="p-5 text-center text-gray-500">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></div></x-filament::section>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section><x-slot name="heading">Doanh thu theo Sales</x-slot><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b dark:border-white/10"><th class="p-2 text-left">Sales</th><th class="p-2 text-right">Paid KH</th><th class="p-2 text-right">Payments</th><th class="p-2 text-right">Net Revenue</th></tr></thead><tbody>@forelse($r['sales'] as $row)<tr class="border-b dark:border-white/5"><td class="p-2 font-medium">{{ $row['name'] }}</td><td class="p-2 text-right">{{ $row['paid_customers'] }}</td><td class="p-2 text-right">{{ $row['payments'] }}</td><td class="p-2 text-right">{{ $money($row['net_revenue']) }}</td></tr>@empty<tr><td colspan="4" class="p-5 text-center text-gray-500">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></div></x-filament::section>
            <x-filament::section><x-slot name="heading">Top khách hàng theo thực thu</x-slot><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b dark:border-white/10"><th class="p-2 text-left">Khách hàng</th><th class="p-2 text-right">Payments</th><th class="p-2 text-right">Net Revenue</th><th class="p-2 text-right">Thực thu</th></tr></thead><tbody>@forelse($r['customers'] as $row)<tr class="border-b dark:border-white/5"><td class="p-2 font-medium">{{ $row['name'] }}</td><td class="p-2 text-right">{{ $row['payments'] }}</td><td class="p-2 text-right">{{ $money($row['net_revenue']) }}</td><td class="p-2 text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="4" class="p-5 text-center text-gray-500">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></div></x-filament::section>
        </div>

        <x-filament::section><x-slot name="heading">Chiến dịch Email tạo doanh thu</x-slot><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b dark:border-white/10"><th class="p-2 text-left">Chiến dịch Email</th><th class="p-2 text-right">Paid KH</th><th class="p-2 text-right">Payments</th><th class="p-2 text-right">Net Revenue</th><th class="p-2 text-right">Thực thu</th></tr></thead><tbody>@forelse($r['email_campaigns'] as $row)<tr class="border-b dark:border-white/5"><td class="p-2 font-medium">{{ $row['name'] }}</td><td class="p-2 text-right">{{ $row['paid_customers'] }}</td><td class="p-2 text-right">{{ $row['payments'] }}</td><td class="p-2 text-right">{{ $money($row['net_revenue']) }}</td><td class="p-2 text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="5" class="p-5 text-center text-gray-500">Chưa có Payment nào được attribution cho Chiến dịch Email.</td></tr>@endforelse</tbody></table></div></x-filament::section>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-200">
            <strong>Giới hạn V1:</strong> báo cáo đang dùng Lead Origin Attribution (first captured origin), chưa phải multi-touch attribution. “Thực thu” bao gồm VAT; “Doanh thu trước VAT” không bao gồm VAT. Chưa tính chi phí vận hành/lợi nhuận/P&amp;L.
        </div>
    </div>
</x-filament-panels::page>
