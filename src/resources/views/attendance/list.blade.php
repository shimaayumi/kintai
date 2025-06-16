<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>kintai</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}" />
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
                <a class="header__menu-link" href="{{ route('attendance.index') }}">勤怠</a>
                <a class="header__menu-link" href="{{ route('attendance.list') }}">勤怠一覧</a>
                @if (Auth::check())
                <!-- 一般ユーザー ログイン中 -->
                <a class="header__menu-link" href="{{ route('stamp_correction_request.index', ['status' => 'pending']) }}">申請</a>
                @elseif (Auth::guard('admin')->check())
                <!-- 管理者ログイン中 -->
                <a class="header__menu-link" href="{{ route('stamp_correction_request.index', ['status' => 'pending']) }}">申請一覧</a>
                @else
                <!-- 未ログイン時はリンク非表示かログインページなど -->
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
    <main>
        <div class="request-list-page">
            <h2 class="request-list-title">勤怠一覧</h2>

            <div class="month-container">
                <a href="{{ route('attendance.list', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="month-nav">← 前月</a>

                <div class="date-picker-wrapper">

                    <form method="GET" action="{{ route('attendance.list') }}">
                        <input
                            type="month"
                            id="month"
                            name="month"
                            class="selected-date-input"
                            value="{{ $month->format('Y-m') }}"
                            aria-label="月を選択"
                            onchange="this.form.submit()">
                    </form>
                </div>
                <div class="month-nav current-month-wrapper">
                    <i class="fa-solid fa-calendar calendar-icon" aria-hidden="true"></i>
                    <span class="current-month">{{ $month->format('Y/m') }}</span>
                </div>

                <a href="{{ route('attendance.list', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="month-nav">翌月 →</a>
            </div>

            <div class="request-table-wrapper">
                <table class="request-table">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>出勤</th>
                            <th>退勤</th>
                            <th>休憩</th>
                            <th>合計</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dates as $date)
                        @php
                        $dateKey = $date->format('Y-m-d');
                        $attendance = $attendances[$dateKey] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $attendance['date'] ?? $date->format('m/d') . '(' . ['日','月','火','水','木','金','土'][$date->dayOfWeek] . ')' }}</td>
                            <td>{{ $attendance['started_at'] ?? '' }}</td>
                            <td>{{ $attendance['ended_at'] ?? '' }}</td>
                            <td>{{ $attendance['break'] ?? '' }}</td>
                            <td>{{ $attendance['work_time'] ?? '' }}</td>
                            <td>
                                @if (!empty($attendance['id']) && (Auth::guard('admin')->check() || Auth::guard('web')->check()))
                                <a href="{{ route('attendance.show', $attendance['id']) }}" class="request-detail-link">詳細</a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarIcon = document.querySelector('.calendar-icon');
            const monthInput = document.getElementById('month');

            if (calendarIcon && monthInput) {
                calendarIcon.addEventListener('click', function() {
                    monthInput.showPicker?.(); // 一部ブラウザのみ対応
                });
            }
        });
    </script>

</html>