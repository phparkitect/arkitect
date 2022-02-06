<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E\Fixtures\MvcExample\Controller;

use Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface;

class CatalogController implements ContainerAwareInterface
{
    public function viewAction(string $id): void
    {
    }

    public function listAction(): void
    {
    }
}
