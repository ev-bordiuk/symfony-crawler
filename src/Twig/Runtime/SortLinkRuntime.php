<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SortLinkRuntime implements RuntimeExtensionInterface
{
    public function __construct(private UrlGeneratorInterface $router)
    {
        // Inject dependencies if needed
    }

    public function sortLink($field, $sort = null, $order = null, $title = '')
    {
        $query = ['sort' => $field];
        $query['order'] = ($field == $sort)? $this->nextOrder($order) : 'DESC';
        $arrow = $query['order'] == 'DESC' ? '&#x25BC' : '&#x25B2;';

        return sprintf(
            '<a href="%s">%s %s</a>',
            $this->router->generate('app_results', $query),
            $title,
            $arrow
        );
    }

    private function nextOrder($order)
    {
        return $order == 'ASC' ? 'DESC' : 'ASC';
    }
}
