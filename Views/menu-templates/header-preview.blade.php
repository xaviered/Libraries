<!DOCTYPE html>
<html lang="{{ config( 'app.locale' ) }}">
<head>
    <link rel="shortcut icon" href="{{ asset( 'images/favicon.png', true ) }}"/>

    <script type="text/javascript" src="'/ixavier-libraries/js/jquery/jquery.min.js'"></script>
    @include( '/ixavier-libraries/menu-templates/head' )
    @include( '/ixavier-libraries/menu-templates/head-includes' )
    <title>{{ $template->name }}</title>
</head>
<body>
@include( '/ixavier-libraries/menu-templates/header' )

