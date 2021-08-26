<div class="columns">
  <div class="column">
    <aside class="menu" style="padding: 2rem 0">
      <ul class="menu-list">
        @foreach($menuLinks as $name => $link)
        <li>
          <a href="{{$link['url']}}" title="{{$name}}" class="{{$link['current'] ? 'is-active' : ''}}">{{$name}}</a>
        </li>
        @endforeach
      </ul>
    </aside>
  </div>
</div>
