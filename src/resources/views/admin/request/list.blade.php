<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title></title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/admin/request/list.css') }}" />

</head>

<body>
    <header>
        <div class="header">
            <div class="header__inner">
                <a class="header__logo" href="/">
                    <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" />
                </a>
            </div>

            <!-- ヘッダーメニュー -->
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

    <main class="request-list-page">
        <div class="container">
            <h1 class="request-list-title">申請一覧</h1>

            {{-- タブ切り替えリンク --}}
            <div class="request-tabs">
                <a href="{{ route('stamp_correction_request.index', ['tab' => 'pending']) }}"
                    class="request-tab-button {{ request('tab', 'pending') === 'pending' ? 'active' : '' }}">
                    承認待ち
                </a>
                <a href="{{ route('stamp_correction_request.index', ['tab' => 'approved']) }}"
                    class="request-tab-button {{ request('tab') === 'approved' ? 'active' : '' }}">
                    承認済み
                </a>
            </div>

            <div class="request-table-wrapper">
                <table class="request-table">
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
                        $tab = request()->input('tab', 'pending');
                        $requests = $tab === 'approved' ? $approvedRequests : $pendingRequests;
                        @endphp

                        @forelse ($requests as $request)
                        <tr>
                            <td>{{ $tab === 'approved' ? '承認済み' : '承認待ち' }}</td>
                            <td>{{ $request->user->name ?? '未設定' }}</td>
                            <td>{{ \Carbon\Carbon::parse($request->started_at)->format('Y/m/d') }}</td>
                            <td>{{ $request->note }}</td>
                            <td>{{ $request->created_at->format('Y/m/d') }}</td>
                            <td>
                                @if ($request->attendance)
                                <a href="{{ route('admin.stamp_correction_request.approve', $request->attendance->id) }}" class="request-detail-link">詳細</a>
                                @else
                                N/A
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">データがありません</td>
                        </tr>
                        @endforelse
                       
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>