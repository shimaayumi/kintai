<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>kintai</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin/attendance/show.css') }}" />

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

        <h1 class="attendance-details-title">勤怠詳細</h1>

        <form method="POST" action="{{ route('admin.attendance.update', $attendance->id) }}" class="attendance-form">
            @csrf
            @method('PUT')
            <div class="container">
                <ul class="attendance-form-list">
                    <li class="attendance-item">
                        <label class="label">名前</label>
                        <span class="name">{{ $attendance->user->name ?? '不明' }}</span>
                    </li>


                    <li class="attendance-item">
                        <label class="label">日付</label>
                        <span class="date-display">
                            <span class="year">{{ \Carbon\Carbon::parse($attendance->started_at)->format('Y') }}年</span>
                            <span class="date">
                                {{ \Carbon\Carbon::parse($attendance->started_at)->format('n月j日') }}
                            </span>
                        </span>
                    </li>

                    {{-- 出勤・退勤 --}}
                    <li class="attendance-item">
                        <div class="form-row">
                            <label class="label">出勤・退勤</label>
                            <div class="input-field">
                                <div class="time-range">
                                    <input type="time" name="started_at" class="input-time"
                                        value="{{ old('started_at', optional($correctionRequest?->started_at ?? $attendance->started_at)->format('H:i')) }}">
                                    <span class="range-separator">〜</span>
                                    <input type="time" name="ended_at" class="input-time"
                                        value="{{ old('ended_at', optional($correctionRequest?->ended_at ?? $attendance->ended_at)->format('H:i')) }}">
                                </div>
                                @error('started_at')<div class="error">{{ $message }}</div>@enderror
                                @error('ended_at')<div class="error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </li>

                    {{-- 休憩 --}}
                    @php
                    // 修正申請があればそれを使う（配列に変換）
                    $breaks = old('breaks')
                    ?? ($correctionRequest && $correctionRequest->correctionBreaks->isNotEmpty()
                    ? $correctionRequest->correctionBreaks->all() // all() で配列に変換
                    : $attendance->breakTimes->all());
                    @endphp

                    @foreach ($breaks as $index => $break)
                    <li class="attendance-item">
                        <div class="form-row">
                            <label class="label">休憩{{ $index + 1 }}</label>
                            <div class="input-field">
                                <div class="time-range">
                                    {{-- $breakが配列の場合 --}}
                                    @php
                                    $breakStarted = is_array($break)
                                    ? ($break['break_started_at'] ?? null)
                                    : (optional($break->break_started_at)->format('H:i'));
                                    $breakEnded = is_array($break)
                                    ? ($break['break_ended_at'] ?? null)
                                    : (optional($break->break_ended_at)->format('H:i'));
                                    @endphp
                                    <input type="time" name="breaks[{{ $index }}][break_started_at]" class="input-time"
                                        value="{{ old("breaks.$index.break_started_at", $breakStarted) }}">
                                    <span class="range-separator">〜</span>
                                    <input type="time" name="breaks[{{ $index }}][break_ended_at]" class="input-time"
                                        value="{{ old("breaks.$index.break_ended_at", $breakEnded) }}">
                                </div>
                                @error("breaks.$index.break_started_at")<div class="error">{{ $message }}</div>@enderror
                                @error("breaks.$index.break_ended_at")<div class="error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </li>
                    @endforeach

                    {{-- 備考 --}}
                    <li class="attendance-item note-item">
                        <div class="form-row">
                            <label class="label">備考</label>
                            <div class="input-field">
                                <textarea name="note" class="input-textarea" rows="4">{{ old('note', $correctionRequest->note ?? $attendance->note) }}</textarea>
                                @error('note')<div class="error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            @if (optional($correctionRequest)->approval_status === \App\Models\CorrectionRequest::APPROVAL_APPROVED)
            <p class="approved-message">承認済みのため修正できません。</p>
            @else
            <button type="submit" class="submit-button">修正</button>
            @endif
        </form>

    </main>

</html>