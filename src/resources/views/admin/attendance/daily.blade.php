<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title></title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin/attendance/daily.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
                <a class="header__menu-link" href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                <a class="header__menu-link" href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                @if(Auth::guard('admin')->check())
                <a class="header__menu-link" href="{{ route('stamp_correction_request.index') }}">申請一覧</a>
                @elseif(Auth::guard('web')->check())
                <a class="header__menu-link" href="{{ route('stamp_correction_request.index') }}">申請</a>
                @endif
            </div>
            @auth
            <!-- ログイン中の表示（ログアウト） -->
            <a href="{{ route('admin.logout') }}" class="btn"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                ログアウト
            </a>
            <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            @endauth

            @guest
            <!-- 未ログインの表示（ログイン） -->
            <a href="{{ route('login') }}" class="btn">ログイン</a>
            @endguest



        </div>
    </header>
    <main>
        <div class="container">
            <h2 class="date-title">
                {{ isset($date) ? \Carbon\Carbon::parse($date)->format('Y年n月j日').'の勤怠' : '' }}
            </h2>

            <div class="date-nav">
                <a href="{{ isset($date) ? route('admin.attendance.list', ['date' => \Carbon\Carbon::parse($date)->copy()->subDay()->format('Y-m-d')]) : '#' }}" class="nav-button">← 前日</a>

                <div class="date-picker-wrapper">
                    <i class="fa-solid fa-calendar calendar-icon" aria-hidden="true"></i>
                    <input
                        type="date"
                        id="date"
                        name="date"
                        class="selected-date-input"
                        value="{{ isset($date) ? \Carbon\Carbon::parse($date)->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d') }}"
                        aria-label="日付を選択" />
                </div>

                <a href="{{ route('admin.attendance.list', ['date' => isset($date) ? \Carbon\Carbon::parse($date)->copy()->addDay()->format('Y-m-d') : \Carbon\Carbon::now()->addDay()->format('Y-m-d')]) }}" class="nav-button">翌日 →</a>
            </div>

            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance['user_name'] }}</td>
                        <td>{{ $attendance['started_at'] ?? '-' }}</td>
                        <td>{{ $attendance['ended_at'] ?? '-' }}</td>
                        <td>
                            @if ($attendance['correction_requested'])
                            {{ $attendance['correction_break_total'] ?? '-' }}
                            @else
                            {{ $attendance['break_time'] ?? '-' }}
                            @endif
                        </td>
                        <td>{{ $attendance['work_time'] ?? '-' }}</td>
                        <td><a class="details-link" href="{{ route('attendance.show', $attendance['id']) }}">詳細</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">勤怠データがありません。</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
    <script>
        document.getElementById('date').addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
                window.location.href = `/admin/attendance/list?date=${selectedDate}`;
            }
        });
        document.querySelector('.calendar-icon').addEventListener('click', function() {
            document.getElementById('date').showPicker(); // 一部ブラウザのみ対応
        });
    </script>

</html>