<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title></title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin/staff/monthly.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
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
                @if (Auth::guard('admin')->check())
                <!-- 管理者ログイン中 -->
                <a class="header__menu-link" href="{{ route('stamp_correction_request.index', ['status' => 'pending']) }}">申請一覧</a>
                @elseif (Auth::check())
                <!-- 一般ユーザー ログイン中 -->
                <a class="header__menu-link" href="{{ route('stamp_correction_request.index', ['status' => 'pending']) }}">申請</a>
                @else
                <!-- 未ログイン時はリンク非表示かログインページなど -->
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
            <h1 class="page-title">{{ $user->name }}さんの勤怠</h1>


            <div class="month-container">


                <a href="{{ route('admin.staff.monthly', $user->id) }}?year={{ $date->copy()->subMonth()->year }}&month={{ $date->copy()->subMonth()->month }}" class="month-nav">← 前月</a>

                <div class="date-picker-wrapper">
                    <i class="fa-solid fa-calendar calendar-icon" aria-hidden="true"></i>
                    <input
                        type="text"
                        id="date"
                        name="date"
                        class="selected-date-input"
                        value="{{ \Carbon\Carbon::parse($date)->format('Y/m') }}"
                        aria-label="月を選択">
                </div>

                <a href="{{ route('admin.staff.monthly', [
        'id' => $user->id,
        'year' => $date->copy()->addMonth()->year,
        'month' => $date->copy()->addMonth()->month
    ]) }}" class="month-nav">翌月 →</a>
            </div>




            <table class="table table-bordered attendance-table">
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
                    @for ($d = 1; $d <= $date->daysInMonth; $d++)
                        @php
                        $day = $date->copy()->day($d);
                        $attendance = $attendances->get($day->toDateString());
                        @endphp
                        <tr>
                            <td>{{ $daysWithWeekday[$day->toDateString()] ?? $day->format('m/d') }}</td>

                            {{-- 出勤時間 --}}
                            <td>
                                @php
                                $start = $attendance?->correctionRequest?->started_at ?? $attendance?->started_at;
                                @endphp
                                {{ $start ? \Carbon\Carbon::parse($start)->format('H:i') : '' }}
                            </td>

                            {{-- 退勤時間 --}}
                            <td>
                                @php
                                $end = $attendance?->correctionRequest?->ended_at ?? $attendance?->ended_at;
                                @endphp
                                {{ $end ? \Carbon\Carbon::parse($end)->format('H:i') : '' }}
                            </td>

                            {{-- 休憩時間 --}}
                            <td>
                                @php
                                $breakTimes = $attendance?->correctionRequest?->correctionBreaks ?? $attendance?->breakTimes;
                                $totalBreakMinutes = 0;
                                if ($breakTimes && $breakTimes->isNotEmpty()) {
                                $totalBreakMinutes = $breakTimes->sum(function ($break) {
                                // break_started_at と break_ended_at はCarbonインスタンスか文字列かに応じて調整
                                $start = \Carbon\Carbon::parse($break->break_started_at);
                                $end = \Carbon\Carbon::parse($break->break_ended_at);
                                return $start && $end ? $start->diffInMinutes($end) : 0;
                                });
                                }
                                $hours = floor($totalBreakMinutes / 60);
                                $minutes = $totalBreakMinutes % 60;
                                @endphp

                                {{ $totalBreakMinutes > 0 ? sprintf('%d:%02d', $hours, $minutes) : '' }}
                            </td>

                            {{-- 実働時間 --}}
                            <td>
                                @php
                                $workMinutes = null;

                                if ($start && $end) {
                                $startTime = \Carbon\Carbon::parse($start);
                                $endTime = \Carbon\Carbon::parse($end);
                                $workMinutes = $startTime->diffInMinutes($endTime);

                                // ここで休憩時間をセット（先に計算済みの$totalBreakMinutesを使う）
                                $breakMinutes = $totalBreakMinutes ?? 0;

                                // 実働から休憩時間を引く（マイナスにならないようにmaxで制御）
                                $actualWorkMinutes = max(0, $workMinutes - $breakMinutes);

                                $workHours = floor($actualWorkMinutes / 60);
                                $workRemainingMinutes = $actualWorkMinutes % 60;
                                }
                                @endphp

                                @if ($start && $end && isset($actualWorkMinutes))
                                {{ sprintf('%d:%02d', $workHours, $workRemainingMinutes) }}
                                @else
                                {{-- 空欄 --}}
                                @endif
                            </td>
                            
                            {{-- 詳細リンク --}}
                            <td>
                                @if (!empty($attendance))
                                <a href="{{ route('attendance.show', ['id' => $attendance->id]) }}" class="details-link">詳細</a>
                                @endif
                            </td>
                        </tr>
                        @endfor
                </tbody>
            </table>
            <div class="csv-export">
                <a href="{{ route('admin.staff.exportCsv', ['user' => $user->id, 'year' => $date->year, 'month' => $date->month]) }}" class="csv-btn">CSV出力</a>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>

    <script>
        // flatpickr の初期化は 1 回だけ
        flatpickr("#date", {
            locale: 'ja',
            dateFormat: "Y/m",
            defaultDate: "{{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}",
            plugins: [
                new monthSelectPlugin({
                    shorthand: false, // ← 「1月」「2月」などにする
                    dateFormat: "Y/m",
                    altFormat: "Y/m",
                    theme: "light"
                })
            ],
            onChange: function(selectedDates, dateStr, instance) {
                if (dateStr) {
                    const [year, month] = dateStr.split('/');
                    const userId = {
                        {
                            $user - > id
                        }
                    };
                    if (year && month) {
                        window.location.href = `/admin/attendance/staff/${userId}?year=${year}&month=${month}`;

                    }
                }
            }
        });

        // カレンダーアイコンで日付入力欄にフォーカス
        document.querySelector('.calendar-icon').addEventListener('click', function() {
            document.getElementById('date').focus();
        });
    </script>
</body>

</html>