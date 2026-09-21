
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>RCT Recuperaciones - Acceso</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/rct.css') }}">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f4f5f7 0%, #e9ecef 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
            color: #1a1a1a;
        }

        /* =========================================
           CONTENEDOR PRINCIPAL
        ========================================= */

        .login-split-card {
            width: 100%;
            max-width: 920px;
            min-height: 540px;

            display: flex;
            overflow: hidden;

            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.04);
            border-radius: 24px;

            box-shadow:
                0 20px 40px rgba(0, 0, 0, 0.08),
                0 1px 3px rgba(0, 0, 0, 0.04);
        }

        /* =========================================
           PANEL IZQUIERDO
        ========================================= */

        .split-banner {
            flex: 1.1;

            background: linear-gradient(
                135deg,
                #3b0914 0%,
                #5a1827 100%
            );

            padding: 52px 48px;

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            color: #ffffff;

            position: relative;
            overflow: hidden;
        }

        /* Decoración sutil */

        .split-banner::before {
            content: "";
            position: absolute;

            width: 260px;
            height: 260px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.035);

            top: -100px;
            right: -100px;
        }

        .split-banner::after {
            content: "";
            position: absolute;

            width: 180px;
            height: 180px;

            border-radius: 50%;

            background: rgba(212, 175, 55, 0.06);

            bottom: -80px;
            left: -60px;
        }

        /* =========================================
           LOGO
        ========================================= */

        .banner-logo-area {
            display: flex;
            align-items: center;
            gap: 16px;

            position: relative;
            z-index: 1;
        }

        .brand-logo-mark {
            width: 52px;
            height: 52px;

            border-radius: 14px;

            background: linear-gradient(
                135deg,
                #ffffff 50%,
                #d4af37 50%
            );

            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);

            position: relative;

            flex-shrink: 0;
        }

        .brand-logo-mark::after {
            content: "CT";

            position: absolute;

            inset: 0;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 14px;
            font-weight: 800;

            color: #5a1827;
        }

        .brand-text-group {
            display: flex;
            flex-direction: column;
        }

        .brand-name {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1;
        }

        .brand-sub {
            margin-top: 7px;

            font-size: 10px;
            font-weight: 700;

            color: #d4af37;

            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* =========================================
           CONTENIDO IZQUIERDO
        ========================================= */

        .banner-content {
            position: relative;
            z-index: 1;
        }

        .banner-content .campaign {
            display: inline-block;

            margin-bottom: 18px;

            padding: 6px 10px;

            border: 1px solid rgba(212, 175, 55, 0.35);
            border-radius: 20px;

            color: #d4af37;

            font-size: 10px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .banner-content h2 {
            max-width: 380px;

            margin-bottom: 16px;

            font-size: 29px;
            font-weight: 700;

            line-height: 1.25;
        }

        .banner-content p {
            max-width: 390px;

            color: #d1b2b6;

            font-size: 13px;
            line-height: 1.7;
        }

        .banner-footer {
            position: relative;
            z-index: 1;

            color: rgba(255, 255, 255, 0.45);

            font-size: 11px;
        }

        /* =========================================
           PANEL DERECHO
        ========================================= */

        .split-form {
            flex: 1;

            padding: 52px 48px;

            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-title-section {
            margin-bottom: 28px;
        }

        .login-title-section h1 {
            margin-bottom: 8px;

            color: #111111;

            font-size: 25px;
            font-weight: 700;
        }

        .login-title-section p {
            color: #666666;

            font-size: 13px;
            line-height: 1.5;
        }

        /* =========================================
           ALERTA DE ERROR LARAVEL
        ========================================= */

        .login-error {
            margin-bottom: 20px;

            padding: 12px 14px;

            background: #fff2f2;

            border: 1px solid #ffcdd2;
            border-radius: 10px;

            color: #c62828;

            font-size: 12px;
            line-height: 1.5;
        }

        /* =========================================
           CAMPOS
        ========================================= */

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;

            margin-bottom: 8px;

            color: #444444;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group input {
            width: 100%;

            padding: 14px 16px;

            background: #fafbfc;

            border: 1px solid #e1e4e8;
            border-radius: 12px;

            color: #111111;

            font-size: 14px;

            outline: none;

            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }

        .input-group input::placeholder {
            color: #a5a5a5;
        }

        .input-group input:focus {
            background: #ffffff;

            border-color: #5a1827;

            box-shadow: 0 0 0 4px rgba(90, 24, 39, 0.08);
        }

        /* =========================================
           OPCIONES
        ========================================= */

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin: 4px 0 24px;

            font-size: 12px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;

            color: #555555;

            cursor: pointer;
            font-weight: 500;
        }

        .remember input {
            width: 15px;
            height: 15px;

            accent-color: #5a1827;

            cursor: pointer;
        }

        .forgot {
            color: #5a1827;

            text-decoration: none;

            font-weight: 600;
        }

        .forgot:hover {
            text-decoration: underline;
        }

        /* =========================================
           BOTÓN
        ========================================= */

        .btn-login {
            width: 100%;

            padding: 14px;

            background: #3b0914;

            border: none;
            border-radius: 12px;

            color: #ffffff;

            font-size: 14px;
            font-weight: 600;

            cursor: pointer;

            box-shadow: 0 4px 12px rgba(59, 9, 20, 0.15);

            transition:
                background-color 0.2s ease,
                transform 0.1s ease,
                box-shadow 0.2s ease;
        }

        .btn-login:hover {
            background: #5a1827;

            box-shadow: 0 6px 16px rgba(59, 9, 20, 0.2);
        }

        .btn-login:active {
            transform: scale(0.99);
        }

        /* =========================================
           FOOTER DEL FORMULARIO
        ========================================= */

        .login-footer {
            margin-top: 24px;

            color: #aaaaaa;

            text-align: center;

            font-size: 10px;
            line-height: 1.5;
        }

        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 768px) {

            body {
                padding: 16px;
            }

            .login-split-card {
                flex-direction: column;

                min-height: auto;

                max-width: 500px;

                border-radius: 20px;
            }

            .split-banner {
                min-height: 300px;

                padding: 36px 30px;
            }

            .banner-content {
                margin: 35px 0;
            }

            .banner-content h2 {
                font-size: 24px;
            }

            .split-form {
                padding: 36px 30px;
            }
        }

        @media (max-width: 480px) {

            body {
                padding: 10px;
            }

            .split-banner,
            .split-form {
                padding: 30px 24px;
            }

            .banner-content h2 {
                font-size: 22px;
            }

            .form-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>

<body>

    <div class="login-split-card">

        <!-- =========================================
             PANEL IZQUIERDO
        ========================================== -->

        <div class="split-banner">

            <div class="banner-logo-area">

                <div class="brand-logo-mark"></div>

                <div class="brand-text-group">
                    <span class="brand-name">RCT</span>
                    <span class="brand-sub">Recuperaciones</span>
                </div>

            </div>


            <div class="banner-content">

                <span class="campaign">
                    Campaña "Sin Atrasos que Atrasan"
                </span>

                <h2>
                    Sistema de Gestión y Control
                </h2>

                <p>
                    Plataforma interna para la administración,
                    seguimiento y control de propuestas de
                    recuperación de cartera de Caja Trujillo.
                </p>

            </div>


            <div class="banner-footer">
                &copy; {{ date('Y') }} RCT Recuperaciones
                &bull; Caja Trujillo
            </div>

        </div>


        <!-- =========================================
             PANEL DERECHO
        ========================================== -->

        <div class="split-form">

            <div class="login-title-section">

                <h1>
                    Iniciar Sesión
                </h1>

                <p>
                    Ingrese sus credenciales corporativas
                    para continuar.
                </p>

            </div>


            {{-- Errores de autenticación de Laravel --}}

            @if($errors->any())

                <div class="login-error">
                    {{ $errors->first() }}
                </div>

            @endif


            <form method="POST" action="{{ route('login') }}">

                @csrf


                <!-- Correo -->

                <div class="input-group">

                    <label for="email">
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        required
                        autofocus
                        placeholder="usuario@cajatrujillo.pe"
                    >

                </div>


                <!-- Contraseña -->

                <div class="input-group">

                    <label for="password">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        placeholder="••••••••"
                    >

                </div>


                <!-- Opciones -->

                <div class="form-actions">

                    <label class="remember">

                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                        >

                        <span>
                            Recordarme
                        </span>

                    </label>


                    <a
                        href="#"
                        class="forgot"
                        onclick="alert('Por favor, contacte al administrador del sistema para restablecer su clave.'); return false;"
                    >
                        ¿Olvidó su clave?
                    </a>

                </div>


                <!-- Botón -->

                <button
                    type="submit"
                    class="btn-login"
                >
                    Acceder al Sistema
                </button>

            </form>


            <div class="login-footer">
                Sistema exclusivo para uso interno
                &middot;
                Caja Trujillo
            </div>

        </div>

    </div>

</body>
</html>

