<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>fleamarket</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/purchase.css') }}" />
    <script src="https://js.stripe.com/v3/"></script>

</head>

<body>
    <header>
        <div class="header">
            <div class="header__inner">
                <a class="header__logo" href="/">
                    <img src="{{ asset('images/logo.svg') }}" alt="ロゴ">
                </a>
            </div>
            <form action="{{ route('items.index') }}" method="GET" class="search-form">
                @csrf
                <input type="text" name="keyword" value="{{ old('keyword', request('keyword')) }}" placeholder="なにをお探しですか？">
                <input type="hidden" name="page" value="{{ request('page', 'all') }}">
            </form>
            <div class="header__menu">
                @if(Auth::check())
                <a href="{{ route('logout') }}" class="btn" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">ログアウト</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                @if(isset($item) && $item->user)
                <a href="{{ route('mypage.show') }}" class="btn">マイページ</a>
                @else
                <p>商品情報がありません。</p>
                @endif
                <a href="{{ route('sell') }}" class="btn btn-outlet">出品</a>
                @else
                <a href="{{ route('auth.login') }}" class="btn">ログイン</a>
                <a href="{{ route('auth.register') }}" class="btn">会員登録</a>
                @endif
            </div>
        </div>
    </header>

    <div class="container">

        <!-- 左側の商品情報・配送先情報 -->
        <div class="left-column">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="item">

                        @if(isset($item))

                        <img src="{{ asset('storage/images/' . $item->images->first()->item_image) }}" alt="{{ $item->name }}">
                        @else
                        <div class="no-image">商品画像がありません</div>
                        @endif
                        <div class="item-info">
                            <div class="item-name">
                                {{ $item->item_name ?? '商品名がありません' }}
                            </div>
                            <div class="item-price">
                                <strong></strong>¥ {{ isset($item) ? number_format($item->price)  : '価格情報がありません' }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- 支払い方法選択 -->
            <div class="form-group">
                <label class="payment_method">支払い方法</label>
                <select name="payment_method" id="payment_method" class="form-control custom-select" required onchange="displaySelectedPaymentMethod()">
                    <option value="" disabled selected>選択してください</option>
                    <option value="convenience_store">コンビニ支払い</option>
                    <option value="credit_card">カード支払い</option>


                </select>
            </div>
            <!-- 配送先情報の表示 -->

            <div class="form-address">
                <div class="address-method">
                    <label class="address-method_ttl">配送先</label>


                    <!-- 住所変更ボタン -->
                    <div class="address-method__button">
                        @if(isset($item))
                        <a href="{{ route('address.change', $item->id) }}" class="btn btn-primary">変更する</a>
                        @endif
                    </div>
                </div>


                <p class="address-postal-code"><strong>〒</strong>
                    {{ !empty($user->address) && !empty($user->address->postal_code) ? $user->address->postal_code : '未設定' }}
                </p>

                <div class="address-wrapper">
                    <p class="address-detail"><strong></strong>
                        {{ !empty($user->address) && !empty($user->address->address) ? $user->address->address : '未設定' }}
                    </p>
                    <p class="address-building"><strong></strong>
                        {{ !empty($user->address) && !empty($user->address->building) ? $user->address->building : '未設定' }}
                    </p>
                </div>
            </div>

        </div>

        <!-- 右側の価格と購入ボタン -->
        <div class="right-column">
            @if (isset($item))
            <p class="payment-amount"><strong>商品代金 </strong><span class="price">¥{{ number_format($item->price) }}</span></p>
            @endif

            <!-- 支払い方法の表示 -->

            <div class="payment-method-wrapper">
                <label class="payment-method-label">支払い方法</label>
                <div id="payment_method_display" class="payment-method"></div>
            </div>



            <!-- 購入確認フォーム -->
            <button id="checkout-button" class="btn btn-primary" onclick="return checkPaymentMethod()">購入する</button>

        </div>

    </div>

    <!-- 購入失敗時にエラーメッセージを表示 -->
    @if(isset($error))
    <div class="error-message">
        <p>{{ $error }}</p>
    </div>
    @endif
    </div>









    <script>
        document.getElementById('checkout-button').addEventListener('click', function(event) {
            // 支払い方法をチェック
            const paymentMethod = document.getElementById('payment_method').value;
            console.log("選択された支払い方法:", paymentMethod); // ここで確認
            if (!paymentMethod) {
                alert('支払い方法を選択してください。');
                event.preventDefault(); // フォームの送信（またはイベントのデフォルト動作）をキャンセル
                return;
            }

            // 住所情報を取得
            const address = {
                postal_code: '{{ $user->address->postal_code ?? "" }}',
                address: '{{ $user->address->address ?? "" }}',
                building: '{{ $user->address->building ?? "" }}',
            };

            // 住所情報が空の場合、エラーメッセージを表示
            if (!address.postal_code || !address.address || !address.building) {
                alert('住所情報が不足しています。すべての項目を入力してください。');
                return;
            }

            // 送信するデータを作成
            var dataToSend = {
                payment_method: paymentMethod,
                address: address,
                name: '{{ $item->item_name }}' // 商品名など、必要なパラメータを追加
            };

            // item_id をURLに渡す
            const itemId = '{{ $item->id }}'; // 商品IDを取得

            // データが正しいか確認
            console.log('送信するデータ:', dataToSend);

            // fetchでPOSTリクエストを送る
            fetch(`/purchase/${itemId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}', // CSRFトークンをヘッダーに追加
                    },
                    body: JSON.stringify(dataToSend) // 定義したdataToSendを送信
                })
                .then(response => response.json()) // レスポンスをJSONとして処理
                .then(data => {
                    console.log(data); // レスポンス内容をコンソールに表示
                    if (data.url) {
                        window.location.href = data.url; // 成功時にCheckout画面にリダイレクト
                    } else {
                        alert(data.error || 'エラーが発生しました'); // エラーメッセージ表示
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('ネットワークエラーが発生しました。');
                });


            function displaySelectedPaymentMethod() {
                const paymentMethod = document.getElementById('payment_method').value;
                const displayElement = document.getElementById('payment_method_display');

                let methodText = '';
                if (paymentMethod === 'convenience_store') {
                    methodText = 'コンビニ支払い';
                } else if (paymentMethod === 'credit_card') {
                    methodText = 'カード支払い';
                }

                displayElement.textContent = methodText;
            }

            function checkPaymentMethod() {
                const paymentMethod = document.getElementById('payment_method').value;
                if (!paymentMethod) {
                    alert('支払い方法を選択してください。');
                    return false;
                }

                const address = {
                    postal_code: '{{ $user->address->postal_code ?? "" }}',
                    address: '{{ $user->address->address ?? "" }}',
                    building: '{{ $user->address->building ?? "" }}'
                };

                if (!address.postal_code || !address.address) {
                    alert('配送先情報を入力してください。');
                    return false;
                }


                return true;
            }
        });

        function displaySelectedPaymentMethod() {
            const selectElement = document.getElementById('payment_method');
            const selectedOption = selectElement.options[selectElement.selectedIndex].text;

            // 選択された支払い方法を画面に表示する場合（任意）
            const displayElement = document.getElementById('selected-payment-method');
            if (displayElement) {
                displayElement.textContent = selectedOption;
            }

            console.log('選択された支払い方法:', selectedOption);
        }
    </script>
</body>


</html>