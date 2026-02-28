@extends('layout')

@section('header')

	
@endsection

@section('content')
@include('error')
			
<h1>Downloading...</h1>

<script>
	
	$(document).ready(function(){
			
		@if(isset($exporturl))
			console.log("download url: {{$exporturl}}");
	  		window.location.assign("/{{$exporturl}}");
	
	  	@endif
	});
	
</script>

  
@endsection