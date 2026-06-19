<?php

namespace Azuracom\ProcessBundle\Entity;

use Azuracom\ProcessBundle\Model\ProcessResourceTag as BaseProcessResourceTag;
use Doctrine\ORM\Mapping as ORM;

/**
 * Default concrete ProcessResourceTag entity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'process_resource_tag')]
class ProcessResourceTag extends BaseProcessResourceTag
{
}
