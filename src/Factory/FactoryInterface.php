<?php

namespace Azuracom\ProcessBundle\Factory;

/**
 * Minimal factory contract, replacing Sylius' FactoryInterface.
 */
interface FactoryInterface
{
    public function createNew(): object;
}
