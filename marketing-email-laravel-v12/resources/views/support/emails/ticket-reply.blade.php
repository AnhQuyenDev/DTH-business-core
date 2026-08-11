<p>Xin chào {{ $ticket->requester_name ?: $ticket->customer?->display_name }},</p>
<p>{!! nl2br(e($messageBody)) !!}</p>
<p><strong>Mã hỗ trợ:</strong> {{ $ticket->ticket_code }}</p>
<p>Vui lòng giữ nguyên mã Ticket trong tiêu đề khi phản hồi email để thuận tiện đối chiếu.</p>
<p>Trân trọng,<br>{{ company_name() }}</p>
