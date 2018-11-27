{{--check out options at https://www.jssor.com/development/api-options.html--}}
@if( $slides && count($slides) )
    <script type="application/javascript">
      <?php $name = $name ?? 'jssor_slider'.rand(); ?>
      var {{ $name }} = new ixavier.jssor("{{ $name }}", {!! json_encode($options ?? []) !!});
    </script>

    <div class="jssor_slider" id="{{ $name }}_div">
        <!-- Slides Container -->
        <div class="slides" data-u="slides">
            @foreach( $slides as $slide )
                <div class="slide">
                    @if( $slide->hasRelation('images') )
                        <?php $img = $slide->getRelation('images', 'size')->get($size ?? 'medium') ?>
                        <img class="image" data-type="{{ $img->type }}" data-u="image" src="{{ $img->src() }}" width="100%" height="100%"/>
                    @elseif( $slide->image instanceof \ixavier\Libraries\Server\Core\RestfulRecord )
                        <img class="image" data-type="{{ $slide->image->type }}" data-u="image" src="{{ $slide->image }}"
                             width="100%"  height="100%"/>
                    @else
                        <img class="image" data-u="image" src="{{ $slide }}" width="100%" height="100%"/>
                    @endif
                    <div class="title">{{ $slide->title }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif
