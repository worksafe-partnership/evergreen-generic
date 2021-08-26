@if (!empty($sidebarLinks))
	@foreach ($sidebarLinks as $link)

		@php
		$linkAttributes = "";
		if (!empty($link['attributes'])) {
			foreach ($link['attributes'] as $key=>$value) {
				$linkAttributes.=$key."='".$value."'; ";
			}
		}
		@endphp
		@if (isset($link['url']))
			<li class="link-li">
				<a href="{{$link['url']}}" {{$linkAttributes}} class="{{$link['current'] ? 'is-active' : ''}}"
				   id="sidebar-{{$link['identifier_path']}}">
					@if (isset($link['icon']))
						{!!icon($link['icon'],'1.2rem')!!}
					@endif
					{!!$link['label']!!}
                    @if (!empty($link['extra']))
                        <span class="is-danger tag">{{$link['extra']}}</span>
                    @endif
				</a>
		@else
			<li class="link-li">
				<a class="no-link" id="sidebar-{{$link['identifier_path']}}">
				@if (isset($link['icon']))
					{!!icon($link['icon'])!!}
				@endif
				{!!$link['label']!!}
				</a>
		@endif
		@if (isset($link['sub_menu']))
			<a class="button toggle-child">
				<span class="default">{{icon('keyboard_arrow_up','1.4rem')}}</span>
				<span class="hidden">{{icon('keyboard_arrow_down','1.4rem')}}</span>
			</a>
			<ul class="menu-list">
			@foreach ($link['sub_menu'] as $subLink)
				@php
					$subLinkAttributes = "";
					if (!empty($subLink['attributes'])) {
						foreach ($subLink['attributes'] as $key=>$value) {
							$subLinkAttributes.=$key."='".$value."'; ";
						}
					}
				@endphp
        			@if (isset($subLink['url']))
        				<li><a href="{{$subLink['url']}}" {{$subLinkAttributes}}  class="{{$subLink['current'] ? 'is-active' : ''}}">
        					@if (isset($subLink['icon']))
        						{!!icon($subLink['icon'])!!}
        					@endif
        				{!!$subLink['label']!!}</a></li>
        			@endif
			@endforeach
			</ul>
		@endif
		</li>
	@endforeach
@endif
