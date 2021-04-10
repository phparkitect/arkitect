<?php
declare(strict_types=1);

namespace Arkitect\Analyzer\Events;

use Arkitect\Analyzer\ClassDescription;
use Symfony\Contracts\EventDispatcher\Event;

class ClassAnalyzed extends Event
{
    /** @var \Arkitect\Analyzer\ClassDescription */
    private $cd;

    public function __construct(ClassDescription $cd)
    {
        $this->cd = $cd;
    }

    public function getClassDescription(): ClassDescription
    {
        return $this->cd;
    }
}
