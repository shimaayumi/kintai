<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>kintai</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}" />

</head>

<body>
    <header>
        <div class="header">
            <div class="header__inner">
                <a class="header__logo" href="/">
                    <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" />
                </a>
            </div>




            <!-- 🛠️ ヘッダーメニュー -->
            <div class="header__menu">
                @if ($status === 'ended')
                <a class="header__menu-link" href="{{ route('attendance.list') }}">今月の出勤一覧</a>
                <a class="header__menu-link" href="{{ route('stamp_correction_request.index') }}">申請一覧</a>
                @else
                <a class="header__menu-link" href="{{ route('attendance.index') }}">勤怠</a>
                <a class="header__menu-link" href="{{ route('attendance.list') }}">勤怠一覧</a>
                <a class="header__menu-link" href="{{ route('stamp_correction_request.index') }}">申請</a>
                @endif
            </div>

            @auth
            <!-- ログイン中の表示（ログアウト） -->
            <a href="{{ route('logout') }}" class="btn"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                ログアウト
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            @endauth

            @guest
            <!-- 未ログインの表示（ログイン） -->
            <a href="{{ route('login') }}" class="btn">ログイン</a>
            @endguest


        </div>
    </header>
    <div class="container">
        {{-- 勤怠ステータス --}}
        <p class="status">
            @switch($status ?? '')
            @case('off')
            勤務外
            @break
            @case('working')
            出勤中
            @break
            @case('on_break')
            休憩中
            @break
            @case('ended')
            退勤済
            @break
            @default
            -
            @endswitch
        </p>

        {{-- 日時 --}}
        <p class="date">{{ $date }}</p>
        <p class="time">{{ $time }}</p>

        {{-- 状態別ボタン --}}
        <div class="button-group">
            @if ($status === 'off')
            <form method="POST" action="{{ route('attendance.action') }}">
                @csrf
                <input type="hidden" name="action" value="startWork">
                <button type="submit" class="attendance-button start" aria-label="出勤打刻ボタン">出勤</button>
            </form>
            @elseif ($status === 'working')
            <form method="POST" action="{{ route('attendance.action') }}" style="display:inline-block;">
                @csrf
                <input type="hidden" name="action" value="afterWork">
                <button type="submit" class="attendance-button end" aria-label="退勤ボタン">退勤</button>
            </form>
            <form method="POST" action="{{ route('attendance.action') }}" style="display:inline-block;">
                @csrf
                <input type="hidden" name="action" value="startBreak">
                {{-- 休憩入ボタン --}}
                <button type="submit" class="attendance-button white-button" aria-label="休憩開始ボタン">休憩入</button>
            </form>
            @elseif ($status === 'on_break')
            <form method="POST" action="{{ route('attendance.action') }}">
                @csrf
                <input type="hidden" name="action" value="endBreak">
                <button type="submit" class="attendance-button white-button" aria-label="休憩終了ボタン">休憩戻</button>
            </form>
            @elseif ($status === 'ended')
            <p class="message">お疲れ様でした。</p>
            @endif
        </div>
    </div>

</body>

</html>