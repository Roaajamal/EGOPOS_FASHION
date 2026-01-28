@extends('layouts.auth2')
@section('title', config('app.name', 'EGO POS'))
@inject('request', 'Illuminate\Http\Request')

@section('content')
<div class="tw-min-h-[80vh] tw-flex tw-items-center tw-justify-center">
    <div class="ego-core-container tw-text-center">
        
        <div class="tw-relative tw-mb-12 ego-animate-entrance">
            <h1 class="ego-main-title">
                {{ config('app.name', 'EGO POS') }}
            </h1>
            <div class="tw-h-[3px] tw-w-24 tw-bg-[#00713d] tw-mx-auto tw-mt-4 tw-rounded-full"></div>
        </div>

        <div class="ego-animate-entrance-delay">
            <a href="{{ route('login') }}" class="ego-login-btn">
                <span>دخول النظام</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-w-5 tw-h-5 tw-mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
            </a>
        </div>

    </div>
</div>

<style>
    /* الخط الرئيسي: أبيض بحدود سوداء واضحة (Stroke) */
    .ego-main-title {
        font-size: 8rem; /* حجم كبير جداً وفخم */
        font-weight: 900;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: -2px;
        line-height: 1;
        /* عمل الحدود السوداء حول النص الأبيض */
        text-shadow: 
            -2px -2px 0 #121d17,  
             2px -2px 0 #121d17,
            -2px  2px 0 #121d17,
             2px  2px 0 #121d17,
             5px  5px 0px rgba(0, 113, 61, 0.2); /* ظل أخضر خفيف جداً خلف الحدود */
    }

    @media (max-width: 768px) {
        .ego-main-title { font-size: 4rem; }
    }

    /* زر الدخول الاحترافي */
    .ego-login-btn {
        display: inline-flex;
        align-items: center;
        background: #121d17;
        color: #ffffff;
        padding: 18px 50px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 20px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .ego-login-btn:hover {
        background: #00713d;
        color: #ffffff;
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 113, 61, 0.2);
    }

    /* حركات الظهور الناعمة */
    .ego-animate-entrance {
        animation: scaleIn 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
    }

    .ego-animate-entrance-delay {
        opacity: 0;
        animation: fadeInUp 0.8s ease-out 0.4s forwards;
    }

    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* خلفية الصفحة: نظيفة جداً مع لمسة EGO */
    body {
        background-color: #f8faf9 !important;
        overflow: hidden; /* لمنع السكرول غير الضروري في الواجهة */
    }
</style>
@endsection