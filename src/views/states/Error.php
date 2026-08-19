<?php

declare(strict_types=1);

namespace App\Views\States;

use AML\View\Page;
use AML\View\View;
use function AML\View\{Heading, Link, Text, VStack};

final class ErrorPage extends Page
{
    public function body(): View
    {
        return VStack(
            Text('APPLICATION ERROR')->class('eyebrow'),
            Heading('Something went wrong.')->size(48)->bold(),
            Text('The incident was contained. You can safely try again.'),
            Link('Return home', '/')->class('button', 'button-primary'),
        )->gap(20)->padding(40)->class('shell', 'route-state');
    }
}