# Tài liệu Hướng dẫn Sử dụng API Tạo mã QR - VietQR

Tài liệu này hướng dẫn chi tiết cách tích hợp và sử dụng **API Tạo mã QR VietQR** (Phiên bản v2) được cung cấp bởi VietQR.io.

---

## 1. Tổng quan API

API **Tạo mã QR VietQR** cho phép lập trình viên dễ dàng khởi tạo mã QR thanh toán chuẩn VietQR / NAPAS247 dựa trên thông tin tài khoản ngân hàng thụ hưởng, số tiền và nội dung chuyển khoản.

- **Endpoint:** `https://api.vietqr.io/v2/generate`
- **Method:** `POST`
- **Content-Type:** `application/json`

---

## 2. Xác thực (Authentication)

Mọi yêu cầu gửi tới API cần bao gồm các thông tin xác thực trong HTTP Header. Bạn có thể đăng ký tài khoản tại [My VietQR](https://my.vietqr.io) để lấy `Client ID` và `API Key`.

| Header Name | Type | Yêu cầu | Mô tả |
| :--- | :--- | :--- | :--- |
| `x-client-id` | `string` | **Bắt buộc** | Client ID nhận được sau khi đăng ký My VietQR. |
| `x-api-key` | `string` | **Bắt buộc** | API Key chứng thực truy cập ứng dụng. |
| `Content-Type` | `string` | **Bắt buộc** | `application/json` |

---

## 3. Cấu trúc Request Body

Gửi dữ liệu định dạng JSON tới API với các tham số sau:

### 3.1. Danh sách tham số (Request Parameters)

| Tham số | Kiểu dữ liệu | Yêu cầu | Độ dài | Mô tả |
| :--- | :--- | :--- | :--- | :--- |
| `accountNo` | `string` | **Bắt buộc** | 6 - 19 ký tự | Số tài khoản ngân hàng thụ hưởng (chỉ nhập số). |
| `accountName` | `string` | Tùy chọn | 5 - 50 ký tự | Tên chủ tài khoản. Viết hoa, tiếng Việt không dấu, không chứa ký tự đặc biệt. |
| `acqId` | `integer` / `string` | **Bắt buộc** | 6 chữ số | Mã BIN định danh ngân hàng thụ hưởng (Ví dụ: `970415` - VietinBank, `970436` - Vietcombank,...). |
| `amount` | `integer` | Tùy chọn | Tối đa 13 chữ số | Số tiền cần chuyển (Đơn vị: VNĐ). |
| `addInfo` | `string` | Tùy chọn | Tối đa 25 ký tự | Nội dung chuyển khoản. Viết tiếng Việt không dấu, không có ký tự đặc biệt. |
| `format` | `string` | Tùy chọn | - | Định dạng dữ liệu VietQR trả về (Mặc định: `text`). |
| `template` | `string` | Tùy chọn | - | Mẫu thiết kế giao diện QR trả về (Xem bảng bên dưới). |

### 3.2. Các mẫu giao diện (Templates)

Trường `template` hỗ trợ các giá trị mẫu sau:

| Giá trị | Kích thước | Ghi chú / Hiển thị |
| :--- | :--- | :--- |
| `compact` | 540x540 px | Mã QR kèm logo VietQR, Napas và logo Ngân hàng. |
| `compact2` | 540x640 px | Mã QR, các logo và thông tin tài khoản chuyển khoản. |
| `qr_only` | 480x480 px | Mẫu QR đơn giản, chỉ bao gồm mã QR code. |
| `print` | 600x776 px | Dạng hóa đơn / tờ in, bao gồm mã QR, logo và thông tin chuyển khoản chi tiết. |

---

## 4. Dữ liệu Trả về (Response)

### 4.1. Cấu trúc Response thành công (HTTP Status 200)

```json
{
  "code": "00",
  "desc": "Gen VietQR successful!",
  "data": {
    "acpId": 970415,
    "accountName": "QUY VAC XIN PHONG CHONG COVID 19",
    "qrCode": "00020101021238560010A0000007270126000697041501121133666688880208QRIBFTTA53037045405790005802VN62220818Ung Ho Quy Vac Xin63043ACF",
    "qrDataURL": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAeAAAAHgCAYAAAB91L6V..."
  }
}
```

### 4.2. Giải thích các trường Response

| Trường | Kiểu dữ liệu | Ý nghĩa |
| :--- | :--- | :--- |
| `code` | `string` | Mã trạng thái kết quả (`00` là thành công). |
| `desc` | `string` | Mô tả chi tiết kết quả xử lý. |
| `data.acpId` | `integer` | Mã BIN ngân hàng thụ hưởng. |
| `data.accountName` | `string` | Tên chủ tài khoản ngân hàng. |
| `data.qrCode` | `string` | Chuỗi văn bản chuẩn VietQR (dùng cho máy quét chuẩn EMVCo). |
| `data.qrDataURL` | `string` | Chuỗi mã hóa hình ảnh Data URI (Base64), có thể trực tiếp gắn vào thẻ `<img src="...">` trên HTML. |

---

## 5. Ví dụ Mẫu Code (Code Examples)

### 5.1. cURL

```bash
curl --location --request POST 'https://api.vietqr.io/v2/generate' \
  --header 'x-client-id: YOUR_CLIENT_ID' \
  --header 'x-api-key: YOUR_API_KEY' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "accountNo": "113366668888",
    "accountName": "QUY VAC XIN PHONG CHONG COVID",
    "acqId": 970415,
    "amount": 79000,
    "addInfo": "Ung Ho Quy Vac Xin",
    "template": "compact"
  }'
```

### 5.2. JavaScript (Fetch / Node.js)

```javascript
const myHeaders = new Headers();
myHeaders.append("x-client-id", "YOUR_CLIENT_ID");
myHeaders.append("x-api-key", "YOUR_API_KEY");
myHeaders.append("Content-Type", "application/json");

const raw = JSON.stringify({
  "accountNo": "113366668888",
  "accountName": "QUY VAC XIN PHONG CHONG COVID",
  "acqId": 970415,
  "amount": 79000,
  "addInfo": "Ung Ho Quy Vac Xin",
  "template": "compact"
});

const requestOptions = {
  method: 'POST',
  headers: myHeaders,
  body: raw,
  redirect: 'follow'
};

fetch("https://api.vietqr.io/v2/generate", requestOptions)
  .then(response => response.json())
  .then(result => console.log(result))
  .catch(error => console.log('error', error));
```

### 5.3. Python (requests)

```python
import requests
import json

url = "https://api.vietqr.io/v2/generate"

payload = json.dumps({
  "accountNo": "113366668888",
  "accountName": "QUY VAC XIN PHONG CHONG COVID",
  "acqId": 970415,
  "amount": 79000,
  "addInfo": "Ung Ho Quy Vac Xin",
  "template": "compact"
})

headers = {
  'x-client-id': 'YOUR_CLIENT_ID',
  'x-api-key': 'YOUR_API_KEY',
  'Content-Type': 'application/json'
}

response = requests.post(url, headers=headers, data=payload)
print(response.json())
```

---

## 6. Xử lý Lỗi (Validation & Errors)

| Lỗi | Nguyên nhân | Cách khắc phục |
| :--- | :--- | :--- |
| **Thiếu tham số** | Không truyền các trường bắt buộc (`accountNo`, `acqId`). | Kiểm tra request body và bổ sung các trường bắt buộc. |
| **Mã tài khoản không hợp lệ** | `accountNo` ngắn hơn 6 ký tự, dài hơn 19 ký tự hoặc chứa chữ cái. | Chỉ truyền số, độ dài từ 6 - 19 ký tự. |
| **Mã ngân hàng không hợp lệ** | `acqId` (Mã BIN) không chính xác. | Tra cứu lại mã BIN chính xác từ API danh sách ngân hàng của VietQR. |
| **Tên tài khoản không hợp lệ** | `accountName` chứa ký tự có dấu hoặc quá 50 ký tự. | Chuẩn hóa chuỗi tên tài khoản về chữ hoa KHÔNG DẤU. |
| **Số tiền không hợp lệ** | `amount` chứa chữ cái, số âm hoặc dài quá 13 chữ số. | Truyền số nguyên dương. |
| **Nội dung không hợp lệ** | `addInfo` chứa dấu tiếng Việt hoặc vượt quá 25 ký tự. | Rút gọn nội dung, bỏ dấu tiếng Việt và ký tự đặc biệt. |

---

## 7. Ghi chú & Khuyến nghị

- **Bảo mật:** Không chia sẻ `x-api-key` công khai ở phía Client-side (Frontend / App di động). Hãy thực hiện gọi API từ Server-side (Backend).
- **Hiển thị hình ảnh:** Sử dụng giá trị `qrDataURL` trực tiếp làm nguồn ảnh `<img src="${res.data.qrDataURL}" />` để hiển thị tức thì trên ứng dụng hoặc trang web.
