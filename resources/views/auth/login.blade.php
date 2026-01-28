@extends('layouts.auth2')
@section('title', 'EGO POS - تسجيل الدخول')

@section('content')
@php
    $username = old('username');
    $password = null;
    if (config('app.env') == 'demo') {
        $username = 'admin';
        $password = '123456';
    }
@endphp

<style>
    /* 1. منع اللون الأزرق نهائياً وتحديد ألوان الهوية */
    :root {
        --ego-dark: #121d17;
        --ego-green: #00713d;
        --ego-bg: #f4f7f5;
    }

    /* 2. تصفير أي إعدادات افتراضية قد تجلب اللون الأزرق */
    html, body {
        height: 100%;
        margin: 0;
        background-color: var(--ego-bg) !important;
        font-family: 'Cairo', sans-serif;
        overflow-x: hidden;
    }

    /* إخفاء تام لأي عناصر تذييل أو روابط زرقاء خارج الكرت */
    footer, .footer, .login-footer, .adminlte-footer, .registration-links, .login-box-msg {
        display: none !important;
        visibility: hidden !important;
    }

    .login-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 20px;
        box-sizing: border-box;
    }

    .login-card {
        background: #ffffff;
        border-radius: 28px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        padding: 45px;
        width: 100%;
        max-width: 450px;
        position: relative;
        /* البوردر الأخضر المطلوب */
        border: 2px solid var(--ego-green) !important;
        box-sizing: border-box;
    }

    /* 3. تنسيق قسم اللغة حسب الاتجاه (بدون أزرق) */
    .lang-section {
        position: absolute;
        top: 20px;
    }
    [dir="rtl"] .lang-section { left: 20px; }
    [dir="ltr"] .lang-section { right: 20px; }

    .lang-dropdown-btn {
        background: #f1f5f9;
        border: none;
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        color: var(--ego-dark);
        cursor: pointer;
    }
    .lang-dropdown-btn:focus {
        box-shadow: 0 0 0 2px var(--ego-green) !important;
    }

    .ego-logo-main {
        max-width: 190px;
        margin: 10px auto 35px auto;
        display: block;
    }

    /* 4. تنسيق الحقول (Focus أخضر حصراً) */
    .form-group { margin-bottom: 22px; }
    .form-group label {
        font-weight: 700;
        color: var(--ego-dark);
        margin-bottom: 10px;
        display: block;
        font-size: 14px;
    }

    .input-ego {
        width: 100%;
        height: 55px;
        background: #f8fafb;
        border: 2px solid #edf2f4;
        border-radius: 15px;
        padding: 0 15px;
        font-size: 16px;
        transition: 0.3s;
        box-sizing: border-box;
        outline: none !important; /* منع الإطار الأزرق الافتراضي للمتصفح */
    }

    .input-ego:focus {
        border-color: var(--ego-green) !important;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(0, 113, 61, 0.08) !important;
    }

    /* 5. أيقونة العين (تغيير الموقع حسب اللغة) */
    .password-wrapper { position: relative; }
    .show-pass-icon {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        cursor: pointer;
        font-size: 18px;
        z-index: 10;
        display: flex;
        align-items: center;
        padding: 0 10px;
    }
    [dir="rtl"] .show-pass-icon { left: 5px; }
    [dir="ltr"] .show-pass-icon { right: 5px; }

    /* 6. الزر الرئيسي (أسود يتحول للأخضر) */
    .btn-login-ego {
        width: 100%;
        height: 55px;
        background: var(--ego-dark);
        color: white;
        border: none;
        border-radius: 15px;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        margin-top: 15px;
        transition: 0.3s;
    }
    .btn-login-ego:hover {
        background: var(--ego-green);
        box-shadow: 0 10px 20px rgba(0, 113, 61, 0.2);
    }

    /* 7. الروابط والـ Checkbox */
    a { color: var(--ego-green) !important; text-decoration: none !important; }
    a:hover { text-decoration: underline !important; }

    input[type="checkbox"] {
        accent-color: var(--ego-green) !important;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    /* 8. تعديلات الموبايل */
    @media (max-width: 480px) {
        .login-card { padding: 35px 25px; border-radius: 20px; }
        .ego-logo-main { max-width: 160px; }
        .input-ego { height: 50px; }
    }
</style>

<div class="login-wrapper">
    <div class="login-card">
        
        <div class="lang-section">
            <div class="dropdown">
                <button class="lang-dropdown-btn dropdown-toggle" type="button" data-toggle="dropdown">
                    <i class="fas fa-globe"></i> {{ config('constants.langs')[App::getLocale()]['full_name'] }}
                </button>
                <ul class="dropdown-menu">
                    @foreach(config('constants.langs') as $key => $val)
                        <li><a href="{{ route('login') }}?lang={{$key}}" style="color: #333 !important; padding: 10px; display: block;">{{$val['full_name']}}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <img src="{{ asset('img/egopos.png') }}" alt="EGO POS" class="ego-logo-main">

        <form method="POST" action="{{ route('login') }}" id="login-form">
            {{ csrf_field() }}

            <div class="form-group">
                <label>@lang('lang_v1.username')</label>
                <input class="input-ego" name="username" required autofocus id="username" type="text" value="{{ $username }}" placeholder="اسم المستخدم" />
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="margin: 0;">@lang('lang_v1.password')</label>
                    <a href="{{ route('password.request') }}" style="font-size: 12px; font-weight: 700;">@lang('lang_v1.forgot_your_password')</a>
                </div>
                <div class="password-wrapper">
                    <input class="input-ego" id="password_input" type="password" name="password" value="{{ $password }}" required placeholder="••••••••" />
                    <span id="toggle_password_btn" class="show-pass-icon">
                        <i class="fas fa-eye" id="eye_icon"></i>
                    </span>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; margin-top: 15px;">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember" style="margin: 0; font-weight: 600; font-size: 14px; color: #64748b; cursor: pointer;">@lang('lang_v1.remember_me')</label>
            </div>

            <button type="submit" class="btn-login-ego">
                @lang('lang_v1.login')
            </button>
        </form>

        <div style="text-align: center; margin-top: 40px; color: #94a3b8; font-size: 11px;">
            &copy; {{ date('Y') }} <b>EGO POS</b>. جميع الحقوق محفوظة.
        </div>
    </div>
</div>
@stop

@section('javascript')
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var toggleBtn = document.getElementById('toggle_password_btn');
        var passwordInput = document.getElementById('password_input');
        var eyeIcon = document.getElementById('eye_icon');

        if(toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function(e) {
                // منع أي سلوك افتراضي قد يسبب مشاكل
                e.preventDefault();
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                }
            });
        }
    });
</script>
@endsection