# DTH Business Core — Email Module Documentation

Baseline tài liệu: Email Module sau UAT, Step G + I và UI refinement V2 (10/09/2026).
Nền tảng UAT: PHP >= 8.2, Laravel 12, Filament 4.13.1, package `dth/email`.

## Tài liệu

1. `01_Tai_lieu_ky_thuat_day_du_Email_Module.docx`
   - Kiến trúc, dependency, cài đặt, database, status, service, queue/scheduler/quota, tracking, compliance, analytics, report, i18n, security, test và extension points.

2. `02_Luong_hoat_dong_va_map_file_function_Email_Module.docx`
   - Trace từng chức năng qua file/function tương ứng: bootstrap, domain, account, template, campaign, recipients, preflight, send/schedule, tracking, suppression, analytics, report, export, health.

3. `03_Huong_dan_su_dung_Email_Module.docx`
   - Hướng dẫn vận hành dành cho Admin/Marketing/Sales operator: thiết lập, tạo template/campaign, import recipient, gửi/lên lịch, đọc dashboard/report/log và suppression.

4. `04_Gioi_thieu_Email_Module_cho_doanh_nghiep.docx`
   - Vai trò doanh nghiệp, lý do cần từng chức năng, rủi ro nếu thiếu, KPI, compliance, reliability và khả năng tái sử dụng.

5. `05_Vong_doi_va_so_do_chuc_nang_Email_Module.docx`
   - Bộ sơ đồ lifecycle/architecture và map hình → source file.

## Diagrams

Thư mục `Diagrams/` chứa PNG + SVG:
- `01_architecture`
- `02_campaign_lifecycle`
- `03_send_pipeline`
- `04_template_personalization`
- `05_tracking_suppression`
- `06_analytics_reports`
- `07_domain_account_setup`
- `08_scheduler_queue_health`

SVG được kèm để có thể chỉnh sửa/tái sử dụng trong slide hoặc tài liệu kỹ thuật.

## Phạm vi hiện tại

- Provider production adapter hiện tại: SMTP.
- Statistical Insight Engine hiện tại là deterministic/rule-based, chưa phải Local AI.
- Enterprise Delivery Log redesign/export (Step F) và Local AI (Step H) đang ở backlog theo quyết định dự án.
