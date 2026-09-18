<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kích hoạt tài khoản · DTH Business Core</title>
    <style>
        *{box-sizing:border-box} body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f4f7fb;color:#172033}
        .shell{min-height:100vh;display:grid;place-items:center;padding:32px}.card{width:min(560px,100%);background:#fff;border:1px solid #dce3eb;border-radius:20px;box-shadow:0 24px 70px rgba(15,23,42,.08);padding:34px}
        .brand{font-size:13px;font-weight:800;letter-spacing:.08em;color:#475569;text-transform:uppercase}.title{font-size:30px;line-height:1.1;margin:12px 0 8px}.muted{color:#718096;margin-bottom:26px}.field{margin:16px 0}.field label{display:block;font-size:14px;font-weight:700;margin-bottom:7px}.field input{width:100%;height:46px;border:1px solid #cbd5e1;border-radius:12px;padding:0 14px;font-size:15px;outline:0}.field input:focus{border-color:#475569;box-shadow:0 0 0 4px rgba(71,85,105,.1)}
        .button{width:100%;height:46px;border:0;border-radius:12px;background:#334155;color:#fff;font-weight:800;font-size:15px;cursor:pointer}.errors{background:#fff1f2;color:#be123c;border:1px solid #fecdd3;border-radius:12px;padding:12px 14px;margin-bottom:18px}
    </style>
</head>
<body><div class="shell"><form class="card" method="post" action="{{ route('dth.account.invitation.accept.submit', ['token' => $token]) }}">@csrf
    <div class="brand">DTH Business Core</div><h1 class="title">Kích hoạt tài khoản</h1><div class="muted">Bạn đang kích hoạt quyền truy cập cho <strong>{{ $invitation->email }}</strong>.</div>
    @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
    <div class="field"><label>Họ và tên</label><input name="name" value="{{ old('name', $invitation->name) }}" required></div>
    <div class="field"><label>Mật khẩu</label><input type="password" name="password" required></div>
    <div class="field"><label>Xác nhận mật khẩu</label><input type="password" name="password_confirmation" required></div>
    <button class="button" type="submit">Kích hoạt tài khoản</button>
</form></div></body></html>
