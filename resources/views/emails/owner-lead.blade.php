<!doctype html>
<html lang="it">
<body style="margin:0; padding:24px; background:#f5f6f8; font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#0f172a;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:14px; padding:28px; border:1px solid #e2e8f0;">
        <h1 style="margin:0 0 4px; font-size:20px;">Nuova richiesta dal sito</h1>
        <p style="margin:0 0 20px; color:#64748b; font-size:14px;">Un proprietario ha chiesto una valutazione gratuita su hostup.it.</p>

        <table style="width:100%; border-collapse:collapse; font-size:15px;">
            <tr>
                <td style="padding:8px 0; color:#64748b; width:130px;">Nome</td>
                <td style="padding:8px 0; font-weight:600;">{{ $lead->name }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#64748b;">Email</td>
                <td style="padding:8px 0;"><a href="mailto:{{ $lead->email }}" style="color:#2b66e8;">{{ $lead->email }}</a></td>
            </tr>
            @if ($lead->phone)
                <tr>
                    <td style="padding:8px 0; color:#64748b;">Telefono</td>
                    <td style="padding:8px 0;"><a href="tel:{{ $lead->phone }}" style="color:#2b66e8;">{{ $lead->phone }}</a></td>
                </tr>
            @endif
            @if ($lead->city)
                <tr>
                    <td style="padding:8px 0; color:#64748b;">Zona immobile</td>
                    <td style="padding:8px 0;">{{ $lead->city }}</td>
                </tr>
            @endif
            @if ($lead->property_type)
                <tr>
                    <td style="padding:8px 0; color:#64748b;">Tipo</td>
                    <td style="padding:8px 0;">{{ $lead->property_type }}</td>
                </tr>
            @endif
        </table>

        @if ($lead->message)
            <div style="margin-top:16px; padding:14px 16px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; font-size:15px; white-space:pre-line;">{{ $lead->message }}</div>
        @endif

        <p style="margin:24px 0 0;">
            <a href="{{ route('admin.leads.index') }}" style="display:inline-block; background:#2b66e8; color:#fff; text-decoration:none; padding:12px 20px; border-radius:10px; font-weight:600; font-size:14px;">
                Apri in Richieste
            </a>
        </p>

        <p style="margin:20px 0 0; color:#94a3b8; font-size:12px;">Rispondi a questa email per scrivere direttamente al proprietario (reply-to già impostato).</p>
    </div>
</body>
</html>
