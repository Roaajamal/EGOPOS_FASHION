@extends('layouts.auth2')

@section('title', __('lang_v1.reset_password'))

@section('content')
<style>
    :root {
        --ego-dark: #0a110e;
        --ego-green: #00713d;
        --ego-bg-grade: #12241b;
    }

    body {
        direction: rtl;
        background: radial-gradient(circle at center, var(--ego-bg-grade) 0%, var(--ego-dark) 100%) !important;
        font-family: 'Cairo', sans-serif;
        margin: 0;
        height: 100vh;
    }

    .login-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 20px;
    }

    .login-card {
        background: #ffffff;
        border-radius: 35px;
        box-shadow: 0 50px 100px rgba(0,0,0,0.6);
        padding: 60px 50px;
        width: 100%;
        max-width: 500px;
        position: relative;
    }

    .ego-logo-main {
        max-width: 220px;
        margin-bottom: 40px;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }

    .instruction-text {
        text-align: center;
        color: #64748b;
        font-size: 15px;
        margin-bottom: 30px;
        font-weight: 600;
    }

    .form-group label {
        font-weight: 700;
        color: var(--ego-dark);
        margin-bottom: 12px;
        display: block;
        font-size: 15px;
    }

    .input-ego {
        width: 100%;
        height: 60px;
        background: #f8fafb;
        border: 2px solid #edf2f4;
        border-radius: 16px;
        padding: 0 20px;
        font-size: 17px;
        transition: all 0.3s;
        box-sizing: border-box;
    }

    .input-ego:focus {
        border-color: var(--ego-green);
        background: white;
        outline: none;
        box-shadow: 0 0 0 4px rgba(0, 113, 61, 0.1);
    }

    .btn-ego {
        width: 100%;
        height: 60px;
        background: var(--ego-dark);
        color: white;
        border: none;
        border-radius: 16px;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 15px;
    }

    .btn-ego:hover {
        background: var(--ego-green);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 113, 61, 0.2);
    }

    .back-to-login {
        display: block;
        text-align: center;
        margin-top: 25px;
        color: var(--ego-green);
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
    }

    .back-to-login:hover {
        text-decoration: underline;
    }

    .help-block {
        color: #e11d48;
        font-size: 13px;
        margin-top: 8px;
        display: block;
    }

    .alert-info {
        background-color: #ecfdf5;
        border: 1px solid #10b981;
        color: #065f46;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
        font-weight: 600;
        text-align: center;
    }
</style>

<div class="login-container">
    <div class="login-card">
        <img src="{{ asset('img/egopos.png') }}" alt="EGO POS" class="ego-logo-main">

        <h3 class="instruction-text">@lang('lang_v1.send_password_reset_link')</h3>

        @if (session('status') && is_string(session('status')))
            <div class="alert alert-info" role="alert">
                <i class="fas fa-check-circle"></i> {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            {{ csrf_field() }}

            <div class="form-group {{ $errors->has('email') ? ' has-error' : '' }}">
                <label>@lang('lang_v1.email_address')</label>
                <input id="email" type="email" class="input-ego" name="email" value="{{ old('email') }}" required autofocus placeholder="example@domain.com">

                @if ($errors->has('email'))
                    <span class="help-block">
                        <strong>{{ $errors->first('email') }}</strong>
                    </span>
                @endif
            </div>

            <button type="submit" class="btn-ego">
                @lang('lang_v1.send_password_reset_link')
            </button>
        </form>

        <a href="{{ route('login') }}" class="back-to-login">
            <i class="fas fa-arrow-right"></i> العودة لصفحة تسجيل الدخول
        </a>

        <div style="text-align: center; margin-top: 40px; color: #94a3b8; font-size: 12px;">
            &copy; {{ date('Y') }} <b>EGO POS</b>. جميع الحقوق محفوظة.
        </div>
    </div>
</div>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('.change_lang').click(function() {
                window.location = "{{ route('password.request') }}?lang=" + $(this).attr('value');
            });
        })
    </script>
@endsection