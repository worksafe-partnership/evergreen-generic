<div class="block">
	@if (!empty($actionButtons))
		@foreach($actionButtons as $button)
		    <a href="@if (isset($button['path'])){{
		    	isset($button['path']) ? $button['path'] : '#'
		    }}@else # @endif"
		    class="button {{isset($button['class']) ? $button['class'] : ''}}
		    {{isset($button['list']) ? 'list-button' : ''}}"
		    	@if(isset($button['id']) && !empty($button['id'])) id="{{$button['id']}}" @endif
		    	@if(isset($button['title']) && !empty($button['title'])) title="{{$button['title']}}" @endif
		    	@if(isset($button['target']) && !empty($button['target'])) target="{{$button['target']}}" @endif
		    	@if(isset($button['onclick']) && !empty($button['onclick'])) onclick="{{$button['onclick']}}" @endif
		    	@if(isset($button['attributes']) && is_array($button['attributes'])) 
		    		@foreach($button['attributes'] as $key => $value)
		    			{{$key}}="{{$value}}"
		    		@endforeach
		    	@endif
		    >
		     	@if (isset($button['icon']))
		      		{{icon($button['icon'])}}&nbsp;
		     	@endif
				@if (strlen($button['label']))
					<span class="action-text is-hidden-touch">{{$button['label']}}</span>
				@endif
		    </a>
				@if (isset($button['list']))
				<div class="button-sub-group">
					@foreach ($button['list'] as $subButton)
					    <a href="{{isset($subButton['path']) ? $subButton['path'] : '#'}}" class="button sub-button {{isset($subButton['class']) ? $subButton['class'] : ''}}"
					    target="{{isset($subButton['target']) ? $subButton['target'] : ''}}"
					    onclick="{{isset($subButton['onclick']) ? $subButton['onclick'] : ''}}">
					     	@if (isset($subButton['icon']))
					      		{{icon($subButton['icon'])}}&nbsp;
					     	@endif
							@if (strlen($subButton['label']))
								<span class="action-text is-hidden-touch">{{$subButton['label']}}</span>
							@endif
						</a>
					@endforeach
				</div>
				@endif
		@endforeach
	@endif
</div>
