<div class="columns">
  <div class="column">
    <div class="tabs is-centered is-boxed">
      <ul>
        @foreach($tabLinks as $name => $link)
          <li>
            <a href="{{$link['url']}}" title="{{$name}}" class="{{$link['current'] ? 'is-active' : ''}}">{{$name}}</a>
          </li>
        @endforeach
      </ul>
    </div>
  </div>
</div>
