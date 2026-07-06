<head>
    @vite('resources/css/app.css')
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ride Nusa')</title>

    <link rel="icon" type="image/png" href="{{ asset('img/logo/logo_web_ridenusa_transparan.png') }}" />

    <!-- fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
        rel="stylesheet">


    <link
        href="https://fonts.googleapis.com/css2?family=Inter+Tight:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
        rel="stylesheet">
    <!-- Core CSS -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/assets/css/animate.min.css" />
    <link rel="stylesheet" href="/assets/css/custom-animate.css" />
    <link rel="stylesheet" href="/assets/css/swiper.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="/assets/css/font-awesome-all.css" />
    <link rel="stylesheet" href="/assets/css/jarallax.css" />
    <link rel="stylesheet" href="/assets/css/jquery.magnific-popup.css" />
    <link rel="stylesheet" href="/assets/css/flaticon.css" />
    <link rel="stylesheet" href="/assets/css/owl.carousel.min.css" />
    <link rel="stylesheet" href="/assets/css/owl.theme.default.min.css" />
    <link rel="stylesheet" href="/assets/css/nice-select.css" />
    <link rel="stylesheet" href="/assets/css/jquery-ui.css" />
    <link rel="stylesheet" href="/assets/css/aos.css" />
    <link rel="stylesheet" href="/assets/css/odometer.min.css" />
    <link rel="stylesheet" href="/assets/css/timePicker.css" />

    <!-- Module CSS -->
    <link rel="stylesheet" href="/assets/css/module-css/slider.css" />
    <link rel="stylesheet" href="/assets/css/module-css/footer.css" />
    <link rel="stylesheet" href="/assets/css/module-css/sliding-text.css" />
    <link rel="stylesheet" href="/assets/css/module-css/services.css" />
    <link rel="stylesheet" href="/assets/css/module-css/about.css" />
    <link rel="stylesheet" href="/assets/css/module-css/booking.css" />
    <link rel="stylesheet" href="/assets/css/module-css/counter.css" />
    <link rel="stylesheet" href="/assets/css/module-css/listing.css" />
    <link rel="stylesheet" href="/assets/css/module-css/video.css" />
    <link rel="stylesheet" href="/assets/css/module-css/pricing.css" />
    <link rel="stylesheet" href="/assets/css/module-css/popular-car.css" />
    <link rel="stylesheet" href="/assets/css/module-css/testimonial.css" />
    <link rel="stylesheet" href="/assets/css/module-css/faq.css" />
    <link rel="stylesheet" href="/assets/css/module-css/team.css" />
    <link rel="stylesheet" href="/assets/css/module-css/call.css" />
    <link rel="stylesheet" href="/assets/css/module-css/download-app.css" />
    <link rel="stylesheet" href="/assets/css/module-css/brand.css" />
    <link rel="stylesheet" href="/assets/css/module-css/blog.css" />
    <link rel="stylesheet" href="/assets/css/module-css/lets-talk.css" />
    <link rel="stylesheet" href="/assets/css/module-css/process.css" />
    <link rel="stylesheet" href="/assets/css/module-css/why-choose.css" />
    <link rel="stylesheet" href="/assets/css/module-css/gallery.css" />
    <link rel="stylesheet" href="/assets/css/module-css/page-header.css" />
    <link rel="stylesheet" href="/assets/css/module-css/error.css" />
    <link rel="stylesheet" href="/assets/css/module-css/shop.css" />
    <link rel="stylesheet" href="/assets/css/module-css/contact.css" />

    <!-- Template CSS -->
    <link rel="stylesheet" href="/assets/css/style.css" />
    <link rel="stylesheet" href="/assets/css/responsive.css" />

    <style>
        /* Fix spacing for motorcycle info items */
        .listing-one__meta li {
            display: flex !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            gap: 5px !important;
        }
        .listing-one__meta li .text p {
            margin: 0 !important;
            white-space: nowrap !important;
        }
        @media (max-width: 1199px) {
            .main-header .container {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                width: 100% !important;
            }
            .main-menu__middle-box {
                margin: 0 !important;
            }
            .mobile-nav__toggler {
                display: block !important;
                color: #FFB51D !important;
                font-size: 28px !important;
                padding: 10px;
                z-index: 999;
            }
            .logo-desktop {
                display: none !important;
            }
            .logo-mobile {
                display: block !important;
            }
        }
        /* Customize mobile nav overlay to be completely transparent so background stays bright */
        .mobile-nav__overlay {
            background-color: transparent !important;
            opacity: 1 !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
        /* Optimize mobile nav transition speed and remove 500ms delay */
        .mobile-nav__wrapper {
            transition: transform 300ms ease, visibility 300ms ease !important;
            -webkit-transition: -webkit-transform 300ms ease, visibility 300ms ease !important;
        }
        .mobile-nav__wrapper.expanded {
            transition: transform 300ms ease, visibility 300ms ease !important;
            -webkit-transition: -webkit-transform 300ms ease, visibility 300ms ease !important;
        }
        .mobile-nav__content {
            transition: transform 300ms ease, opacity 300ms ease !important;
            -webkit-transition: -webkit-transform 300ms ease, opacity 300ms ease !important;
        }
        .mobile-nav__wrapper.expanded .mobile-nav__content {
            transition: transform 300ms ease, opacity 300ms ease !important;
            -webkit-transition: -webkit-transform 300ms ease, opacity 300ms ease !important;
        }
    </style>
</head>

<body>

    <nav class="main-menu">
        <div class="main-menu__wrapper">
            <div class="main-menu__wrapper-inner">
                <header class="main-header">
                    <div class="container d-flex justify-content-between align-items-center py-3">
                        <div class="main-menu__left">

                            <a class="navbar-brand" href="{{ url('/') }}">
                                <img src="{{ asset('img/logo/logo_ridenusa_white_BTG.png') }}" alt="Ride Nusa"
                                    class="logo-desktop" style="height:100px;">
                                <img src="{{ asset('img/logo/logo_ridenusa_BGT.png') }}" alt="Ride Nusa"
                                    class="logo-mobile" style="height:60px; display:none;">
                            </a>
                        </div>
                        <div class="main-menu__middle-box">
                            <div class="main-menu__main-menu-box">
                                <a href="#" class="mobile-nav__toggler"><i class="fa fa-bars"></i></a>
                                <ul class="main-menu__list">

                                    <li>

                                        <a href="{{ route('login') }}">Login</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('register') }}">Register</a>

                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </header>
            </div>
        </div>
    </nav>

    <!-- Main Slider Start -->
    <section class="main-slider">
        <div class="main-slider__carousel owl-carousel owl-theme">

            <div class="item">
                <div class="main-slider__bg"
                    style="background-image: url('/assets/images/backgrounds/1680x550_booking-one-bg.jpg');">
                </div><!-- /.slider-one__bg -->
                <div class="container">
                    <div class="main-slider__content">
                        <div class="main-slider__sub-title-box">
                            <p class="main-slider__sub-title">Your Best</p>
                        </div>
                        <h2 class="main-slider__title">Motorcycle <span>Rental</span></h2>
                        <p class="main-slider__sub-title-two">Experience</p>
                        <div class="main-slider__btn-and-video-box">
                            <div class="main-slider__btn-box">
                                <a href="{{ route('about') }}" class="thm-btn">Read More<span
                                        class="fas fa-arrow-right"></span></a>
                            </div>
                            <div class="main-slider__video-link">
                                <a href="https://www.youtube.com/watch?v=Get7rqXYrbQ" class="video-popup">
                                    <div class="main-slider__video-icon">
                                        <span class="icon-play-2"></span>
                                        <i class="ripple"></i>
                                    </div>
                                </a>
                                <h4 class="main-slider__video-title">Watch Video</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="item">
                <div class="main-slider__bg" style="background-image: url(/assets/images/backgrounds/xsr155.jpeg);">
                </div><!-- /.slider-one__bg -->
                <div class="container">
                    <div class="main-slider__content">
                        <div class="main-slider__sub-title-box">
                            <p class="main-slider__sub-title">Your Best</p>
                        </div>
                        <h2 class="main-slider__title">Motorcycle<span>Booking</span></h2>
                        <p class="main-slider__sub-title-two">Experience</p>
                        <div class="main-slider__btn-and-video-box">
                            <div class="main-slider__btn-box">
                                <a href="{{ route('about') }}" class="thm-btn">Read More<span
                                        class="fas fa-arrow-right"></span></a>
                            </div>
                            <div class="main-slider__video-link">
                                <a href="https://www.youtube.com/watch?v=Get7rqXYrbQ" class="video-popup">
                                    <div class="main-slider__video-icon">
                                        <span class="icon-play-2"></span>
                                        <i class="ripple"></i>
                                    </div>
                                </a>
                                <h4 class="main-slider__video-title">Watch Video</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="item">
                <div class="main-slider__bg"
                    style="background-image: url('/assets/images/backgrounds/nmaxturbo.jpeg');">
                </div><!-- /.slider-one__bg -->
                <div class="container">
                    <div class="main-slider__content">
                        <div class="main-slider__sub-title-box">
                            <p class="main-slider__sub-title">Your Best</p>
                        </div>
                        <h2 class="main-slider__title">Motorcycle<span>Choosing</span></h2>
                        <p class="main-slider__sub-title-two">Experience</p>
                        <div class="main-slider__btn-and-video-box">
                            <div class="main-slider__btn-box">
                                <a href="{{ route('about') }}" class="thm-btn">Read More<span
                                        class="fas fa-arrow-right"></span></a>
                            </div>
                            <div class="main-slider__video-link">
                                <a href="https://www.youtube.com/watch?v=Get7rqXYrbQ" class="video-popup">
                                    <div class="main-slider__video-icon">
                                        <span class="icon-play-2"></span>
                                        <i class="ripple"></i>
                                    </div>
                                </a>
                                <h4 class="main-slider__video-title">Watch Video</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </section>


    <!--Main Slider Start -->
    <section class="listing-one">
        <div class="container">
            <div class="section-title text-center sec-title-animation animation-style1">
                <div class="section-title__tagline-box justify-content-center">
                    <div class="section-title__tagline-shape">
                        <img src="{{ asset('assets/images/shapes/logo_BGT.png') }}" alt="">
                    </div>
                    <span class="section-title__tagline">Checkout our new Motorcycle</span>
                </div>
                <h2 class="section-title__title title-animation">Explore Most Popular Motorcycle</h2>
            </div>

            <div class="listing-one__tab-box listing-one-tabs-box">
                <ul class="listing-one-tab-buttons listing-one-tab-btns clearfix list-unstyled">
                    <li data-tab="#all" class="p-tab-btn active-btn"><span>All Brands</span></li>
                    @foreach ($motorcycles->pluck('brand')->unique() as $brand)
                        <li data-tab="#{{ Str::slug($brand) }}" class="p-tab-btn">
                            <span>{{ $brand }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="p-tabs-content">
                    <div class="p-tab active-tab" id="all">
                        <div class="listing-one__inner">
                            <div class="row">
                                @foreach ($motorcycles->shuffle()->take(6) as $m)
                                    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                                        <div class="listing-one__single">
                                            <div class="listing-one__img">
                                                @if ($m->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($m->image_path))
                                                    {{-- Jika file benar-benar ada di storage/app/public --}}
                                                    <img src="{{ asset('storage/' . $m->image_path) }}"
                                                        alt="{{ $m->category }}">
                                                @else
                                                    {{-- Jika database kosong ATAU file fisik tidak ditemukan --}}
                                                    <img src="{{ asset('assets/images/resources/RIDEnotrasparan.png') }}"
                                                        alt="No Image Available">
                                                @endif

                                                <div class="listing-one__brand-name">
                                                    <p>{{ strtoupper($m->brand) }}</p>
                                                </div>
                                            </div>
                                            <div class="listing-one__content">
                                                <h3 class="listing-one__title"><a
                                                        href="{{ Auth::check() ? route('motorcycles.show', $m->id) : route('login') }}">{{ $m->category }}</a>
                                                </h3>
                                                <div class="listing-one__meta-box-info">
                                                    <ul class="list-unstyled listing-one__meta">
                                                        <li>
                                                            <div class="icon"><span class="icon-manual"></span>
                                                            </div>
                                                            <div class="text">
                                                                <p>{{ ucfirst($m->transmission) }}</p>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="icon"><span class="icon-mileage"></span>
                                                            </div>
                                                            <div class="text">
                                                                <p>{{ $m->lastService->kilometer ?? 0 }} KM</p>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="icon"><span class="icon-fuel-type"></span>
                                                            </div>
                                                            <div class="text">
                                                                <p>{{ $m->fuel_configuration }}</p>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="icon"><span class="icon-mileage"></span>
                                                            </div>
                                                            <div class="text">
                                                                {{-- Karena di tabel tidak ada kolom kilometer, kita
                                                                tampilkan CC saja atau tgl service --}}
                                                                <p>{{ $m->cc }} CC</p>
                                                            </div>
                                                        </li>
                                                        <li style="flex-wrap: nowrap; gap: 5px;">
                                                            <div class="icon"><span class="fas fa-motorcycle"></span>
                                                            </div>
                                                            <div class="text">
                                                                {{-- Mengubah 'big_matic' menjadi 'Big Matic' agar lebih
                                                                rapi --}}
                                                                <p style="white-space: nowrap;">
                                                                    {{ str_replace('_', ' ', ucfirst($m->type)) }}
                                                                </p>
                                                            </div>
                                                        </li>

                                                    </ul>
                                                </div>
                                                <div class="listing-one__car-rent-box">
                                                    <p class="listing-one__car-rent">Starting From <span>Rp
                                                            {{ number_format($m->price, 0, ',', '.') }}/</span>
                                                        Day</p>
                                                </div>
                                                <div class="listing-one__btn-box">
                                                    <a href="{{ Auth::check() ? route('motorcycles.show', $m->id) : route('login') }}"
                                                        class="thm-btn">Details
                                                        Now</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @foreach ($motorcycles->pluck('brand')->unique() as $brand)
                        <div class="p-tab" id="{{ Str::slug($brand) }}">
                            <div class="listing-one__inner">
                                <div class="row">
                                    @foreach ($motorcycles->where('brand', $brand) as $m)
                                        <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                                            <div class="listing-one__single">
                                                <div class="listing-one__img">
                                                    @if ($m->image_path && file_exists(public_path('storage/' . $m->image_path)))
                                                        <img src="{{ asset('storage/' . $m->image_path) }}" alt="{{ $m->category }}"
                                                            style="width: 100%; height: 250px; object-fit: cover;">
                                                    @else
                                                        <img src="{{ asset('assets/images/resources/RIDEnotrasparan.png') }}"
                                                            alt="No Image Available"
                                                            style="width: 100%; height: 250px; object-fit: cover;">
                                                    @endif

                                                    <div class="listing-one__brand-name">
                                                        <p>{{ strtoupper($m->brand) }}</p>
                                                    </div>
                                                </div>
                                                <div class="listing-one__content">
                                                    <h3 class="listing-one__title"><a
                                                            href="{{ Auth::check() ? route('motorcycles.show', $m->id) : route('login') }}">{{ $m->category }}</a>
                                                    </h3>
                                                    <div class="listing-one__meta-box-info">
                                                        <ul class="list-unstyled listing-one__meta">
                                                            <li>
                                                                <div class="icon"><span class="icon-manual"></span>
                                                                </div>
                                                                <div class="text">
                                                                    <p>{{ ucfirst($m->transmission) }}</p>
                                                                </div>
                                                            </li>
                                                            <li>
                                                                <div class="icon"><span class="icon-mileage"></span>
                                                                </div>
                                                                <div class="text">
                                                                    <p>{{ $m->lastService->kilometer ?? 0 }} KM</p>
                                                                </div>
                                                            <li style="flex-wrap: nowrap; gap: 5px;">
                                                                <div class="icon"><span class="fas fa-motorcycle"></span>
                                                                </div>
                                                                <div class="text">
                                                                    {{-- Mengubah 'big_matic' menjadi 'Big Matic' agar lebih
                                                                    rapi --}}
                                                                    <p style="white-space: nowrap;">
                                                                        {{ str_replace('_', ' ', ucfirst($m->type)) }}
                                                                    </p>
                                                                </div>
                                                            </li>
                                                            </li>
                                                            <li>
                                                                <div class="icon"><span class="icon-fuel-type"></span>
                                                                </div>
                                                                <div class="text">
                                                                    <p>{{ $m->fuel_configuration }}</p>
                                                                </div>
                                                            </li>
                                                            <li>
                                                                <div class="icon"><span class="icon-mileage"></span>
                                                                </div>
                                                                <div class="text">
                                                                    {{-- Karena di tabel tidak ada kolom kilometer, kita
                                                                    tampilkan CC saja atau tgl service --}}
                                                                    <p>{{ $m->cc }} CC</p>
                                                                </div>
                                                            </li>
                                                            <li>
                                                                <div class="icon"><span class="icon-mileage"></span>
                                                                </div>
                                                                <div class="text">
                                                                    {{-- Jika ada data service, tampilkan kilometernya. Jika
                                                                    tidak ada, tampilkan 0 --}}
                                                                    <p>{{ $m->lastService->kilometer ?? 0 }} KM</p>
                                                                </div>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div class="listing-one__car-rent-box">
                                                        <p class="listing-one__car-rent">Starting From <span>Rp
                                                                {{ number_format($m->price, 0, ',', '.') }}/</span>
                                                            Day
                                                        </p>
                                                    </div>
                                                    <div class="listing-one__btn-box">
                                                        <a href="{{ Auth::check() ? route('motorcycles.show', $m->id) : route('login') }}"
                                                            class="thm-btn">Details
                                                            Now</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
    </section>

    <!--Mobile Nav-->
    <div class="mobile-nav__wrapper">
        <div class="mobile-nav__overlay mobile-nav__toggler"></div>
        <div class="mobile-nav__content">
            <span class="mobile-nav__close mobile-nav__toggler"><i class="fa fa-times"></i></span>
            <div class="logo-box">
                <a href="{{ url('/') }}" aria-label="logo image"><img
                        src="{{ asset('img/logo/logo_ridenusa_white_BTG.png') }}" width="150" alt="Ride Nusa" /></a>
            </div>
            <div class="mobile-nav__container"></div>
            <ul class="mobile-nav__contact list-unstyled">
                <li>
                    <i class="fa fa-envelope"></i>
                    <a href="mailto:support@ridenusa.com">support@ridenusa.com</a>
                </li>
                <li>
                    <i class="fas fa-phone"></i>
                    <a href="tel:+6281234567890">+62 812-3456-7890</a>
                </li>
            </ul>
        </div>
    </div>







    <!-- Script yang dibutuhkan -->
    <script src="/assets/js/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/jarallax.min.js"></script>
    <script src="/assets/js/jquery.ajaxchimp.min.js"></script>
    <script src="/assets/js/jquery.appear.min.js"></script>
    <script src="/assets/js/swiper.min.js"></script>
    <script src="/assets/js/jquery.circle-progress.min.js"></script>
    <script src="/assets/js/knob.js"></script>
    <script src="/assets/js/jquery.magnific-popup.min.js"></script>
    <script src="/assets/js/jquery.validate.min.js"></script>
    <script src="/assets/js/wNumb.min.js"></script>
    <script src="/assets/js/wow.js"></script>
    <script src="/assets/js/owl.carousel.min.js"></script>
    <script src="/assets/js/jquery-ui.js"></script>
    <script src="/assets/js/jquery.nice-select.min.js"></script>
    <script src="/assets/js/jquery-sidebar-content.js"></script>
    <script src="/assets/js/gsap/gsap.js"></script>
    <script src="/assets/js/gsap/ScrollTrigger.js"></script>
    <script src="/assets/js/gsap/SplitText.js"></script>
    <script src="/assets/js/marquee.min.js"></script>
    <script src="/assets/js/odometer.min.js"></script>
    <script src="/assets/js/timePicker.js"></script>
    <script src="/assets/js/typed-2.0.11.js"></script>
    <script src="/assets/js/aos.js"></script>

    <!-- Template JS -->
    <script src="/assets/js/script.js"></script>

</body>

</html>