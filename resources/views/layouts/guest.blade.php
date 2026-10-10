<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Osmena Hydraulic POS') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex">
            {{-- Branding panel with automotive / hydraulics illustration --}}
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-orange-900">
                <svg class="absolute inset-0 w-full h-full opacity-[0.07]" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>

                <div class="relative z-10 flex flex-col justify-between p-12 w-full">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-orange-500/20 border border-orange-400/30 text-orange-400 text-2xl">&#9881;</span>
                        <div>
                            <p class="text-white font-bold leading-tight">Osmena Hydraulic</p>
                            <p class="text-slate-400 text-sm leading-tight">Point of Sale</p>
                        </div>
                    </div>

                    <div>
                        {{-- Automotive + hydraulics illustration --}}
                        <svg viewBox="0 0 560 320" class="w-full max-w-lg mx-auto" xmlns="http://www.w3.org/2000/svg">
                            <ellipse cx="280" cy="285" rx="230" ry="18" fill="black" opacity="0.25" />

                            <!-- coiled hydraulic hose -->
                            <g transform="translate(40,150)" stroke="#fb923c" stroke-width="7" fill="none" stroke-linecap="round" opacity="0.85">
                                <circle cx="35" cy="35" r="30" />
                                <circle cx="35" cy="35" r="18" />
                                <path d="M35 5 V-15" />
                            </g>

                            <!-- gear -->
                            <g transform="translate(470,60)" fill="#94a3b8" opacity="0.9">
                                <path d="M30 0 34 0 36 8 44 11 51 6 55 10 50 17 53 25 61 27 61 31 53 33 50 41 55 45 51 49 44 44 36 47 34 55 30 55 28 47 20 44 13 49 9 45 14 41 11 33 3 31 3 27 11 25 14 17 9 10 13 6 20 11 28 8Z" />
                                <circle cx="32" cy="27" r="11" fill="#1e293b" />
                            </g>

                            <!-- pickup truck -->
                            <g transform="translate(60,120)">
                                <path d="M0 110 L0 70 Q0 60 12 60 L60 60 L95 25 Q102 18 114 18 L230 18 Q244 18 244 32 L244 60 L330 60 Q345 60 345 78 L345 110 Z"
                                      fill="#f97316" />
                                <path d="M108 24 L108 58 L232 58 L232 30 Q232 24 224 24 Z" fill="#0f172a" opacity="0.85" />
                                <path d="M65 60 L96 30 Q101 24 110 24 L110 60 Z" fill="#0f172a" opacity="0.85" />

                                <rect x="0" y="70" width="345" height="10" fill="#1e293b" opacity="0.4" />

                                <circle cx="60" cy="112" r="26" fill="#111827" />
                                <circle cx="60" cy="112" r="11" fill="#9ca3af" />
                                <circle cx="285" cy="112" r="26" fill="#111827" />
                                <circle cx="285" cy="112" r="11" fill="#9ca3af" />

                                <rect x="245" y="70" width="90" height="34" rx="3" fill="#ea580c" />
                                <line x1="245" y1="86" x2="335" y2="86" stroke="#c2410c" stroke-width="2" />

                                <circle cx="330" cy="55" r="6" fill="#fde68a" />
                            </g>

                            <!-- floating hydraulic fitting icon -->
                            <g transform="translate(390,190)" fill="none" stroke="#38bdf8" stroke-width="6" stroke-linecap="round" opacity="0.9">
                                <path d="M0 20 H40" />
                                <rect x="40" y="6" width="22" height="28" rx="4" fill="#38bdf8" stroke="none" />
                                <path d="M62 20 H90" />
                            </g>
                        </svg>
                    </div>

                    <div class="text-slate-300 text-sm max-w-sm">
                        <p class="font-semibold text-white mb-1">Point of Sale &amp; Inventory</p>
                        <p>Barcode checkout, stock ledger, sales &amp; profit reports, and backups &mdash; built for hydraulics and auto parts shops.</p>
                    </div>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center bg-gray-50 px-6 py-12">
                <div class="w-full sm:max-w-sm">
                    <div class="flex lg:hidden items-center justify-center gap-3 mb-8">
                        <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-orange-100 text-orange-600 text-xl">&#9881;</span>
                        <div class="text-center">
                            <p class="font-bold text-gray-800 leading-tight">Osmena Hydraulic</p>
                            <p class="text-gray-500 text-xs leading-tight">Point of Sale</p>
                        </div>
                    </div>

                    <div class="bg-white px-6 py-8 shadow-sm border border-gray-100 rounded-xl">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
