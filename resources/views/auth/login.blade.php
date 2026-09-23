<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - BrgyHoops</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }

        .container {
            width: 400px;
            margin: 100px auto;
            background: white;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            width: 100%;
            padding: 13px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #1d4ed8;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #2563eb;
        }
    </style>
</head>

<body>

    @include('components.alert')

    <div class="container">

        <h1>Login to BrgyHoops</h1>

        <form action="/login" method="POST">

            @csrf

            @include('components.form-input', [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'placeholder' => 'Enter your email',
                'required' => true
            ])

            @include('components.form-input', [
    'name' => 'password',
    'label' => 'Password',
    'type' => 'password',
    'placeholder' => 'Enter your password',
    'required' => true
])
            

            <button type="submit">
                Login
            </button>

        </form>

        <a class="back" href="/">
            ← Back to Home
        </a>

    </div>

</body>
</html>