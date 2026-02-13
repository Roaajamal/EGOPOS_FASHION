<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EGO-POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0; height: 100vh; overflow: hidden;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #f0fdf4 100%);
            background-size: 400% 400%; animation: waveMove 12s ease infinite;
            display: flex; align-items: center; justify-content: center;
        }
        @keyframes waveMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

        .master-box {
            width: 1100px; height: 680px; background: white; border-radius: 60px;
            display: flex; position: relative; overflow: hidden;
            box-shadow: 0 50px 120px rgba(45, 90, 39, 0.18);
        }

        .shake-error {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
            border-color: #ef4444 !important;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        .overlay-slider {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, #2d5a27 0%, #40916c 100%);
            z-index: 30; display: flex; align-items: center; justify-content: center;
            transition: all 1.2s cubic-bezier(0.7, 0, 0.2, 1);
        }

        .master-box.active .overlay-slider {
            width: 44%; transform: translateX(128%); 
            clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%);
        }

        .login-content {
            width: 50%; margin-left: 0; margin-right: auto;
            padding: 40px 20px 40px 60px; 
            display: flex; flex-direction: column;
            align-items: flex-start; justify-content: center;
            opacity: 0; transition: 0.6s ease 0.6s;
        }
        .master-box.active .login-content { opacity: 1; }

        .field-input {
            width: 420px; 
            padding: 18px 25px; border-radius: 20px;
            background: #f8fafc; border: 2.5px solid transparent;
            outline: none; font-weight: 700; text-align: left; transition: 0.3s;
        }
        .field-input:focus { border-color: #2d5a27; background: white; }

        .switch {
            position: relative; display: inline-block; width: 40px; height: 22px;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .4s; border-radius: 34px;
        }
        .slider:before {
            position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: #2d5a27; }
        input:checked + .slider:before { transform: translateX(18px); }

        .btn-creative {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(15px); border: 2px solid rgba(255, 255, 255, 0.4);
            color: white; padding: 10px 30px; border-radius: 50px;
            font-size: 1.1rem; font-weight: 900; transition: 0.5s;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
            min-width: 200px; margin: 0 auto;
        }
        .btn-creative:hover { background: white; color: #2d5a27; transform: scale(1.05); }
        .master-box.active .btn-creative { transform: translateX(35px); }

        .hero-main {
            width: 520px; filter: drop-shadow(0 40px 60px rgba(0,0,0,0.25));
            animation: floating 5s ease-in-out infinite; margin-bottom: 2rem;
        }
        @keyframes floating { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-30px); } }

        .sst-logo-fixed {
            position: fixed; top: 30px; left: 40px; z-index: 100;
            width: 85px; height: auto; opacity: 0.9;
        }

        .customer-service {
            display: flex; align-items: center; gap: 10px; color: #166534;
            font-weight: 800; text-decoration: none; font-size: 0.9rem;
            margin-top: 20px; transition: 0.3s;
        }
        .cs-icon {
            width: 35px; height: 35px; background: #dcfce7; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
    </style>
</head>
<body class="{{ $errors->any() ? 'is-error' : '' }}">

    <div id="sstLogo">
        <img src="/img/logo-small.png" class="sst-logo-fixed" onerror="this.src='{{ asset('img/logo-small.png') }}'">
    </div>

    <div class="master-box {{ $errors->any() ? 'active' : '' }}" id="mainContainer">
        
        <div class="overlay-slider">
            <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-w-full">
                <img src="/img/mainn.png" class="hero-main" onerror="this.src='{{ asset('img/mainn.png') }}'">
                <button type="button" id="mainActionBtn" onclick="startFlow()" class="btn-creative">
                    <span id="labelTxt">{{ $errors->any() ? 'Login' : 'Enter System' }}</span>
                    <i id="labelIco" class="fas {{ $errors->any() ? 'fa-sign-in-alt' : 'fa-chevron-right' }}"></i>
                </button>
            </div>
        </div>

        <div class="login-content">
            <div class="tw-mb-6 tw-w-full tw-flex tw-justify-start">
                <img src="/img/egopos-logo.png" class="tw-h-16 tw-ml-10" onerror="this.src='{{ asset('img/egopos-logo.png') }}'">
            </div>

            <form id="authForm" action="{{ route('login') }}" method="POST" class="tw-space-y-5 tw-w-full tw-flex tw-flex-col tw-items-start">
                @csrf
                <div class="tw-text-left">
                    <label class="tw-text-sm tw-font-black tw-text-green-800 tw-ml-4 tw-block tw-mb-2 capitalize tracking-wide">Username</label>
                    <input type="text" name="username" placeholder="Username" class="field-input {{ $errors->any() ? 'shake-error' : '' }}" required>
                </div>

                <div class="tw-text-left">
                    <label class="tw-text-sm tw-font-black tw-text-green-800 tw-ml-4 tw-block tw-mb-2 capitalize tracking-wide">Password</label>
                    <input id="passInp" type="password" name="password" placeholder="••••••••" class="field-input {{ $errors->any() ? 'shake-error' : '' }}" required >
                    
                    <div class="tw-mt-4 tw-ml-4 tw-flex tw-items-center tw-gap-3">
                        <label class="switch">
                            <input type="checkbox" id="showPassCheck" onclick="togglePassword()">
                            <span class="slider"></span>
                        </label>
                        <span class="tw-text-xs tw-font-black tw-text-green-900">Show Password</span>
                    </div>
                </div>

                @if($errors->any())
                <div style="width: 420px;" class="tw-bg-red-50 tw-text-red-600 tw-p-3 tw-rounded-xl tw-mt-2 tw-text-left tw-text-xs tw-font-bold tw-border tw-border-red-100 tw-shadow-sm">
                    <i class="fas fa-exclamation-circle tw-mr-2"></i> Invalid login details, please check your username or password.
                </div>
                @endif

                <a href="tel:064017373" class="customer-service">
                    <div class="cs-icon"><i class="fas fa-headset"></i></div>
                    <span>Support: 064017373</span>
                </a>
            </form>
        </div>
    </div>

    <script>
        let isActivated = {{ $errors->any() ? 'true' : 'false' }};

        function startFlow() {
            const container = document.getElementById('mainContainer');
            if (!isActivated) {
                container.classList.add('active');
                document.getElementById('labelTxt').innerText = "Login";
                document.getElementById('labelIco').className = "fas fa-sign-in-alt";
                isActivated = true;
            } else {
                document.getElementById('authForm').submit();
            }
        }

        function togglePassword() {
            const pass = document.getElementById('passInp');
            const check = document.getElementById('showPassCheck');
            pass.type = check.checked ? 'text' : 'password';
        }
    </script>
</body>
</html>