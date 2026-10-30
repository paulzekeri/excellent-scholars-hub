@php
    $year = date('Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Excellent Scholars Hub') - ESH LMS</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-700 via-blue-600 to-blue-500 font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="mx-auto h-20 w-auto">
                <p class="text-blue-50 mt-2 text-sm">Excellent Scholars Hub</p>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-800 p-4 rounded" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-amber-100 border-l-4 border-amber-500 text-amber-900 p-4 rounded" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded" role="alert">
                    <p class="font-semibold mb-1">Please fix the following:</p>
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-2xl p-8">
                @yield('content')
            </div>

            <p class="mt-6 text-center text-xs text-blue-100">&copy; {{ $year }} Excellent Scholars Hub</p>
        </div>
    </div>
</body>
</html>
