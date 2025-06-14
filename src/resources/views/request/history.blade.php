<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>修正申請一覧（承認待ち）</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/requests/history.css') }}" />

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
                <a class="header__menu-link" href="{{ route('stamp_correction_request.index', ['tab' => 'pending']) }}">申請</a>
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

        <div class="container">
            <h2 class="page-title">申請一覧</h2>

            <div class="tab-menu">
                <a href="{{ route('stamp_correction_request.index', ['status' => 'pending']) }}"
                    class="tab {{ request('status', 'pending') === 'pending' ? 'active' : '' }}">
                    承認待ち
                </a>
                <a href="{{ route('stamp_correction_request.index', ['status' => 'approved']) }}"
                    class="tab {{ request('status') === 'approved' ? 'active' : '' }}">
                    承認済み
                </a>
            </div>
            @if ($requests->isEmpty())
            <p>現在、承認待ちの修正申請はありません。</p>
            @else
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $statusLabels = [
                    \App\Models\CorrectionRequest::APPROVAL_PENDING => '承認待ち',
                    \App\Models\CorrectionRequest::APPROVAL_APPROVED => '承認済み',
                    ];
                    @endphp
                    @foreach ($requests as $request)
                    <tr>
                        <td>
                            @if ($request->approval_status === \App\Models\CorrectionRequest::APPROVAL_PENDING)
                            承認待ち
                            @elseif ($request->approval_status === \App\Models\CorrectionRequest::APPROVAL_APPROVED)
                            承認済み
                            @else
                            {{ $request->status }}
                            @endif
                        </td>
                        <td>{{ $request->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->started_at)->format('Y/m/d') }}</td>
                        <td>{{ $request->note }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}</td>
                        <td>
                            @if ($request->attendance)
                            <a href="{{ route('attendance.show', ['id' => $request->attendance->id]) }}" class="details-link">詳細</a>
                            @else
                            <span class="text-muted">詳細なし</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </main>

</body>

</html>