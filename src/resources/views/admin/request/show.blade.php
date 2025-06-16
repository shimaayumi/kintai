<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title></title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin/request/show.css') }}" />

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
                <a class="header__menu-link" href="{{ route('stamp_correction_request.index', ['tab' => 'pending']) }}">申請一覧</a>
            </div>

            @auth('admin')
            <!-- 管理者ログイン中の表示 -->
            <a href="{{ route('admin.logout') }}" class="btn"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                ログアウト
            </a>
            <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            @endauth
        </div>
    </header>
    <div class="attendance-detail-container">
        <h1 class="attendance-details-title">勤怠詳細</h1>

        <div class="attendance-box">
            <table class="attendance-table">
                <tr>
                    <th>名前</th>
                    <td>{{ $correctionRequest->attendance->user->name ?? '未設定' }}</td>
                </tr>
                <tr>
                    @php
                    $attendance = $correctionRequest->attendance;
                    $workDate = optional($attendance)->work_date
                    ? \Carbon\Carbon::parse($attendance->work_date)
                    : null;
                    @endphp
                    <th>日付</th>

                    <td>
                        <span class="date-year">{{ $workDate ? \Carbon\Carbon::parse($workDate)->format('Y年') : '-' }}</span>
                        <span class="date-rest">{{ $workDate ? \Carbon\Carbon::parse($workDate)->format('n月j日') : '' }}</span>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        {{ $correctionRequest->started_at ? $correctionRequest->started_at->format('H:i') : ($correctionRequest->attendance?->started_at?->format('H:i') ?? '-') }}
                        <span class="time-separator">〜</span>
                        {{ $correctionRequest->ended_at ? $correctionRequest->ended_at->format('H:i') : ($correctionRequest->attendance?->ended_at?->format('H:i') ?? '-') }}
                    </td>
                </tr>
                {{-- 修正申請の休憩情報を優先表示 --}}
                @php
                $breaks = $correctionRequest->correctionBreaks->isNotEmpty()
                ? $correctionRequest->correctionBreaks
                : $correctionRequest->attendance->breakTimes;
                @endphp

                @foreach ($breaks as $breakTime)
                <tr>
                    <th>休憩{{ $loop->iteration }}</th>
                    <td>
                        <span class="break-start">
                            {{ $breakTime->break_started_at ? \Carbon\Carbon::parse($breakTime->break_started_at)->format('H:i') : '-' }}
                        </span>
                        <span class="time-separator">〜</span>
                        <span class="break-end">
                            {{ $breakTime->break_ended_at ? \Carbon\Carbon::parse($breakTime->break_ended_at)->format('H:i') : '-' }}
                        </span>
                    </td>
                </tr>
                @endforeach
                <tr>
                    <th>備考</th>
                    <td>{{ $correctionRequest->note ?? '' }}</td>
                </tr>
            </table>
        </div>
        <div class="btn-container">
            @if ($correctionRequest->approval_status === \App\Models\CorrectionRequest::APPROVAL_APPROVED)
            <span class="approved-label">承認済み</span>
            @else
            <form action="{{ route('admin.stamp_correction_request.approve', ['id' => $correctionRequest->id]) }}" method="POST">
                @csrf
                <button type="submit" class="btn-primary">承認</button>
            </form>
            @endif
        </div>
</body>

</html>