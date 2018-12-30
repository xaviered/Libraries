<? /** @var \ixavier\Libraries\Server\RestfulRecords\MenuTemplates\MenuTemplate $template */ ?>
@include( '/ixavier-libraries/menu-templates/base/header' )
<div class="menu">
    <div class="header-note">
        <p>Dinner includes one item of every category below</p>
    </div>

    <table>
        <?php $categoryIndex = 0 ?>
        @foreach($template->categories as $category)
            @if($category->slug === 'main_course')
                <tr class="category category-{{ $category->slug }} category-{{ $categoryIndex }}">
                    <td class="title" colspan="3">{{ $category->title }}</td>
                    <td class="price-type"></td>
                </tr>
                @include( '/ixavier-libraries/menu-templates/new_years/table-list-products', ['category'=>$category] )
            @else
                <tr class="category category-{{ $category->slug }} category-{{ $categoryIndex }}">
                    <td class="title" colspan="3">{{ $category->title }}</td>
                </tr>
                <tr class="products">
                    <td colspan="3" class="category-{{ $category->slug }} category-{{ $categoryIndex }}">
                        @include( '/ixavier-libraries/menu-templates/new_years/block-list-products', ['category'=>$category] )
                    </td>
                </tr>
            @endif
            <?php $categoryIndex++ ?>
        @endforeach
    </table>

    <hr/>
    <div class="footer-note">
        <p>Please ask for our Champagne Toast</p>
        <p>Tax and gratuity not included</p>

        <div class="address">
            50-22 39th Ave. Woodside, NY 11377
            <br/>p: <a href="tel:7185074591">718.507.4591</a> &nbsp; &nbsp; f: 718.606.6073
            <br/><a href="https://www.donatospol.com">www.donatospol.com</a>
        </div>
    </div>
</div>
@include( '/ixavier-libraries/menu-templates/base/footer' )
