{{-- @var --}}
<script src="{!! asset( 'ixavier-libraries/js/jssor-slider/jssor-slider.min.js' ) !!}"></script>
@if( $slides && $slides->count() )
    <script>
        jQuery(document).ready(function ($) {
        var options = { $AutoPlay: 0 };
        var jssor_slider1 = new $JssorSlider$('slider_{{ $name }}_container', options);
        });
    </script>
    <div id="slider_{{ $name }}_container" style="position: relative; top: 0px; left: 0px; width: 600px; height: 300px;">
        <!-- Slides Container -->
        <div u="slides" style="cursor: move; position: absolute; overflow: hidden; left: 0px; top: 0px; width: 600px; height: 300px;">
            @foreach( $slides as $slide)
                <div><img u="image" src="{{ $slide->url }}" /></div>
            @endforeach
        </div>
    </div>
@endif