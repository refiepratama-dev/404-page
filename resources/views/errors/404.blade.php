<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Actor:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Actor', sans-serif;
            margin: 0;
            background: #ffffff;
        }

        .video-wrap {
            position: fixed;
            top: 40px;
            left: 12px;
            right: 12px;
            bottom: 40px;
            z-index: -1;
            border-radius: 24px;
            overflow: hidden;
        }

        .video-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 0;
        }

        .btn-white {
            transition: all 0.2s ease;
        }

        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        }

        .btn-white:active {
            transform: translateY(0);
        }

        .fade-in {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .fade-in:nth-child(2) { animation-delay: 0.1s; }
        .fade-in:nth-child(3) { animation-delay: 0.2s; }
        .fade-in:nth-child(4) { animation-delay: 0.3s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .fade-in {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }

        @media (max-width: 640px) {
            .video-wrap {
                top: 20px;
                left: 8px;
                right: 8px;
                bottom: 20px;
                border-radius: 16px;
            }
        }
    </style>
</head>
<body class="text-white min-h-screen relative overflow-hidden selection:bg-indigo-500 selection:text-white">

    <div class="video-wrap">
        <video class="video-bg" autoplay loop muted playsinline>
            <source src="{{ asset('videos/404-bg.mp4') }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="overlay"></div>
    </div>

    <main class="relative z-10 min-h-screen w-full pl-6 pr-6 md:pl-20 md:pr-20 text-left flex flex-col justify-end">
        <div class="max-w-lg fade-in mb-8 md:mb-20">
            <h1 class="text-6xl md:text-8xl font-bold text-white mb-10 tracking-tight">
                404
            </h1>

            <h2 class="text-xl md:text-2xl font-bold text-white mb-6">
                Page Not Found
            </h2>

            <p class="text-base text-gray-300 mb-12 leading-relaxed">
                The page you're looking for might have been moved or is no longer available.
            </p>

            <div class="flex gap-6 items-center">
                <a href="{{ route('home') }}"
                   class="btn-white group relative inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white border border-transparent text-gray-900 font-bold text-sm shadow-lg hover:bg-gray-100 transition-all"
                   style="min-width: 150px">
                    <span class="relative z-10 flex items-center gap-2">
                        Back to Home
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </span>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-full"></div>
                </a>
            </div>
        </div>
    </main>

</body>
</html>