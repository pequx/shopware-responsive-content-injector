<?php

namespace PhagResponsiveContentInjector\Models;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="s_plugin_phag_responsive_content_injector")
 * @ORM\Entity(repositoryClass="Repository")
 */
class PhagResponsiveContentInjector extends ModelEntity
{
    /**
     * Primary Key - autoincrement value
     *
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer $active
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @var string $name
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;

    /**
     * @var string $element
     * @ORM\Column(name="element", type="text", nullable=false)
     */
    private $element;

    /**
     * @var integer $lastParentId
     * @ORM\Column(name="last_parent_id", type="integer")
     */
    protected $lastParentId;

    /**
     * @var \Shopware\Models\Blog\Blog
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Blog\Blog")
     * @ORM\JoinColumn(name="last_parent_id", referencedColumnName="id")
     *
     */
    protected $lastParent;
    //@todo: create a reference table with parent->element relation history?

    /**
     * @var \DateTime $createdAt
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime $updatedAt
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private $updatedAt;

    /**
     * @var integer $renderCount
     * @ORM\Column(name="render_count", type="integer")
     */
    private $renderCount;

    /**
     * @var integer $viewCount
     * @ORM\Column(name="view_count", type="integer")
     */
    private $viewCount;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Getters
     */

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function getActive() //@todo: change to isActive
    {
        return $this->active;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @return ModelEntity
     */
    public function getLastParent()
    {
        return $this->lastParent;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return integer
     */
    public function getRenderCount()
    {
        return $this->renderCount;
    }

    /**
     * @return integer
     */
    public function getViewCount()
    {
        return $this->viewCount;
    }

    /**
     * Setters
     */

    /**
     * @param $active integer
     */
    public function setIsActive($active)
    {
        $this->active = $active;
    }

    /**
     * @param $name string
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param $element string
     */
    public function setElement($element)
    {
        $this->element = $element;
    }

    /**
     * @param $lastParent ModelEntity
     */
    public function setLastParent($lastParent)
    {
        $this->lastParent = $lastParent;
    }

    /**
     * @param $createdAt \DateTime
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @param $updatedAt \DateTime
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @param $renderCount integer
     */
    public function setRenderCount($renderCount)
    {
        $this->renderCount = $renderCount;
    }

    /**
     * @param $viewCount integer
     */
    public function setViewCount($viewCount)
    {
        $this->viewCount = $viewCount;
    }
}
