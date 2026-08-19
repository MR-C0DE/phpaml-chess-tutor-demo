<?php

declare(strict_types=1);

namespace App\Views\States;

use AML\View\Page;
use AML\View\View;
use function AML\View\{Text, VStack};

final class LoadingPage extends Page
{
    public function body(): View
    {
        return VStack(
            Text('Loading…')->class('loading-title'),
            Text('AML View is preparing the next interface.'),
        )->gap(12)->padding(40)->class('shell', 'route-state');
    }
}