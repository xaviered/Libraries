<? /** @var \ixavier\Libraries\Server\RestfulRecords\MenuTemplates\MenuTemplate $template */ ?>
@include( '/ixavier-libraries/menu-templates/base/header' )
<div class="menu">
    <table>
        <?php $categoryIndex = 0 ?>
        @foreach($template->categories as $category)
            @if($category->slug === 'main_course')
                <tr class="category category-{{ $category->slug }} category-{{ $categoryIndex }}">
                    <td class="title" colspan="3">{{ $category->title }}</td>
                    <td class="price-type"></td>
                </tr>
                @include( '/ixavier-libraries/menu-templates/valentines/table-list-products', ['category'=>$category] )
            @else
                <tr class="category category-{{ $category->slug }} category-{{ $categoryIndex }}">
                    <td class="title" colspan="3">{{ $category->title }}</td>
                </tr>
                <tr class="products">
                    <td colspan="3" class="category-{{ $category->slug }} category-{{ $categoryIndex }}">
                        @include( '/ixavier-libraries/menu-templates/valentines/block-list-products', ['category'=>$category] )
                    </td>
                </tr>
            @endif
            <?php $categoryIndex++ ?>
        @endforeach
    </table>

    <hr/>
    <div class="footer-note">
        <p>All entrees served with mashed potatoes.</p>
        <p>Offered Valentine's Weekend; Fri - Sun from 12 PM - 10 PM.</p>
    </div>
</div>
@include( '/ixavier-libraries/menu-templates/base/footer' )
