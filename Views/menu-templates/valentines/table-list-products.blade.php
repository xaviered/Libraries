@foreach ($category->products as $productIndex=>$product)
    <tr class="product products-{{ $category->slug }} products-{{ $categoryIndex }} product-{{ $product->slug }} product-{{ $productIndex }}">
        <td class="icon"></td>
        <td class="number">{{ $product->sort }}.&nbsp;</td>
        <td class="details">
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td class="title">{!! str_replace(' ', '&nbsp;', $product->title) !!}</td>
                    <td class="{{ $product->hasPrice() ? 'line' : '' }}">&nbsp;</td>
                </tr>
                <tr>
                    <td class="description" colspan="2">{!! str_replace(' ', '&nbsp;', $product->description) !!}</td>
                </tr>
            </table>
        </td>
        @foreach ($product->price as $i=>$price)
            <td class="price price-{{ $i }}">{{ $price }}</td>
        @endforeach
    </tr>
@endforeach
