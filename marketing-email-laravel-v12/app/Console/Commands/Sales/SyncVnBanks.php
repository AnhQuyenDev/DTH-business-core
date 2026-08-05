<?php

namespace App\Console\Commands\Sales;

use App\Models\VnBank;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncVnBanks extends Command
{
    protected $signature = 'sales:sync-vn-banks';

    protected $description = 'Sync Vietnamese bank list from VietQR API (fallback to built-in list)';

    private const FALLBACK = [
        ['code' => 'VBA', 'short_name' => 'Agribank', 'name' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam', 'swift_code' => 'VBAAVNVX'],
        ['code' => 'VCB', 'short_name' => 'Vietcombank', 'name' => 'Ngân hàng TMCP Ngoại thương Việt Nam', 'swift_code' => 'VCBVVNVX'],
        ['code' => 'BIDV', 'short_name' => 'BIDV', 'name' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam', 'swift_code' => 'BIDVVNVX'],
        ['code' => 'CTG', 'short_name' => 'VietinBank', 'name' => 'Ngân hàng TMCP Công Thương Việt Nam', 'swift_code' => 'ICBVVNVX'],
        ['code' => 'TCB', 'short_name' => 'Techcombank', 'name' => 'Ngân hàng TMCP Kỹ thương Việt Nam', 'swift_code' => 'VTCBVNVX'],
        ['code' => 'MBB', 'short_name' => 'MB', 'name' => 'Ngân hàng TMCP Quân đội', 'swift_code' => 'MSCBVNVX'],
        ['code' => 'ACB', 'short_name' => 'ACB', 'name' => 'Ngân hàng TMCP Á Châu', 'swift_code' => 'ASCBVNVX'],
        ['code' => 'VIB', 'short_name' => 'VIB', 'name' => 'Ngân hàng TMCP Quốc tế Việt Nam', 'swift_code' => 'VNIBVNVX'],
        ['code' => 'HDB', 'short_name' => 'HDBank', 'name' => 'Ngân hàng TMCP Phát triển TP.HCM', 'swift_code' => 'HDBCVNVX'],
        ['code' => 'SHB', 'short_name' => 'SHB', 'name' => 'Ngân hàng TMCP Sài Gòn - Hà Nội', 'swift_code' => 'SHBAVNVX'],
        ['code' => 'VPB', 'short_name' => 'VPBank', 'name' => 'Ngân hàng TMCP Việt Nam Thịnh Vượng', 'swift_code' => 'VPBKVNVX'],
        ['code' => 'MSB', 'short_name' => 'MSB', 'name' => 'Ngân hàng TMCP Hàng hải Việt Nam', 'swift_code' => 'MSBVVNVX'],
        ['code' => 'TPB', 'short_name' => 'TPBank', 'name' => 'Ngân hàng TMCP Tiên Phong', 'swift_code' => 'TPBVVNVX'],
        ['code' => 'OCB', 'short_name' => 'OCB', 'name' => 'Ngân hàng TMCP Phương Đông', 'swift_code' => 'OJCBVNVX'],
        ['code' => 'LPB', 'short_name' => 'LPBank', 'name' => 'Ngân hàng TMCP Bưu điện Liên Việt', 'swift_code' => 'LVBKVNVX'],
        ['code' => 'SEAB', 'short_name' => 'SeABank', 'name' => 'Ngân hàng TMCP Đông Nam Á', 'swift_code' => 'SEAVVNVX'],
        ['code' => 'NAB', 'short_name' => 'Nam A Bank', 'name' => 'Ngân hàng TMCP Nam Á', 'swift_code' => 'NAMAVNVX'],
        ['code' => 'BAB', 'short_name' => 'BacABank', 'name' => 'Ngân hàng TMCP Bắc Á', 'swift_code' => 'BACAVNVX'],
        ['code' => 'PGB', 'short_name' => 'PGBank', 'name' => 'Ngân hàng TMCP Xăng dầu Petrolimex', 'swift_code' => 'PGBLVNVX'],
        ['code' => 'VAB', 'short_name' => 'VietABank', 'name' => 'Ngân hàng TMCP Việt Á', 'swift_code' => 'VNTAVNVX'],
        ['code' => 'KLB', 'short_name' => 'KienlongBank', 'name' => 'Ngân hàng TMCP Kiên Long', 'swift_code' => 'KLBVVNVX'],
        ['code' => 'STB', 'short_name' => 'Sacombank', 'name' => 'Ngân hàng TMCP Sài Gòn Thương Tín', 'swift_code' => 'SACOVNVX'],
        ['code' => 'EXB', 'short_name' => 'Eximbank', 'name' => 'Ngân hàng TMCP Xuất nhập khẩu Việt Nam', 'swift_code' => 'EBVIVNVX'],
        ['code' => 'PVC', 'short_name' => 'PVcomBank', 'name' => 'Ngân hàng TMCP Đại chúng Việt Nam', 'swift_code' => 'WBVNVNVX'],
        ['code' => 'OJB', 'short_name' => 'OceanBank', 'name' => 'Ngân hàng TMCP Đại Dương', 'swift_code' => 'OJBVNVNX'],
        ['code' => 'VCCB', 'short_name' => 'VietCapitalBank', 'name' => 'Ngân hàng TMCP Bản Việt', 'swift_code' => 'VCBCVNVX'],
        ['code' => 'NCB', 'short_name' => 'NCB', 'name' => 'Ngân hàng TMCP Quốc Dân', 'swift_code' => 'NVBAVNVX'],
        ['code' => 'BVB', 'short_name' => 'BaoViet Bank', 'name' => 'Ngân hàng TMCP Bảo Việt', 'swift_code' => 'BVBVVNVX'],
        ['code' => 'GNB', 'short_name' => 'GPBank', 'name' => 'Ngân hàng TMCP Dầu khí Toàn cầu', 'swift_code' => 'GBNKVNVX'],
        ['code' => 'CBB', 'short_name' => 'CBBank', 'name' => 'Ngân hàng TMCP Xây dựng Việt Nam', 'swift_code' => 'GTBAVNVX'],
        ['code' => 'ABB', 'short_name' => 'ABBANK', 'name' => 'Ngân hàng TMCP An Bình', 'swift_code' => 'ABBKVNVX'],
        ['code' => 'SCB', 'short_name' => 'SCB', 'name' => 'Ngân hàng TMCP Sài Gòn', 'swift_code' => 'SACLVNVX'],
        ['code' => 'DAB', 'short_name' => 'DongA Bank', 'name' => 'Ngân hàng TMCP Đông Á', 'swift_code' => 'EACBVNVX'],
        ['code' => 'VIETBANK', 'short_name' => 'VietBank', 'name' => 'Ngân hàng TMCP Việt Nam Thương Tín', 'swift_code' => 'VNTBVNVX'],
        ['code' => 'VRB', 'short_name' => 'VRB', 'name' => 'Ngân hàng Liên doanh Việt - Nga', 'swift_code' => 'VRTBVNVX'],
        ['code' => 'NVB', 'short_name' => 'NamVietBank', 'name' => 'Ngân hàng TMCP Nam Việt', 'swift_code' => 'NVBVVNVX'],
        ['code' => 'SGB', 'short_name' => 'Saigonbank', 'name' => 'Ngân hàng TMCP Sài Gòn Công Thương', 'swift_code' => 'SGCBVNVX'],
        ['code' => 'VBA', 'short_name' => 'Agribank', 'name' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam', 'swift_code' => 'VBAAVNVX'],
    ];

    public function handle(): int
    {
        $banks = $this->fetchFromApi();

        if (empty($banks)) {
            $this->warn('VietQR API unavailable, using built-in fallback list.');
            $banks = self::FALLBACK;
        }

        foreach ($banks as $bank) {
            VnBank::updateOrCreate(
                ['code' => $bank['code']],
                [
                    'bin' => $bank['bin'] ?? null,
                    'short_name' => $bank['short_name'],
                    'name' => $bank['name'],
                    'swift_code' => $bank['swift_code'] ?? null,
                    'logo' => $bank['logo'] ?? null,
                ]
            );
        }

        $this->info('Synced '.count($banks).' banks.');

        return self::SUCCESS;
    }

    private function fetchFromApi(): array
    {
        try {
            $response = Http::timeout(10)->get('https://api.vietqr.io/v2/banks');

            if (! $response->ok()) {
                return [];
            }

            return collect($response->json('data', []))
                ->filter(fn (array $bank) => filled($bank['code'] ?? null) && filled($bank['short_name'] ?? null))
                ->map(fn (array $bank) => [
                    'code' => (string) $bank['code'],
                    'bin' => isset($bank['bin']) ? (string) $bank['bin'] : null,
                    'short_name' => (string) $bank['short_name'],
                    'name' => (string) ($bank['name'] ?? $bank['short_name']),
                    'swift_code' => $bank['swift_code'] ?? null,
                    'logo' => $bank['logo'] ?? null,
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
