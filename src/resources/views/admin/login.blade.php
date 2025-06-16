<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>kintai</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/admin/login.css') }}" />
</head>

<body>
  <header class="header">
    <div class="header__inner">
      <a class="header__logo" href="/">
        <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" />
      </a>


    </div>
  </header>

  <div class="login-form">
    <h2 class="login-form__heading content__heading">管理者ログイン</h2>
    <div class="login-form__inner">
      <form method="POST" action="{{ route('admin.login.post') }}">

        @csrf
        <div class="login-form__group">
          <label class="login-form__label" for="email">メールアドレス</label>
          <input class="login-form__input" type="email" name="email" id="email">
          <p class="register-form__error-message">
            @error('email')
            <span>{{ $message }}</span>
            @enderror
          </p>
        </div>
        <div class="login-form__group">
          <label class="login-form__label" for="password">パスワード</label>
          <input class="login-form__input" type="password" name="password" id="password">
          <p class="register-form__error-message">
            @error('password')
            <span>{{ $message }}</span>
            @enderror
          </p>
        </div>
        <input class="login-form__btn btn" type="submit" value="管理者ログインする">


      </form>




    </div>
  </div>
</body>

</html>