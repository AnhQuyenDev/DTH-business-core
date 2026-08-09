# Redesign UI/UX Phase 2 - Dashboard, Bao cao va Phan tich

Ngay trien khai: 10/08/2026

## Muc tieu

Phase 2 chuyen cac dashboard tu dang liet ke KPI/bang bieu sang giao dien ho tro ra quyet dinh, uu tien bieu do, so sanh ky, phan tich nguon doanh thu, hieu qua chien dich va nang suat nhan luc.

Theo yeu cau BA, Opportunity/Cơ hội kinh doanh khong duoc dung lam trong tam dashboard phan tich. Module Opportunity van duoc giu nguyen de van hanh Sales, nhung analytics Sales dung chuoi Bao gia -> Da xem -> Da chap nhan -> Paid va doanh thu thuc thu.

## Nguyen tac du lieu

1. Tien thuc thu: Payment co status verified va paid_at trong khoang phan tich.
2. Doanh thu thuan: payments.net_amount, tach khoi VAT.
3. Doanh thu theo dich vu/goi: payment_revenue_lines.
4. Attribution Marketing: payment_attributions (Marketing Campaign, Email Campaign, Landing Page, UTM/acquisition source).
5. Marketing Manager chi xem doanh thu co attribution Marketing; Marketing Staff chi xem attribution thuoc noi dung do chinh minh tao.
6. Sales Staff chi xem payment/quotation cua chinh minh; Sales Manager xem Sales team.
7. Finance xem toan bo du lieu thanh toan/doi soat theo quyen hien tai.
8. Admin/Executive co pham vi doc toan he thong.

## Dashboard Admin / Dieu hanh

- Thuc thu, doanh thu thuan, Paid Customer, Lead moi.
- Tien cho doi soat, Accepted chua thu, tuong tac khach hang, nhan su active.
- Line chart doanh thu hien tai vs ky truoc.
- Donut doanh thu theo nguon.
- Bar chart doanh thu theo dich vu.
- Card hieu qua Marketing Campaign/ROAS.
- Management Insights tu dong tom tat bien dong quan trong.
- Workforce snapshot de biet ai tao gia tri, ban gi, cham soc ai.
- Link drill-down sang Revenue Analysis, Campaign Analysis, Workforce Analysis, Audit.

## Dashboard Marketing

- Views -> Submissions -> Leads -> Paid Customers funnel.
- Doanh thu attributed, budget khai bao, ROAS tham chieu.
- Revenue trend, source/UTM mix.
- Marketing Campaign comparison: Leads, Paid, Conversion, Revenue, Budget, ROAS.
- Email Campaign: Recipients, Open Rate, Click Rate, Paid Customer, Revenue.
- Landing Page: Views, Submissions, Leads, Paid Customer, Revenue.

Luu y: ROAS hien tai dung budget khai bao cua Marketing Campaign, khong phai ad-spend ledger. Chi so nay la chi so quan tri tham chieu cho den khi co module chi phi/quang cao thuc te.

## Dashboard Finance

- Gross collected, Net revenue, VAT, payment count.
- Pending verification, outstanding, average payment, average verification time.
- Revenue current vs previous period.
- Aging Accepted chua thu: 0-7, 8-30, 31+ ngay.
- Revenue source mix.
- Hang cho doi soat uu tien.

## Dashboard Sales

- Sent quotations, Viewed, Accepted, Paid, acceptance rate.
- Net revenue, Paid Customers, Average Payment.
- Funnel Sent -> Viewed -> Accepted -> Paid.
- Revenue trend.
- Revenue by Service.
- Team revenue neu la Sales Manager.

Khong dua Opportunity vao dashboard theo quyet dinh BA Phase 2.

## Dashboard CSKH

- Lead handled, Qualified, Qualification rate.
- Follow-up today, overdue, unassigned.
- Status mix.
- Priority queue.
- Team productivity khi la Manager.

## Campaign Analysis

Trang phan tich rieng cho Admin/Executive/Marketing:

- Revenue trend theo ky.
- Revenue source/UTM donut.
- Marketing Campaign comparison.
- Email Campaign comparison.
- Landing Page performance.
- CSV export.

## Revenue Analysis

Bao cao doanh thu duoc redesign theo dashboard:

- Bo loc thoi gian + Campaign/UTM/Service/Package/Sales.
- KPI current vs previous equal-length period.
- Revenue trend current vs previous.
- Revenue mix by Source.
- Service/Sales bar charts.
- Campaign comparison va cac top dimension thay vi day dac bang bieu.
- CSV export.

## Workforce Analysis va KPI

Workforce Analysis tra loi cac cau hoi van hanh:

- Ai da lam gi trong ky?
- Sales ban duoc gi, thu ve bao nhieu, co bao nhieu Paid Customer?
- CSKH xu ly bao nhieu Lead, qualify bao nhieu, cham soc nhung Customer nao, co overdue khong?
- Marketing tao bao nhieu Campaign/Landing Page/Lead, attributed revenue bao nhieu?
- Finance doi soat bao nhieu payment, tong tien va thoi gian doi soat trung binh?

### Activity Index

Activity Index 0-100 la chi so tuong doi trong cung phong ban va cung khoang thoi gian. No duoc chuan hoa theo output cua nhan vien co output cao nhat trong phong.

- Sales: revenue, paid customers, accepted quotations, quotation activity.
- Marketing: attributed revenue, leads, paid customers, campaign/landing activity.
- CSKH: qualified leads, customer interactions, customers touched, handled leads; co penalty nhe cho overdue.
- Finance: verified amount, verified payment count, verification speed, customers.

Chi so nay KHONG phai luong, thuong hay KPI nhan su chinh thuc. Can ket hop target cong viec, chat luong, SLA va danh gia quan ly truoc khi dung cho performance review.

## So sanh ky

Cac dashboard chinh co cac period:

- 7 ngay
- 30 ngay
- 90 ngay
- Tu dau nam (YTD)

Moi ky duoc so sanh voi khoang thoi gian ngay truoc do co do dai tuong duong. Revenue Analysis voi khoang ngay tuy chon cung tu tao previous period cung do dai.

## Xuat bao cao

Admin, cac Manager va Finance co CSV export o dashboard/analysis phu hop. CSV su dung cung data scope voi UI.

## Anh huong nghiep vu

Phase 2 khong thay doi:

- Lead workflow.
- Opportunity state machine.
- Quotation/Approval/OTP.
- Payment verification.
- Customer conversion.
- Customer Care workflow.

Phase 2 chi bo sung presentation + analytics queries + report scoping.

## Trien khai

Khong co migration moi. Khong can npm build. Sau khi copy code:

```bash
php artisan optimize:clear
```

## Regression checklist

1. Admin Dashboard load va khong co Opportunity KPI.
2. Revenue line chart current/previous hien dung.
3. Campaign Analytics load cho Admin/Marketing Manager, staff bi scope dung.
4. Revenue Analysis filters khong lam lo du lieu ngoai scope.
5. Workforce Analysis Admin thay toan cong ty; Manager chi thay phong minh.
6. Marketing Staff khong thay revenue khong thuoc attribution noi dung minh tao.
7. Sales Staff chi thay revenue cua minh.
8. Finance Dashboard van vao Payment Tracking duoc.
9. Email Campaign Report hien open/click/paid/revenue va status dung ngon ngu.
10. CSV export mo duoc bang Excel/Google Sheets va co UTF-8 BOM.
