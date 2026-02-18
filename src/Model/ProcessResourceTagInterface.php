<?php

namespace Azuracom\ProcessBundle\Model;

interface ProcessResourceTagInterface
{
    public function getClassName(): string;

    public function setClassName(string $className): self;

    public function getResourceId(): string;

    public function setResourceId(string $resourceId): self;

    public function getResourceCode(): ?string;

    public function setResourceCode(?string $resourceCode): self;

    public function getComment(): ?string;

    public function setComment(?string $comment): self;

    public function getProcess(): ProcessInterface;

    public function setProcess(ProcessInterface $process): self;
}
