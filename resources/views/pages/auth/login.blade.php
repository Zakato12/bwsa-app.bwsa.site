<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BWASA - Login</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    @extends('layouts.partials.head')
</head>
<body>
    
    <div class="login-container">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <div class="login-card">
            <div class="login-header">
                <h2>Sign In</h2>
                <p>Enter your credentials to access your account</p>

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

            </div>
             
            <form class="login-form" id="loginForm" method="post" action="{{ url('/login') }}">
                {{ csrf_field() }}
                <div class="form-group">
                    <div class="input-wrapper">
                        <input
                            class="@error('username') is-invalid @enderror"  
                            id="username" 
                            name="username" 
                            required 
                            autocomplete="username"
                            >
                        <label for="username">Username</label>
                    </div>
                    @error('username')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="input-wrapper password-wrapper">
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <label for="password">Password</label>
                        <button 
                            type="button" 
                            class="password-toggle @error('password') is-invalid
                            @enderror" 
                            id="passwordToggle" 
                            aria-label="Toggle password visibility">
                            <span class="eye-icon"></span>
                        </button>
                    </div>
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-options">
                    <label class="remember-wrapper">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="checkbox-label">
                            <span class="checkmark"></span>
                            Remember me
                        </span>
                    </label>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="login-btn">
                    <span class="btn-text">Sign In</span>
                    <span class="btn-loader"></span>
                </button>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/form-utils.js') }}"></script>
    <script src="{{asset('js/login.js')}}"></script>
</body>
</html>