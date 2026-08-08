# DTH — Quyết định BA cho VietQR và xác nhận thanh toán

Ngày rà soát: 08/08/2026.

## Mục tiêu

Tách rõ ba khái niệm để hệ thống không tự công nhận doanh thu sai:

1. **QR thanh toán**: chỉ là phương tiện giúp khách điền đúng ngân hàng/số tài khoản/số tiền/nội dung.
2. **Thông báo đã chuyển khoản**: chỉ là khai báo của khách, chưa phải chứng cứ thanh toán.
3. **Paid**: trạng thái kế toán/đối soát, Round 2 chỉ Finance được xác nhận.

## Quyết định giữ

- Đồng bộ danh sách ngân hàng từ VietQR Bank API về `vn_banks` để form không hard-code bank code.
- Tài khoản ngân hàng chọn ngân hàng từ master, số tài khoản 6-19 chữ số.
- Nếu có VietQR Client ID/API Key, dùng Account Lookup để gợi ý/xác thực tên tài khoản.
- Nếu có credential Generate API, tạo QR qua VietQR v2 Generate.
- Nếu chưa có credential Generate API, dùng Quick Link image làm fallback.
- QR luôn chứa `grand_total` và nội dung chuyển khoản ổn định theo mã báo giá.
- `payment_snapshot` nằm trên Quotation để Bank Account master thay đổi không làm thay báo giá đã chuẩn bị/gửi.
- Khách chỉ được thông báo thanh toán sau `Accepted`.
- Round 2 chỉ hỗ trợ full payment của toàn bộ `grand_total`.
- Finance đối soát thành `Paid` hoặc trả về `Unpaid` kèm lý do.

## Quyết định chưa làm

- Không tự chuyển Paid chỉ vì QR được tạo/quét.
- Không dùng Account Lookup để suy luận đã nhận tiền.
- Không polling ngân hàng bằng QR API.
- Không làm partial payment bằng một cờ `partially_paid` khi chưa có payment ledger.
- Không tích hợp payOS/webhook trong Round 2. Nếu triển khai phase sau, webhook phải được verify chữ ký trước khi gọi cùng service chuyển Paid hiện tại để giữ một nguồn nghiệp vụ duy nhất.

## Invariant

`Accepted -> payment notice -> Finance verified -> Paid -> Customer + Opportunity Won`.

Bất kỳ tích hợp payment gateway tương lai nào cũng phải thay thế bước **Finance verified** bằng **Verified Gateway Callback**, không được bypass idempotency/conversion service hiện tại.
