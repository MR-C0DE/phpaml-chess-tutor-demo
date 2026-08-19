<?php

declare(strict_types=1);

namespace App\Views\Pages\About;

use AML\View\Page;
use AML\View\PageMetadata;
use AML\View\Shared;
use AML\View\State;
use AML\View\View;
use AML\Engine\StateRef;
use function AML\View\{Heading, Link, Text, VStack};

final class AboutPage extends Page
{
    #[State, Shared('demo.count')]
    public int $count = 0;

    public function metadata(): PageMetadata
    {
        return (new PageMetadata())->title('About — PHPAML View')->description('Frontend navigation powered by PHPAML Engine.');
    }

    public function body(): View
    {
        return VStack(
            Text('CLIENT ROUTER')->class('eyebrow'),
            Heading('Navigation without a page reload')->size(48)->bold(),
            Text('PHPAML Engine loads this route, updates the document history and keeps the frontend runtime mounted.'),
            Text(StateRef::to('count', $this->count))->class('shared-counter'),
            Link('Return home', '/')->class('button', 'button-primary'),
        )->gap(20)->padding(40)->class('shell');
    }
}