<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>住所変更</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/address_edit.css') }}" />
</head>

<body>
    <header>
        <header class="header">
            <div class="header__inner">
                <a class="header__logo" href="/">
                    <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" />
                </a>
            </div>
        </header>
    </header>

    <div class="container">
        <h1>配送先住所変更</h1>

        <form action="{{ route('purchase.updateAddress', $item->id) }}" method="POST">
            @csrf
            <!-- 住所変更フォーム -->
            <div class="form-group">
                <label for="postal_code">郵便番号</label>
                <input type="text" name="postal_code" id="postal_code" class="form-control" value="{{ $userAddress->postal_code }}" required>
            </div>

            <div class="form-group">
                <label for="address">住所</label>
                <input type="text" name="address" id="address" class="form-control" value="{{ $userAddress->address }}" required>
            </div>

            <div class="form-group">
                <label for="building">建物名</label>
                <input type="text" name="building" id="building" class="form-control" value="{{ $userAddress->building }}">
            </div>

            <button type="submit" class="btn btn-success">住所を更新する</button>
        </form>
    </div>
</body>

</html>