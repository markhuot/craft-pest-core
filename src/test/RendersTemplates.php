<?php

namespace markhuot\craftpest\test;

trait RendersTemplates
{
    public function expectTemplate(string $template, array $data=[])
    {
        $results = \Craft::$app->view->renderTemplate($template, $data);

        return expect($results);
    }
}
