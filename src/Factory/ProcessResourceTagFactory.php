<?php

namespace Azuracom\ProcessBundle\Factory;

use Azuracom\ProcessBundle\Model\ProcessResourceTagInterface;

class ProcessResourceTagFactory implements FactoryInterface
{
    public function __construct(
        private readonly string $processResourceTagClass,
    ) {}

    public function createNew(): ProcessResourceTagInterface
    {
        return new $this->processResourceTagClass();
    }

    public function createFromArray(array $array): ProcessResourceTagInterface
    {
        $resourceTag = $this->createNew();

        foreach ($array as $attr => $attrValue) {
            $resourceTag->{"set$attr"}($attrValue);
        }

        return $resourceTag;
    }

    public function createFromResource(mixed $resource, ?string $comment = null): ProcessResourceTagInterface
    {
        $resourceTag = $this->createNew();

        if (!is_object($resource)) {
            return $resourceTag;
        }

        $className = str_replace('\\', '', get_class($resource));

        //proxies specif
        if (preg_match("#Proxies__CG__#", $className)) {
            $className = str_replace('Proxies__CG__', '', $className);
        }

        $resourceTag->setClassName($className);
        $resourceTag->setComment($comment);

        if (method_exists($resource, 'getId')) {
            $resourceTag->setResourceId($resource->getId());
        }

        if (method_exists($resource, 'getCode')) {
            $resourceTag->setResourceCode($resource->getCode());
        }

        if (property_exists($resource, 'Id')) {
            $resourceTag->setResourceId($resource->Id);
        }

        return $resourceTag;
    }
}
