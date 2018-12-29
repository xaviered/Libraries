@include( '/ixavier-libraries/menu-templates/base/header' )
<div class="menu">
    <table>
        <?php $i = 0 ?>
        @foreach($template->categories as $k=>$category)
            <tr class="category">
                <td class="title" colspan="3">{{ $category->title }}</td>
                @if($i++ == 0)
                    <td class="price-type">SM</td>
                    <td class="price-type">MD</td>
                    <td class="price-type">LG</td>
                @endif
            </tr>
            @foreach ($category->products as $product)
                <tr class="product">
                    <td class="icon"></td>
                    <td class="number">{{ $product->sort }}.&nbsp;</td>
                    <td class="details">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td class="title">{!! str_replace(' ', '&nbsp;', $product->title) !!}</td>
                                <td class="description">{!! str_replace(' ', '&nbsp;', $product->description) !!}</td>
                                <td class="line">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                    @foreach ($product->price as $i=>$price)
                        <td class="price price-{{ $i }}">{{ $price }}</td>
                    @endforeach
                </tr>
            @endforeach
        @endforeach
    </table>
</div>
@include( '/ixavier-libraries/menu-templates/base/footer' )
