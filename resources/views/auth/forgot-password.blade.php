@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
    <div class="container">
        <div class="auth-wrap">
            <div class="card card-ring auth-card">
                <div class="section-kicker">Account Recovery</div>

                <h1>Forgot <span>Password?</span></h1>

                <p class="auth-description">
                    Enter your email address and we will send you
                    instructions to reset your password.
                </p>

                @include('partials.flash')

                <form action="#" method="POST">
                    @csrf

                    <div class="field">
                        <label for="email">
                            <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            required
                        >
                    </div>

                    <button type="submit" class="button button-primary button-block" style="margin-top: 22px;">
                        Send Reset Link
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <a class="auth-back" href="/login">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Back to Login
                </a>
            </div>
        </div>
    </div>
@endsection
