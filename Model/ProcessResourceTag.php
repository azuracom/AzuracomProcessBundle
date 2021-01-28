<?php

namespace Azuracom\ProcessBundle\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

class ProcessResourceTag implements ResourceInterface, ProcessResourceTagInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $resourceId;

    /**
     * @var string
     */
    protected $resourceCode;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var Process
     */
    protected $process;

    /**
     * @return string
     */
    public function getClassName() : string
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName($className) : ProcessResourceTagInterface
    {
        $this->className = $className;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getResourceId() : string
    {
        return $this->resourceId;
    }

    /**
     * @param string $resourceId
     */
    public function setResourceId(string $resourceId) : ProcessResourceTagInterface
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    /**
     * @return string
     */
    public function getResourceCode() :?string
    {
        return $this->resourceCode;
    }

    /**
     * @param string $resourceCode
     */
    public function setResourceCode(?string $resourceCode) : ProcessResourceTagInterface
    {
        $this->resourceCode = $resourceCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment() : string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment(?string $comment) : ProcessResourceTagInterface
    {
        $this->comment = $comment;

        return $this;
    }


    public function getProcess() : ProcessInterface
    {
        return $this->process;
    }

    public function setProcess(ProcessInterface $process) : ProcessResourceTagInterface
    {
        $this->process = $process;

        return $this;
    }

    /**
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }
}
