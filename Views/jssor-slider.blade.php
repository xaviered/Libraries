{{--check out options at https://www.jssor.com/development/api-options.html--}}
@if( $slides && count($slides) )
	<?php $name =  ($name ?? 'jssor_slider' . rand()) ?>
    <script>
        var {{ $name }};
        jQuery(document).ready(function ($) {
            var options = {!! json_encode($options ?? []) !!};
            {{ $name }} = new $JssorSlider$('{{ $name }}_div', options || null);
        });
    </script>

    <div class="jssor_slider" id="{{ $name }}_div">
        <!-- Slides Container -->
        <div class="slides" data-u="slides">
            @foreach( $slides as $slide )
                <div class="slide">
                    @if( $slide->hasRelation('images') )
                        <?php $img = $slide->getRelation('images', 'size')->get($size ?? 'large') ?>
                        <img data-type="{{ $img->type }}" data-u="image" src="{{ $img->src() }}" />
                    @elseif( $slide->image instanceof \ixavier\Libraries\Server\Core\RestfulRecord )
                        <img data-type="{{ $slide->image->type }}" data-u="image" src="{{ $slide->image }}"/>
                    @else
                        <img data-u="image" src="{{ $slide }}"/>
                    @endif
                    <div class="title">{{ $slide->title }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif
