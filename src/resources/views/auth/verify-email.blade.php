@extends('layouts.app')

@section('content')
<div class="container">
    <div class="alert alert-info">
        <p><strong>登録していただいたメールアドレスに認証メールを送付しました。</strong></p>
        <p>メール認証を完了してください。</p>
    </div>

    <!-- メール認証ボタン -->
    <div class="form-group">
        <form action="{{ route('verification.resend') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">認証メールを再送する</button>
        </form>
    </div>

    <div class="form-group mt-3">
        <a href="{{ route('verification.notice') }}" class="btn btn-success">認証はこちらから</a>
    </div>
</div>
@endsection