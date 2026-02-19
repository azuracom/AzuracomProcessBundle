<?php

namespace Azuracom\ProcessBundle\Handler;

use Azuracom\ProcessBundle\Helper\ProcessHelperInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;

abstract class AbstractHandler implements HandlerInterface
{
    /** @var ProcessHelperInterface|null */
    protected $helper;

    /** @var ProcessInterface|null */
    protected $process;


    public function isEligible(ProcessInterface $process): bool
    {
        return in_array($process->getType(), static::getTypes());
    }

    public function configure(): void
    {
        if ($this->process && $this->helper) {
            $this->helper->setSubject($this->process);
        }
    }

    public static function getTypes(): array
    {
        $oClass = new \ReflectionClass(static::class);
        $types = [];
        foreach ($oClass->getConstants() as $constName => $constValue) {
            if (preg_match("#^TYPE_#", $constName)) {
                $types[] = $constValue;
            }
        }

        if(empty($types)) {
            $types[] = static::class;
        }

        return $types;
    }

    public static function getTypeLabel(?string $type = null): string
    {
        return "app.process.type." . $type;
    }

    /**
     * Get the value of process
     */
    public function getProcess(): ?ProcessInterface
    {
        return $this->process;
    }

    /**
     * Set the value of process
     *
     * @return  self
     */
    public function setProcess(?ProcessInterface $process): self
    {
        $this->process = $process;

        return $this;
    }

    /**
     * Get the value of helper
     */
    public function getHelper(): ?ProcessHelperInterface
    {
        return $this->helper;
    }

    /**
     * Set the value of helper
     *
     * @return  self
     */
    public function setHelper(?ProcessHelperInterface $helper): self
    {
        $this->helper = $helper;

        return $this;
    }
}
