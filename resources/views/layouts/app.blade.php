@php
    $siteName = $site['name'];
    $h = $site['header'];
    $seo = $seo ?? [];
    $title = $seo['title'] ?? $siteName;
    $description = $seo['description'] ?? $site['seo']['default_description'] ?? $site['tagline'] ?? null;
    $ogImage = $seo['og_image'] ?? $site['seo']['default_og_image'] ?? null;
    $canonical = $seo['canonical'] ?? url()->current();
    $bodyClass = trim(($bodyClass ?? '').($h['visible'] ? '' : ' no-header'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>{{ $title }}</title>
  @if($description)<meta name="description" content="{{ $description }}" />@endif
  <link rel="canonical" href="{{ $canonical }}" />
  @if(!empty($seo['noindex']))<meta name="robots" content="noindex, nofollow" />@endif

  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="{{ $siteName }}" />
  <meta property="og:title" content="{{ $title }}" />
  @if($description)<meta property="og:description" content="{{ $description }}" />@endif
  <meta property="og:url" content="{{ $canonical }}" />
  @if($ogImage)<meta property="og:image" content="{{ $ogImage }}" />@endif
  <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}" />
  <meta name="twitter:title" content="{{ $title }}" />
  @if($description)<meta name="twitter:description" content="{{ $description }}" />@endif
  @if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}" />@endif
  <meta name="theme-color" content="#f7f3ee" />

  <!-- Fonts (unchanged from the original design) -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500;600&family=Great+Vibes&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/enhancements.css') }}?v={{ filemtime(public_path('css/enhancements.css')) }}" />
  @if($site['effects'])
  <link rel="stylesheet" href="{{ asset('css/fx.css') }}?v={{ filemtime(public_path('css/fx.css')) }}" />
  @endif
  @stack('head')
  <noscript><style>[data-reveal],.reveal-scroll{opacity:1!important;transform:none!important}.preloader{display:none!important}</style></noscript>
</head>
<body class="{{ $bodyClass }}">
  <a class="skip-link" href="#main">Skip to content</a>

  <div class="preloader" id="preloader" aria-hidden="true">
    <div class="preloader__inner">
      <img class="preloader__logo-img" src="{{ $h['logo_url'] }}" alt="" />
      <div class="preloader__bar"><span></span></div>
    </div>
  </div>

  <div class="cursor" id="cursor" aria-hidden="true"></div>
  <div class="cursor-follow" id="cursorFollow" aria-hidden="true"></div>

  @include('partials.header')

  @if($h['floating_button'])
  <div class="side-widgets">
    <a href="/contact" class="side-widgets__btn side-widgets__btn--pink" aria-label="Send an enquiry"><i class="fa-solid fa-envelope-open-text"></i></a>
  </div>
  @endif

  @include('partials.mobile-menu')

  <main id="main">
    @yield('content')
  </main>

  @include('partials.footer')

  @php
    $waDigits = preg_replace('/\D/', '', (string) ($site['contact']['whatsapp'] ?? ''));
    if (strlen($waDigits) === 10) { $waDigits = '91'.$waDigits; }   // local Indian number without country code
  @endphp
  @if(strlen($waDigits) >= 8)
    <a class="wa-float" href="https://wa.me/{{ $waDigits }}?text={{ rawurlencode('Hello '.$site['name'].', I would like to know more about your photography services.') }}"
       target="_blank" rel="noopener noreferrer" aria-label="Chat with us on WhatsApp" title="Chat with us on WhatsApp">
      <i class="fa-brands fa-whatsapp"></i><span class="wa-float__tip">Chat on WhatsApp</span>
    </a>
  @endif

  <button class="totop" id="toTop" aria-label="Back to top"><i class="fa-solid fa-arrow-up"></i></button>

  <script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
  <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}"></script>
  @if($site['effects'])
  <script src="{{ asset('js/fx.js') }}?v={{ filemtime(public_path('js/fx.js')) }}" defer></script>
  @endif
  @stack('scripts')
</body>
</html>
