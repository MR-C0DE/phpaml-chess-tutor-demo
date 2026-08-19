<?php

declare(strict_types=1);

namespace App\Views\States;

use AML\View\Page;
use AML\View\View;
use function AML\View\{Heading, Link, Text, VStack};

final class NotFoundPage extends Page
{
    public function body(): View
    {
        return VStack(
            Text('404')->class('eyebrow'),
            Heading('This page does not exist.')->size(48)->bold(),
            Text('No AML View page matches ' . $this->param('path', '/')),
            Link('Return home', '/')->class('button', 'button-primary'),
        )->gap(20)->padding(40)->class('shell', 'route-state');
    }
}