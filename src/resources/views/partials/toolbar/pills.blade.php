<div class="block">
    @if (!empty($pillButtons))
    <div class="pill-menu">
            <a href="#"
               class="button is-primary "
            >
                    {{icon($pillButtonIcon,'1.5rem')}}
                @if (strlen($pillButtonText))
                    <span class="action-text is-hidden-touch">{{$pillButtonText}}</span>
                @endif
            </a>
    </div>
    <div class="pill-buttons">
        @foreach($pillButtons as $name => $button)
            @if($name !== "sub" || ($name == "sub" && !empty($button['list'])))
                <a @if(isset($button['id'])) id={{$button['id']}} @endif href="@if (isset($button['path'])) {{isset($button['path']) ? $button['path'] : '#'}} @else # @endif"
                   class="button is-primary {{isset($button['class']) ? $button['class'] : ''}} {{isset($button['list']) ? 'list-button' : ''}}"
                   @if (isset($button['target'])) target="{{$button['target']}}" @endif
                   @if (isset($button['onclick'])) onclick="{{$button['onclick']}}" @endif
                   @if (isset($button['title'])) title="{{$button['title']}}" @endif
                   @if (isset($button['attributes']) && is_array($button['attributes']))
                    @foreach ($button['attributes'] as $key => $value)
                        {{$key}}="{{$value}}"
                    @endforeach
                   @endif
                >
                    @if (isset($button['icon']))
                        {{icon($button['icon'],'1.5rem')}}
                    @endif
                    @if (isset($button['label']) && strlen($button['label']))
                        <span class="action-text">{{$button['label']}}</span>
                    @endif
                </a>
                @if (isset($button['list']) && is_array($button['list']))
                    <div class="button-sub-group">
                        @foreach ($button['list'] as $subButton)
                            <a href="{{isset($subButton['path']) ? $subButton['path'] : '#'}}"
                            class="button sub-button is-primary {{isset($subButton['class']) ? $subButton['class'] : ''}}"
                            target="{{isset($subButton['target']) ? $subButton['target'] : ''}}">
                                @if (isset($subButton['icon']))
                                    {{icon($subButton['icon'],'1.5rem')}}
                                @endif
                                @if (strlen($subButton['label']))
                                    <span class="action-text">{{$subButton['label']}}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif
        @endforeach
    </div>
    @endif
    @if ($backButton != null)
    <div class="back-button">
            <a href="@if (isset($backButton['path'])) {{isset($backButton['path']) ? $backButton['path'] : '#'}} @else # @endif"
               class="button is-primary {{isset($backButton['class']) ? $backButton['class'] : ''}} {{isset($backButton['list']) ? 'list-button' : ''}}"
               @if (isset($backButton['target'])) target="{{$backButton['target']}}" @endif
               @if (isset($backButton['onclick'])) onclick="{{$backButton['onclick']}}" @endif
            >
                @if (isset($backButton['icon']))
                    {{icon($backButton['icon'],'1.5rem')}}
                @endif
                @if (strlen($backButton['label']))
                    <span class="action-text is-hidden-touch">{{$backButton['label']}}</span>
                @endif
            </a>
    </div>
    @endif
</div>
