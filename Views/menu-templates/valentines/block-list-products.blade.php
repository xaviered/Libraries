@foreach ($category->products as $productIndex=>$product)
    <div class="product product-{{ $product->slug }} product-{{ $productIndex }}">
        <div class="title">{{ $product->title }}</div>
        @if($product->hasPrice())
            <div class="price">{{ $product->getPrice(0) }}</div>
        @endif
        <div class="description">{{ $product->description }}</div>
    </div>
@endforeach
