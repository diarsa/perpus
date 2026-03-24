<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ (isset($title) && $title ? $title . ' - ' : '') . config('app.name') }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body { background-color: #f5f5f5; font-family: 'Roboto', sans-serif; }
        .shopee-orange { background-color: #ee4d2d !important; color: white !important; }
        .shopee-text-orange { color: #ee4d2d !important; }
        
        .navbar-main { padding: 15px 0; background: linear-gradient(-180deg,#f53d2d,#f63); }
        .search-bar { background: #fff; border-radius: 2px; padding: 3px; display: flex; width: 100%; }
        .search-input { border: none; flex-grow: 1; padding: 8px 15px; outline: none; font-size: 14px; }
        .search-btn { background-color: #fb5533; border: none; padding: 10px 25px; color: white; border-radius: 2px; }
        .search-btn:hover { background-color: #f63; }
        
        .product-card { background: #fff; border-radius: 2px; transition: transform 0.1s ease-in, box-shadow 0.1s ease-in; cursor: pointer; height: 100%; border: none; }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 0 10px rgba(0,0,0,0.1); border: 1px solid #ee4d2d; }
        .product-img { aspect-ratio: 1/1; object-fit: cover; width: 100%; background: #f8f8f8; }
        .product-info { padding: 8px; }
        .product-title { font-size: 13px; line-height: 18px; height: 36px; overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; margin-bottom: 8px; color: #222; }
        .product-price { color: #ee4d2d; font-size: 16px; font-weight: 500; }
        .product-stock { font-size: 11px; color: #757575; border-top: 1px solid #f0f0f0; padding-top: 5px; margin-top: 5px; }
        
        .promo-banner { background-color: #fff; border-radius: 2px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,0.05); }
        .nav-top { background-color: transparent; font-size: 12px; color: white; padding: 5px 0; }
        .nav-top a { color: white; text-decoration: none; margin-right: 15px; }
        .nav-top a:hover { opacity: 0.8; }
        
        .btn-shopee {  background-color: #ee4d2d; color: white; border-radius: 2px; font-size: 14px; padding: 10px; border: none; width: 100%; }
        .btn-shopee:hover { background-color: #f25838; color: white; }
        .btn-shopee-outline { background-color: transparent; border: 1px solid #ee4d2d; color: #ee4d2d; border-radius: 2px; font-size: 14px; padding: 8px; width: 100%; }
        .btn-shopee-outline:hover { background-color: rgba(238,77,45,0.05); }

        .pagination .page-item.active .page-link { background-color: #ee4d2d; border-color: #ee4d2d; }
        .pagination .page-link { color: #ee4d2d; }
    </style>
    @livewireStyles
</head>
<body>

    {{ $slot }}

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>
</html>
