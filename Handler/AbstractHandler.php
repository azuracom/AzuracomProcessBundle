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
        return $process->getType() == self::getType();
    }

    public function configure(): void
    {
        if($this->process && $this->helper){
            $this->helper->setSubject($this->process);
        }
    }

    public static function getType(): string
    {
        if (preg_match('~([^\\\\]+?)$~i', static::class, $matches)) {
            return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $matches[1]));
        }

        return null;
    }

    public static function getTypeLabel(): string
    {
        return static::getType();
    }

    /**
     * Get the value of process
     */ 
    public function getProcess() : ?ProcessInterface
    {
        return $this->process;
    }

    /**
     * Set the value of process
     *
     * @return  self
     */ 
    public function setProcess(?ProcessInterface $process) :self
    {
        $this->process = $process;

        return $this;
    }

    /**
     * Get the value of helper
     */ 
    public function getHelper() : ?ProcessHelperInterface
    {
        return $this->helper;
    }

    /**
     * Set the value of helper
     *
     * @return  self
     */ 
    public function setHelper(?ProcessHelperInterface $helper) : self
    {
        $this->helper = $helper;

        return $this;
    }
}