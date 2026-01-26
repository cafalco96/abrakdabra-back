<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Recuperar contraseña</title>
	<style>
		:root {
			--bg: #0b0909;
			--surface: #16161a;
			--primary: #e11d48;
			--text: #f5f5f5;
			--muted: #9ca3af;
		}
		body {
			margin: 0;
			padding: 0;
			background: var(--bg);
			color: var(--text);
			font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
		}
		.wrapper { max-width: 640px; margin: 0 auto; padding: 28px 16px; }
		.card {
			background: var(--surface);
			border-radius: 12px;
			padding: 28px;
			box-shadow: 0 10px 30px rgba(0,0,0,0.35);
			border: 1px solid rgba(255,255,255,0.04);
		}
		h1 { margin: 0 0 16px; font-size: 22px; letter-spacing: -0.01em; }
		p { margin: 0 0 14px; font-size: 14px; line-height: 1.55; color: var(--text); }
		.btn {
			display: inline-block;
			padding: 12px 18px;
			background: var(--primary);
			color: #ffffff !important;
			text-decoration: none;
			border-radius: 8px;
			font-weight: 700;
			letter-spacing: 0.01em;
		}
		.muted { color: var(--muted); font-size: 13px; margin-top: 18px; }
		.center { text-align: center; }
	</style>
</head>
<body>
<div class="wrapper">
	<div class="card">
		<h1>Recupera tu contraseña</h1>

		<p>Hola {{ $user->name ?? 'usuario' }},</p>

		<p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en Abrakdabra.</p>

		<p class="center" style="margin: 26px 0;">
			<a href="{{ $url }}" class="btn">Restablecer contraseña</a>
		</p>

		<p>Si tú no realizaste esta solicitud, puedes ignorar este correo; tu contraseña seguirá siendo la misma.</p>

		<p class="muted">
			Este enlace es válido por {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutos.
		</p>
	</div>
</div>
</body>
</html>
