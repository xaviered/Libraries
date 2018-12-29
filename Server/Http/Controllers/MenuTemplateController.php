<?php

namespace ixavier\Libraries\Server\Http\Controllers;

use Illuminate\Http\Request;
use ixavier\Libraries\Server\RestfulRecords\MenuTemplates\MenuTemplate;

class MenuTemplateController extends BaseController
{
    public function preview(Request $request, string $slug)
    {
        $template = (new MenuTemplate($slug))
            ->loadData($request->get('menu'))
            ->setMediaType($request->get('backgroundType') ?? 'web');

        return view('ixavier-libraries/menu-templates/template-preview', ['template' => $template]);
    }
}
