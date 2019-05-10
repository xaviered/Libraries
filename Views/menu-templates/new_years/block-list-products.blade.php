@foreach ($category->products as $productIndex=>$product)
    <div class="product product-{{ $product->slug }} product-{{ $productIndex }}">
        <span class="title">{{ $product->title }}</span>
        @if($product->hasPrice())
            <span class="price">{{ $product->getPrice(0) }}</span>
        @endif
        <span class="description">{{ $product->description }}</span>
    </div>
@endforeach
