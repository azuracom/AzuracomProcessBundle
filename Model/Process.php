<?php

namespace Azuracom\ProcessBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Process
 *
 * @ORM\Table(name="process")
 * @ORM\Entity()
 * @Vich\Uploadable
 */
class Process
{
    const STATUS_NEW = "new";
    const STATUS_HAS_WARNING = 'has_warning';
    const STATUS_HAS_ERROR = 'has_error';
    const STATUS_SUCCEDED = 'succeded';

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
     * @ORM\Column(name="type", type="string", length=100)
     * @Assert\Length(max = 100)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="unique_id", type="string", length=255)
     * @Assert\Length(max = 255)
     */
    protected $uniqueId;    

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="process", fileNameProperty="fileName")
     * @Assert\File(maxSize="2M")
     */
    protected $file;


    /**
     * @var string
     *
     * @ORM\Column(name="original_file_name", type="string", length=255,nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $originalFileName;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=255,nullable=true)
     * @Assert\Length(max = 255)
     */
    protected $fileName;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    protected $startedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ended_at", type="datetime", nullable=true)
     */
    protected $endedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     * @Assert\Length(max = 255)
     */
    protected $status = self::STATUS_NEW;

    /**
     * @var boolean
     *
     * @ORM\Column(name="resolved", type="boolean")
     */
    protected $resolved = true;

    /**
     * @var array
     *
     * @ORM\Column(name="options", type="array")
     */
    protected $options = array();

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="ProcessResourceTag", mappedBy="process",cascade={"persist"},orphanRemoval=true)
     */
    protected $resourceTags;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at",type="datetime",nullable=true)
     */
    protected $createdAt;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at",type="datetime",nullable=true)
     */
    protected $updatedAt;


    public function __construct($type = null, $autoStart = true)
    {
        $this->resourceTags = new ArrayCollection();

        $this->type = $type;
        if ($autoStart) {
            $this->startProcess();
        }
    }

    public function __toString()
    {
        return $this->id ? $this->getTypeName() . " - " . $this->id : "Nouveau processus";
    }

    public function generateUniqueId()
    {
        $this->uniqueId = uniqid();
    }

    public function addRessourceTagByArray($value)
    {
        $resourceTag = new ProcessResourceTag();
        foreach ($value as $attr => $attrValue) {
            $resourceTag->{"set$attr"}($attrValue);
        }

        $resourceTag->setProcess($this);
        $this->resourceTags->add($resourceTag);
    }

    public static function createResourceTage($resource, string $comment = null)
    {
        if (!is_object($resource)) {
            return;
        }

        $resourceTag = new ProcessResourceTag();

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


    public function addRessourceTag($resource, string $comment = null)
    {
        $resourceTag = $this->createResourceTage($resource, $comment);
        $resourceTag->setProcess($this);

        $this->resourceTags->add($resourceTag);
    }

    public function startProcess()
    {
        $this->startedAt = new \DateTime();
        $this->resetRessourceTags();
    }

    public function endProcess()
    {
        if ($this->status == self::STATUS_NEW) {
            $this->status = self::STATUS_SUCCEDED;
        }

        $this->endedAt = new \DateTime();
    }

    public function resetRessourceTags()
    {
        $this->resourceTags = new ArrayCollection();
    }


    public static function getConstants()
    {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

    public static function getStatusList()
    {
        $list = array();
        foreach (self::getConstants() as $constName => $constValue) {
            if (preg_match("#^STATUS_#", $constName)) {
                $list[$constValue] = self::convertStatusToString($constValue);
            }
        }
        return $list;
    }

    public function getStatusName()
    {
        return self::convertStatusToString($this->status);
    }

    public static function convertStatusToString($status = null)
    {
        switch ($status) {
            case self::STATUS_NEW:
                return "En attente";
            case self::STATUS_HAS_WARNING:
                return "Avertissement";
            case self::STATUS_HAS_ERROR:
                return "Erreur";
            case self::STATUS_SUCCEDED:
                return "Réussi";
            default:
                return "Others";
        }
    }

    public function getStatusColor()
    {
        return self::getStatusColorStatic($this->status);
    }

    public static function getStatusColorStatic($status)
    {
        switch ($status) {
            case self::STATUS_HAS_ERROR:
                return 'danger';

            case self::STATUS_SUCCEDED:
                return 'success';

            case self::STATUS_HAS_WARNING:
                return 'warning';

            default:
                return 'default';
        }
    }


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Process
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * Set user
     *
     * @param UserInterface $user
     *
     * @return Process
     */
    public function setUser(UserInterface $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Process
     */
    public function setFile(File $file = null)
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();

            if ($file instanceof UploadedFile) {
                $this->originalFileName = $file->getClientOriginalName();
            }
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set fileName
     *
     * @param string $fileName
     *
     * @return Process
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }


    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Process
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Process
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set options
     *
     * @param array $options
     *
     * @return Process
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions()
    {
        if (!is_array($this->options)) {
            $this->options = array();
        }

        return $this->options;
    }

    public function setOption($key, $value)
    {
        if (is_string($key) && is_string($value)) {
            $this->options[$key] = $value;
        }
    }

    public function getOption($key)
    {
        if (isset($this->getOptions()[$key])) {
            return $this->getOptions()[$key];
        }
        return null;
    }

    /**
     * Set originalFileName.
     *
     * @param string|null $originalFileName
     *
     * @return Process
     */
    public function setOriginalFileName($originalFileName = null)
    {
        $this->originalFileName = $originalFileName;

        return $this;
    }

    /**
     * Get originalFileName.
     *
     * @return string|null
     */
    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }
    /**
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @param \DateTime $startedAt
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return \DateTime
     */
    public function getEndedAt()
    {
        return $this->endedAt;
    }

    /**
     * @param \DateTime $endedAt
     */
    public function setEndedAt($endedAt)
    {
        $this->endedAt = $endedAt;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
    /**
     * @return boolean
     */
    public function isResolved()
    {
        return $this->resolved;
    }

    /**
     * @param boolean $resolved
     */
    public function setResolved($resolved)
    {
        $this->resolved = $resolved;
    }

    /**
     * Get the value of uniqueId
     *
     * @return  string
     */ 
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * Set the value of uniqueId
     *
     * @param  string  $uniqueId
     *
     * @return  self
     */ 
    public function setUniqueId(string $uniqueId)
    {
        $this->uniqueId = $uniqueId;

        return $this;
    }
}
