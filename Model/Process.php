<?php

namespace Azuracom\ProcessBundle\Model;

use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Process
 * @Vich\Uploadable
 */
class Process implements ResourceInterface, ProcessInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $uniqueId;

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="process", fileNameProperty="filename")
     */
    protected $file;

    /**
     * @var string
     */
    protected $originalFilename;

    /**
     * @var string
     */
    protected $filename;


    /**
     * @var \DateTime
     */
    protected $startedAt;

    /**
     * @var \DateTime
     */
    protected $endedAt;

    /**
     * @var string
     */
    protected $status = self::STATUS_NEW;

    /**
     * @var boolean
     */
    protected $resolved = true;

    /**
     * @var array
     */
    protected $options = array();


    protected $user;


    protected $resourceTags;

    /**
     * @var \DateTime $created
     */
    protected $createdAt;

    /**
     * @var \DateTime $updated
     */
    protected $updatedAt;

    /**
     * @var Query[]
     */
    protected $queries = [];


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
        return $this->id ? $this->getType() . " - " . $this->id : "Nouveau processus";
    }

    public function generateUniqueId(): void
    {
        $this->uniqueId = uniqid();
    }


    public function startProcess(): ProcessInterface
    {
        $this->startedAt = new \DateTime();
        $this->resetRessourceTags();

        return $this;
    }

    public function endProcess(): ProcessInterface
    {
        if ($this->status == self::STATUS_NEW) {
            $this->status = self::STATUS_SUCCEDED;
        }

        $this->endedAt = new \DateTime();

        return $this;
    }

    public function resetRessourceTags(): ProcessInterface
    {
        $this->resourceTags = new ArrayCollection();

        return $this;
    }

    public function getResourceTags()
    {
        return $this->resourceTags;
    }

    public function addRessourceTag(ProcessResourceTagInterface $resourceTag): ProcessInterface
    {
        $this->resourceTags->add($resourceTag);

        return $this;
    }

    public function removeResourceTag(ProcessResourceTagInterface $resourceTag): ProcessInterface
    {
        $this->resourceTags->remove($resourceTag);

        return $this;
    }

    public function getStatusColor(): string
    {
        return self::getStatusColorStatic($this->status);
    }

    public static function getStatusColorStatic(string $status): string
    {
        $status = strtolower($status);
        if(preg_match("#warning#",$status)){
            return 'warning';
        }

        if(preg_match("#error#",$status)){
            return 'danger';
        }

        if(preg_match("#succeded|notice#",$status)){
            return 'success';
        }

        return 'default';

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
    public function setType($type): ProcessInterface
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType(): string
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
    public function setUser(?UserInterface $user = null): ProcessInterface
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return UserInterface
     */
    public function getUser(): ?UserInterface
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
    public function setFile(File $file = null): ProcessInterface
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();

            if ($file instanceof UploadedFile) {
                $this->originalFilename = $file->getClientOriginalName();
            }
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * Set filename
     *
     * @param string $filename
     *
     * @return Process
     */
    public function setFilename($filename): ProcessInterface
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }


    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Process
     */
    public function setCreatedAt(?\DateTime $createdAt): ProcessInterface
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt(): ?\DateTime
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
    public function setUpdatedAt(?\DateTime $updatedAt): ProcessInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): ?\DateTime
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
    public function setOptions(array $options): ProcessInterface
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions(): array
    {
        if (!is_array($this->options)) {
            $this->options = array();
        }

        return $this->options;
    }

    public function setOption(string $key, $value): ProcessInterface
    {
        if (is_string($key) && is_string($value)) {
            $this->options[$key] = $value;
        }

        return $this;
    }

    public function getOption(string $key)
    {
        if (isset($this->getOptions()[$key])) {
            return $this->getOptions()[$key];
        }
        return null;
    }

    /**
     * Set originalFilename.
     *
     * @param string|null $originalFilename
     *
     * @return Process
     */
    public function setOriginalFilename(?string $originalFilename = null): ProcessInterface
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    /**
     * Get originalFilename.
     *
     * @return string|null
     */
    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }
    /**
     * @return \DateTime
     */
    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    /**
     * @param \DateTime $startedAt
     */
    public function setStartedAt(?\DateTime $startedAt): ProcessInterface
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndedAt(): ?\DateTime
    {
        return $this->endedAt;
    }

    /**
     * @param \DateTime $endedAt
     */
    public function setEndedAt(?\DateTime $endedAt): ProcessInterface
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): ProcessInterface
    {
        $this->status = $status;

        return $this;
    }
    /**
     * @return boolean
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * @param boolean $resolved
     */
    public function setResolved(bool $resolved): ProcessInterface
    {
        $this->resolved = $resolved;

        return $this;
    }

    /**
     * Get the value of uniqueId
     *
     * @return  string
     */
    public function getUniqueId(): ?string
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
    public function setUniqueId(?string $uniqueId): ProcessInterface
    {
        $this->uniqueId = $uniqueId;

        return $this;
    }

    /**
     * Get the value of queries
     *
     * @return  Query[]
     */ 
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * Set the value of queries
     *
     * @param  Query[]  $queries
     *
     * @return  self
     */ 
    public function setQueries($queries) : ProcessInterface
    {
        $this->queries = $queries;

        return $this;
    }

    public function addQuery(Query $query) : ProcessInterface
    {
        $this->queries[] = $query;

        return $this;
    }

    public function removeQuery(Query $query) : ProcessInterface
    {
        foreach ($this->queries as $key => $tmp) {
            if($tmp === $query){
                unset($this->queries[$key]);
                break;
            }
        }

        return $this;
    }
}
