<?php

namespace Silnik\ORM;

use Doctrine\ORM\Mapping as ORM;

abstract class AbstractEntity
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected $id;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: true)]
    protected $createdat;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'updatedAt', type: 'datetime', nullable: true)]
    protected $updatedat;

    /**
     * Get the value of createdat
     */
    public function getCreatedat()
    {
        return $this->createdat;
    }

    /**
     * Set the value of createdat
     */
    public function setCreatedat($createdat): self
    {
        $this->createdat = $createdat;

        return $this;
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of updatedat
     */
    public function getUpdatedat()
    {
        return $this->updatedat;
    }

    /**
     * Set the value of updatedat
     */
    public function setUpdatedat($updatedat): self
    {
        $this->updatedat = $updatedat;

        return $this;
    }
}