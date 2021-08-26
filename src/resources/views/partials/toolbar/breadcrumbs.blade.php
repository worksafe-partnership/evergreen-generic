@if (!empty($breadcrumbs))
	<nav class="breadcrumb">
		@foreach ($breadcrumbs as $crumb)
			@if ($crumb['link'])
				<a href="{{$crumb['path']}}" class="button is-outlined">
					@if(isset($crumb['icon']))
						{{icon($crumb['icon'],'1.2rem')}}&nbsp;
					@endif
					{{$crumb['name']}}
				</a>
			@else
				<a class="button is-outlined">
					@if(isset($crumb['icon']))
						{{icon($crumb['icon'],'1.2rem')}}&nbsp;
					@endif
					{{$crumb['name']}}
				</a>
			@endif
		@endforeach
	</nav>
@endif

