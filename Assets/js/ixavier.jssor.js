if (typeof(ixavier) === 'undefined') {
  ixavier = {};
}

ixavier.jssor = {};
ixavier.jssor_instances = {};

ixavier.jssor = function (name, options) {
  this.canvasWidth = 0;
  this.jssorSlider = null;
  this.options = options;
  this.name = name;

  $(document).on('jssor', function (e, jssor) {
    jssor.jssorSlider = new $JssorSlider$(jssor.name + '_div', options || null);
  });

  this.__proto__.updateElementSize = function ($col) {
    let $this = this;
    $col.each(function (i, ele) {
      $(ele).width($this.canvasWidth);
      $(ele).height($this.canvasWidth / 2);
      $(ele).css('visibility', 'visible');
    });
  };

  this.__proto__.updateDimensions = function () {
    this.canvasWidth = $('.popular .canvas').width();
    let $title = $('.jssor_slider .slides .slide .title');
    $title.css('top', (this.canvasWidth / 2 - $title.height() - 17) + 'px');

    let eles = ['.jssor_slider', '.jssor_slider .slides', '.popular .canvas'];
    for (let i in eles) {
      this.updateElementSize($(eles[i]));
    }
    $(document).trigger('jssor', this);
  };

  let update = function (jssor) {
    jssor.updateDimensions();
  };

  window.setTimeout(update, 300, this);
  // @todo: update things on resize
  // $(window).on('resize', update, this);
};
