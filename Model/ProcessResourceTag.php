<?php

namespace Azuracom\ProcessBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProcessResourceTag
 *
 * @ORM\Table(name="process_resource_tag")
 * @ORM\Entity()
 */
class ProcessResourceTag
{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;


    /**
     * @var string
     *
     * @ORM\Column(name="class_name", type="string", length=255)
     * @Assert\Length(max = 255)
     */
    protected $className;

    /**
     * @var string
     *
     * @ORM\Column(name="resource_id", type="string", length=255,nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $resourceId;

    /**
     * @var string
     *
     * @ORM\Column(name="resource_code", type="string", length=255,nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $resourceCode;


    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text",nullable=true)
     */
    protected $comment;


    /**
     * @var Process
     * 
     * @ORM\ManyToOne(targetEntity="Process",inversedBy="resourceTags")
     * @ORM\JoinColumn(name="process_id", referencedColumnName="id")
     */
    protected $process;


    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * @param string $resourceId
     */
    public function setResourceId($resourceId)
    {
        $this->resourceId = $resourceId;
    }

    /**
     * @return string
     */
    public function getResourceCode()
    {
        return $this->resourceCode;
    }

    /**
     * @param string $resourceCode
     */
    public function setResourceCode($resourceCode)
    {
        $this->resourceCode = $resourceCode;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return \Azuracom\ProcessBundle\Model\Process
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param \Azuracom\ProcessBundle\Model\Process $process
     */
    public function setProcess($process)
    {
        $this->process = $process;
    }

    /**
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }
}
