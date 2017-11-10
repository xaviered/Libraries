{{-- @var --}}
<script src="{!! asset( 'ixavier-libraries/js/jssor-slider/jssor-slider.min.js' ) !!}"></script>
@if( $slides && count($slides) )
	<?php $name = $name ?? rand() ?>
    <script>
        jQuery(document).ready(function ($) {
            var options = {$AutoPlay: 1};
            var jssor_slider1 = new $JssorSlider$('jssor_slider1', options);
        });
    </script>
    <style type="text/css">
        .content {
            margin-left: 78px;
        }
        .jssor_slider div.slide img {
            max-width: 1018px;
        }
    </style>
    <div class="jssor_slider" id="jssor_slider1"
         style="position: relative; top: 0px; left: 0px; width: 1018px; height: 508px;">
        <!-- Slides Container -->
        <div class="slides" data-u="slides"
             style="cursor: move; position: absolute; overflow: hidden; left: 0px; top: 0px; width: 1018px; height: 508px;">
            @foreach( $slides as $slide )
                @if( $slide->hasRelationship('image') )
                    <div class="slide"><img data-u="image" src="{{ $slide->getRelationship('image')->src }}" /></div>
                @elseif( isset($slide->image) )
                    <div class="slide"><img data-u="image" src="{{ $slide->image }}"/></div>
                @else
                    <div class="slide"><img data-u="image" src="{{ $slide }}"/></div>
                @endif
            @endforeach
        </div>
    </div>
@endif