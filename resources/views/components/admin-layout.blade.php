<!DOCTYPE html>
<html lang="en" 
    x-data="{ 
        darkMode: localStorage.getItem('darkMode') === 'true',
        sidebarOpen: true
    }" 
    x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" 
    x-bind:class="{ 'dark': darkMode }"
    class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin Panel | Ride Nusa' }}</title>
    
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
    
    {{-- Original Vite Config --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 text-gray-800 dark:bg-dark-base dark:text-gray-200 transition-colors duration-300 font-sans flex font-normal">

    {{-- Sidebar --}}
    @include('admin.layouts.sidebar')

    {{-- Main Content Area --}}
    <div class="flex-1 flex flex-col min-h-screen transition-all duration-300"
         :class="sidebarOpen ? 'ml-64' : 'ml-0'">
         
        {{-- Navbar --}}
        @include('admin.layouts.navbar')

        {{-- Content --}}
        <main class="flex-1 p-6 z-0">
            {{ $slot }}
        </main>
    </div>

    {{-- Universal Table / Data Search Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const globalSearchInput = document.getElementById('global-table-search');
            if (globalSearchInput) {
                const localSearchInput = document.getElementById('search-input');
                
                if (localSearchInput) {
                    globalSearchInput.value = localSearchInput.value;
                    
                    globalSearchInput.addEventListener('input', function() {
                        localSearchInput.value = this.value;
                        localSearchInput.dispatchEvent(new Event('input'));
                        localSearchInput.dispatchEvent(new Event('keyup'));
                    });
                    
                    const localContainer = localSearchInput.closest('.mb-6') || localSearchInput.closest('div');
                    if (localContainer && localContainer !== document.body) {
                        localContainer.style.display = 'none';
                    }
                } else {
                    globalSearchInput.addEventListener('input', function() {
                        const query = this.value.toLowerCase().trim();
                        const tables = document.querySelectorAll('table');
                        tables.forEach(table => {
                            const rows = table.querySelectorAll('tbody tr');
                            rows.forEach(row => {
                                const cells = row.querySelectorAll('td');
                                if (cells.length === 1 && (cells[0].textContent.includes('tidak ditemukan') || cells[0].textContent.includes('No data') || cells[0].colSpan > 1)) {
                                    return;
                                }
                                
                                const text = row.textContent.toLowerCase();
                                if (text.includes(query)) {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                            });
                        });
                    });
                }
            }
        });
    </script>
</body>
</html>
