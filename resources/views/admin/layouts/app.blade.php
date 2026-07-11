<!DOCTYPE html>
<html lang="en" x-data="{ 
        darkMode: localStorage.getItem('darkMode') === 'true',
        sidebarOpen: true
    }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" x-bind:class="{ 'dark': darkMode }"
    class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin Panel | Ride Nusa' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo/logo_web_ridenusa_transparan.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#FFB51D',
                            light: '#ffc64f',
                            dark: '#e09f19',
                        },
                        dark: {
                            base: '#131222',
                            card: '#1c1b2f',
                            hover: '#29283f',
                        },
                        gray: {
                            custom: '#868689'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background-color: rgba(134, 134, 137, 0.4);
            border-radius: 10px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background-color: rgba(255, 181, 29, 0.3);
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 181, 29, 0.6);
        }
    </style>
</head>

<body
    class="bg-gray-50 text-gray-800 dark:bg-dark-base dark:text-gray-200 transition-colors duration-300 font-sans flex font-normal">

    {{-- Sidebar --}}
    @include('admin.layouts.sidebar')

    {{-- Main Content Area --}}
    <div class="flex-1 flex flex-col min-h-screen transition-all duration-300" :class="sidebarOpen ? 'ml-64' : 'ml-0'">

        {{-- Navbar --}}
        @include('admin.layouts.navbar')

        {{-- Content --}}
        <main class="flex-1 p-6 z-0">
            @if(View::hasSection('title'))
                <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-white transition-colors">@yield('title')</h1>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Auto-refresh data every 5 seconds without page reload (HTML Swapping) -->
    <script>
        setInterval(function() {
            // Skip refresh if user is typing or interacting with form inputs
            const activeEl = document.activeElement;
            if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'SELECT')) {
                return;
            }

            const searchInput = document.getElementById('global-table-search');
            const localSearchInput = document.getElementById('search-input');
            const searchVal = (searchInput ? searchInput.value : '') + (localSearchInput ? localSearchInput.value : '');
            
            if (searchVal.trim() !== '') {
                return;
            }

            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // 1. Swap table bodies
                const currentTables = document.querySelectorAll('table');
                const newTables = doc.querySelectorAll('table');
                currentTables.forEach((table, index) => {
                    if (newTables[index]) {
                        const currentTbody = table.querySelector('tbody');
                        const newTbody = newTables[index].querySelector('tbody');
                        if (currentTbody && newTbody && currentTbody.innerHTML !== newTbody.innerHTML) {
                            currentTbody.innerHTML = newTbody.innerHTML;
                        }
                    }
                });

                // 2. Swap stats numbers (dashboard cards)
                const currentStats = document.querySelectorAll('h2.text-3xl.font-bold');
                const newStats = doc.querySelectorAll('h2.text-3xl.font-bold');
                currentStats.forEach((stat, index) => {
                    if (newStats[index] && stat.textContent !== newStats[index].textContent) {
                        stat.innerHTML = newStats[index].innerHTML;
                    }
                });

                // 3. Swap notification alerts (dashboard notification lists)
                const currentNotifs = document.querySelectorAll('ul.space-y-3');
                const newNotifs = doc.querySelectorAll('ul.space-y-3');
                currentNotifs.forEach((notif, index) => {
                    if (newNotifs[index] && notif.innerHTML !== newNotifs[index].innerHTML) {
                        notif.innerHTML = newNotifs[index].innerHTML;
                    }
                });
            })
            .catch(err => console.warn('Auto-refresh error:', err));
        }, 5000);
    </script>
</body>

</html>