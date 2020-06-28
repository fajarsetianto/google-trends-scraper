<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Limitless - Responsive Web Application Kit by Eugene Kopyov</title>

	<!-- Global stylesheets -->
	<link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
	<link href="{{asset('vendor/limitless/global_assets/css/icons/icomoon/styles.min.css')}}" rel="stylesheet" type="text/css">
	<link href="{{asset('vendor/limitless/assets/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css">
	<link href="{{asset('vendor/limitless/assets/css/bootstrap_limitless.min.css')}}" rel="stylesheet" type="text/css">
	<link href="{{asset('vendor/limitless/assets/css/layout.min.css')}}" rel="stylesheet" type="text/css">
	<link href="{{asset('vendor/limitless/assets/css/components.min.css')}}" rel="stylesheet" type="text/css">
	<link href="{{asset('vendor/limitless/assets/css/colors.min.css')}}" rel="stylesheet" type="text/css">
    <!-- /global stylesheets -->
    
    @yield('css')

	<!-- Core JS files -->
	<script src="{{asset('vendor/limitless/global_assets/js/main/jquery.min.js')}}"></script>
	<script src="{{asset('vendor/limitless/global_assets/js/main/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('vendor/limitless/global_assets/js/plugins/loaders/blockui.min.js')}}"></script>
    <script src="{{asset('vendor/limitless/global_assets/js/plugins/forms/styling/uniform.min.js')}}"></script>
	<!-- /core JS files -->
</head>

<body style="background-image: linear-gradient(#00000000, #000000cf),url('{{asset('images/bg.jpg')}}');
background-size: 350px;
background-blend-mode: luminosity;">
    
    @yield('content')
            
            
    
    
    @yield('js')
</body>
</html>
