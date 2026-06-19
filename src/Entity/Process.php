<?php

namespace Azuracom\ProcessBundle\Entity;

use Azuracom\ProcessBundle\Model\Process as BaseProcess;
use Doctrine\ORM\Mapping as ORM;

/**
 * Default concrete Process entity. Projects wanting to add their own fields can declare their own
 * entity extending Azuracom\ProcessBundle\Model\Process and point azuracom_process.resources.process.classes.model
 * to it.
 */
#[ORM\Entity]
#[ORM\Table(name: 'process')]
class Process extends BaseProcess
{
}
