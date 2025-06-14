<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title></title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/attendance/show.css') }}" />

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
            <a href=" {{ route('logout') }}" class="btn"
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


        <h1 class="attendance-details-title">勤怠詳細</h1>

        <div class="container">
            <form method="POST" action="{{ route('user.attendance.update', $attendance->id) }}">
                @csrf
                @method('PUT')

                <ul class="attendance-form-list">
                    <li class="attendance-item">
                        <div class="form-row">
                            <label class="label">名前</label>
                            <div class="input-field">
                                <span class="name">{{ $attendance->user->name }}</span>
                            </div>
                        </div>
                    </li>

                    <li class="attendance-item">
                        <div class="form-row">
                            <label class="label">日付</label>
                            <div class="input-field">
                                <div class="date-display">
                                    <span class="year-part">{{ \Carbon\Carbon::parse($attendance->started_at)->format('Y年') }}</span>
                                    <span class="day-part">{{ \Carbon\Carbon::parse($attendance->started_at)->format('n月j日') }}</span>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="attendance-item">
                        <div class="form-row">
                            <label class="label">出勤・退勤</label>
                            <div class="input-field">
                                <div class="time-range">
                                    @php
                                    $startedAt = old('started_at', $correctionRequest ? \Carbon\Carbon::parse($correctionRequest->started_at)->format('H:i') : \Carbon\Carbon::parse($attendance->started_at)->format('H:i'));
                                    $endedAt = old('ended_at', $correctionRequest && $correctionRequest->ended_at ? \Carbon\Carbon::parse($correctionRequest->ended_at)->format('H:i') : ($attendance->ended_at ? \Carbon\Carbon::parse($attendance->ended_at)->format('H:i') : ''));
                                    @endphp

                                    <input type="time" name="started_at" class="input-time" value="{{ $startedAt }}">
                                    <span class="range-separator">〜</span>
                                    <input type="time" name="ended_at" class="input-time" value="{{ $endedAt }}">
                                </div>
                                @error('started_at')<div class="error">{{ $message }}</div>@enderror
                                @error('ended_at')<div class="error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </li>

                    @php
                    // old('breaks') があれば優先
                    $oldBreaks = old('breaks');

                    if ($oldBreaks) {
                    $breaks = $oldBreaks;
                    } else {
                    if (isset($correctionRequest) && $correctionRequest->correctionBreaks && $correctionRequest->correctionBreaks->isNotEmpty()) {
                    // 修正申請の休憩時間を使う
                    $breaks = $correctionRequest->correctionBreaks->map(function ($break) {
                    return [
                    'break_started_at' => $break->break_started_at ? \Carbon\Carbon::parse($break->break_started_at)->format('H:i') : '',
                    'break_ended_at' => $break->break_ended_at ? \Carbon\Carbon::parse($break->break_ended_at)->format('H:i') : '',
                    ];
                    })->toArray();
                    } else {
                    // 元の勤怠の休憩時間を使う
                    $breaks = $attendance->breakTimes->map(function ($break) {
                    return [
                    'break_started_at' => $break->break_started_at ? $break->break_started_at->format('H:i') : '',
                    'break_ended_at' => $break->break_ended_at ? $break->break_ended_at->format('H:i') : '',
                    ];
                    })->toArray();
                    }
                    }
                    @endphp

                    @foreach ($breaks as $index => $break)
                    <li class="attendance-item">
                        <div class="form-row">
                            <label class="label">休憩{{ $index + 1 }}</label>
                            <div class="input-field">
                                <div class="time-range">
                                    <input type="time" name="breaks[{{ $index }}][break_started_at]" class="input-time"
                                        value="{{ old("breaks.$index.break_started_at", $break['break_started_at']) }}">
                                    <span class="range-separator">〜</span>
                                    <input type="time" name="breaks[{{ $index }}][break_ended_at]" class="input-time"
                                        value="{{ old("breaks.$index.break_ended_at", $break['break_ended_at']) }}">
                                </div>
                                @error("breaks.$index.break_started_at")<div class="error">{{ $message }}</div>@enderror
                                @error("breaks.$index.break_ended_at")<div class="error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </li>
                    @endforeach

                    <li class="attendance-item">
                        <div class="form-row">
                            <label class="label">備考</label>
                            <div class="input-field">
                                {{-- 修正申請があればその値を優先 --}}
                                @php
                                $noteValue = old('note', $correctionRequest? $correctionRequest->note : $attendance->note);
                                @endphp

                                <textarea name="note" class="input-textarea" rows="4">{{ $noteValue }}</textarea>

                                @error('note')
                                <div class="error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </li>
                </ul>
        </div>

        <div class="form-footer">
            @if ($correctionRequest && $correctionRequest->approval_status === 'approved')
            <p class="status-message">承認済みのため、修正はできません。</p>
            @elseif ($correctionRequest && $correctionRequest->approval_status === 'pending')
            <p class="status-message">承認待ちのため、修正はできません。</p>
            @else
            <button type="submit" class="submit-button">修正</button>
            @endif

        </div>
        </form>
    </main>

</html>