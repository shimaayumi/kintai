<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                    <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" />
                </a>
            </div>

            <!-- 🛠️ 検索フォーム -->
            <form action="{{ route('index') }}" method="GET" class="search-form">
                @csrf
                <input type="text" name="keyword" value="{{ old('keyword', request('keyword')) }}" placeholder="なにをお探しですか？" />
                <input type="hidden" name="page" value="{{ request('page', 'all') }}" />
            </form>

            <!-- 🛠️ ヘッダーメニュー -->

            <a href="{{ route('logout') }}" class="btn" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">ログアウト</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            <a href="{{ route('mypage') }}" class="btn">マイページ</a>

            <a href="{{ route('sell') }}" class="btn btn-outlet">
                <span class="btn-text">出品</span>
            </a>


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
                                <strong><span class="currency">¥</span></strong>
                                {{ isset($item) ? number_format($item->price)  : '価格情報がありません' }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- 支払い方法選択 -->
            <div class="form-group">
                <label class="payment_method">支払い方法</label>
                <select name="payment_method" id="payment_method" class="form-control custom-select" onchange="displaySelectedPaymentMethod()">
                    <option value="" disabled selected>選択してください</option>
                    <option value="convenience_store">コンビニ支払い</option>
                    <option value="credit_card">カード支払い</option>


                </select>

                <div id="payment_method_error" class="error-message"></div>
            </div>
            <!-- 配送先情報の表示 -->

            <div class="form-address">
                <div class="address-method">
                    <label class="address-method_ttl">配送先</label>


                    <!-- 住所変更ボタン -->
                    <div class="address-method__button">
                        @if(isset($item))

                        <a href="{{ route('address.edit', ['item_id' => $item->id]) }}">変更する</a>
                        @endif


                        </a>
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
                <div id="address_error" class="error-message"></div>
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

            <button id="checkout-button" class="checkout-button" data-item-id="{{ $item->id }}">購入する</button>

        </div>

    </div>

   
    




    <script>
        // 支払い方法表示用関数
        function displaySelectedPaymentMethod() {
            const select = document.getElementById('payment_method');
            const selectedValue = select.value;
            let displayText = '';

            if (selectedValue === 'convenience_store') {
                displayText = 'コンビニ支払い';
            } else if (selectedValue === 'credit_card') {
                displayText = 'カード支払い';
            }

            // 選択された支払い方法を表示
            const displayElement = document.getElementById('payment_method_display');
            if (displayElement) {
                displayElement.textContent = displayText;
            }

            console.log('選択された支払い方法:', displayText);
        }

        // 購入ボタン押下時の処理
        document.querySelector('.checkout-button').addEventListener('click', function(event) {
            const paymentMethod = document.getElementById('payment_method').value;

            // エラーボックス取得
            const paymentErrorBox = document.getElementById('payment_method_error');
            const addressErrorBox = document.getElementById('address_error');

            // エラー内容初期化
            paymentErrorBox.textContent = '';
            addressErrorBox.textContent = '';

            let hasError = false;

            // 支払い方法のバリデーション
            if (!paymentMethod) {
                paymentErrorBox.textContent = '支払い方法を選択してください';
                hasError = true;
            }

            // 配送先のバリデーション
            const address = {
                postal_code: '{{ $user->address->postal_code ?? "" }}',
                address: '{{ $user->address->address ?? "" }}',
                building: '{{ $user->address->building ?? "" }}',
            };

            if (!address.postal_code || !address.address || !address.building) {
                addressErrorBox.textContent = '住所情報が不足しています';
                hasError = true;
            }

            if (hasError) {
                event.preventDefault();
                return;
            }

            const dataToSend = {
                payment_method: paymentMethod,
                address: address
            };

            const itemId = '{{ $item->id }}';

            fetch(`/purchase/${itemId}/checkout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify(dataToSend)
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw err;
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    window.location.href = data.url;
                })
                .catch(error => {
                    // 通信エラーやAPI側のバリデーションエラー
                    if (error.errors) {
                        if (error.errors.payment_method) {
                            paymentErrorBox.textContent = error.errors.payment_method.join(', ');
                        }
                        if (error.errors.address) {
                            addressErrorBox.textContent = error.errors.address.join(', ');
                        }
                    } else {
                        alert('エラーが発生しました: ' + (error.message || '不明なエラー'));
                    }
                });
        });
    </script>

</body>


</html>