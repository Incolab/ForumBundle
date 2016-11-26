<?php

namespace Incolab\ForumBundle\Entity;

/**
 * Category
 */

class Category
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $parent;
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $readRoles;
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $writeRoles;
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $childs;

    /**
     * @var string
     */
    private $slug;

    /**
     * @var int
     */
    private $position;
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $topics;

    /**
     * @var int
     */
    private $numTopics;

    /**
     * @var int
     */
    private $numPosts;

    /**
     * @var \Incolab\ForumBundle\Entity\Topic
     */
    private $lastTopic;

    /**
     * @var \Incolab\ForumBundle\Entity\Post
     */
    private $lastPost;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->parent = null;
        $this->childs = new \Doctrine\Common\Collections\ArrayCollection();
        $this->topics = new \Doctrine\Common\Collections\ArrayCollection();
        $this->readRoles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->writeRoles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->numPosts = 0;
        $this->numTopics = 0;
    }
    
    /**
     * Set id
     *
     * @param string $id
     *
     * @return Category
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Category
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Category
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return Category
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set position
     *
     * @param integer $position
     *
     * @return Category
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }
    
    /**
     * Increments the category position
     *
     * @return Category
     */
    public function incrementPosition()
    {
        $this->position++;
        
        return $this;
    }
    
    /**
     * Decrements the category position
     *
     * @return Category
     */
    public function decrementPosition()
    {
        $this->position--;
        
        return $this;
    }


    /**
     * Set numTopics
     *
     * @param integer $numTopics
     *
     * @return Category
     */
    public function setNumTopics($numTopics)
    {
        $this->numTopics = $numTopics;

        return $this;
    }

    /**
     * Get numTopics
     *
     * @return integer
     */
    public function getNumTopics()
    {
        return $this->numTopics;
    }
    
    /**
     * Increments the number of topics
     *
     * @return Category
     */
    public function incrementNumTopics()
    {
        $this->numTopics++;
        
        return $this;
    }
    
    /**
     * Decrements the number of topics
     *
     * @return Category
     */
    public function decrementNumTopics()
    {
        $this->numTopics--;
        
        return $this;
    }


    /**
     * Set numPosts
     *
     * @param integer $numPosts
     *
     * @return Category
     */
    public function setNumPosts($numPosts)
    {
        $this->numPosts = $numPosts;

        return $this;
    }

    /**
     * Get numPosts
     *
     * @return integer
     */
    public function getNumPosts()
    {
        return $this->numPosts;
    }
    
    /**
     * Increments the number of posts
     *
     * @return Category
     */
    public function incrementNumPosts()
    {
        $this->numPosts++;
        
        return $this;
    }
    
    /**
     * Decrements the number of posts
     *
     * @return Category
     */
    public function decrementNumPosts()
    {
        $this->numPosts--;
        
        return $this;
    }


    /**
     * Add child
     *
     * @param \Incolab\ForumBundle\Entity\Category $child
     *
     * @return Category
     */
    public function addChild(\Incolab\ForumBundle\Entity\Category $child)
    {
        if (!$this->hasChild($child)) {
            $this->childs[] = $child;
        }

        return $this;
    }

    /**
     * Remove child
     *
     * @param \Incolab\ForumBundle\Entity\Category $child
     */
    public function removeChild(\Incolab\ForumBundle\Entity\Category $child)
    {
        $this->childs->removeElement($child);
    }

    /**
     * Get childs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChilds()
    {
        return $this->childs;
    }
    
    /**
     * Get child by slug
     *
     * @return Category
     */
    public function getChildBySlug($slug)
    {
        foreach ($this->childs as $child) {
            if ($child->getSlug() === $slug) {
                return $child;
            }
        }
    }
    
    public function hasChild(\Incolab\ForumBundle\Entity\Category $category) {
        foreach ($this->childs as $elmt) {
            if ($elmt->getId() === $category->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add topic
     *
     * @param \Incolab\ForumBundle\Entity\Topic $topic
     *
     * @return Category
     */
    public function addTopic(\Incolab\ForumBundle\Entity\Topic $topic)
    {
        if (!$this->hasTopic($topic)) {
            $this->topics[] = $topic;
        }

        return $this;
    }

    /**
     * Remove topic
     *
     * @param \Incolab\ForumBundle\Entity\Topic $topic
     */
    public function removeTopic(\Incolab\ForumBundle\Entity\Topic $topic)
    {
        $this->topics->removeElement($topic);
    }
    
    public function hasTopic(\Incolab\ForumBundle\Entity\Topic $topic) {
        foreach ($this->topics as $elmt) {
            if ($elmt->getId() == $topic->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get topics
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
     * Set parent
     *
     * @param \Incolab\ForumBundle\Entity\Category $parent
     *
     * @return Category
     */
    public function setParent(\Incolab\ForumBundle\Entity\Category $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Incolab\ForumBundle\Entity\Category
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set lastTopic
     *
     * @param \Incolab\ForumBundle\Entity\Topic $lastTopic
     *
     * @return Category
     */
    public function setLastTopic(\Incolab\ForumBundle\Entity\Topic $lastTopic = null)
    {
        $this->lastTopic = $lastTopic;

        return $this;
    }

    /**
     * Get lastTopic
     *
     * @return \Incolab\ForumBundle\Entity\Topic
     */
    public function getLastTopic()
    {
        return $this->lastTopic;
    }

    /**
     * Set lastPost
     *
     * @param \Incolab\ForumBundle\Entity\Post $lastPost
     *
     * @return Category
     */
    public function setLastPost(\Incolab\ForumBundle\Entity\Post $lastPost = null)
    {
        $this->lastPost = $lastPost;

        return $this;
    }

    /**
     * Get lastPost
     *
     * @return \Incolab\ForumBundle\Entity\Post
     */
    public function getLastPost()
    {
        return $this->lastPost;
    }

    /**
     * Add readRole
     *
     * @param \Incolab\ForumBundle\Entity\ForumRole $readRole
     *
     * @return Category
     */
    public function addReadRole(\Incolab\ForumBundle\Entity\ForumRole $readRole)
    {
        if (!$this->hasReadRole($readRole)) {
            $this->readRoles[] = $readRole;
        }

        return $this;
    }

    /**
     * Remove readRole
     *
     * @param \Incolab\ForumBundle\Entity\ForumRole $readRole
     */
    public function removeReadRole(\Incolab\ForumBundle\Entity\ForumRole $readRole)
    {
        $this->readRoles->removeElement($readRole);
    }

    /**
     * Get readRoles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReadRoles()
    {
        return $this->readRoles;
    }
    
    /**
     * Check readRole
     *
     * @param \Incolab\ForumBundle\Entity\ForumRole $readRole
     *
     * @return boolean
     */
    public function hasReadRole(\Incolab\ForumBundle\Entity\ForumRole $readRole)
    {
        foreach ($this->readRoles as $role) {
            if ($role == $readRole) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add writeRole
     *
     * @param \Incolab\ForumBundle\Entity\ForumRole $writeRole
     *
     * @return Category
     */
    public function addWriteRole(\Incolab\ForumBundle\Entity\ForumRole $writeRole)
    {
        if (!$this->hasWriteRole($writeRole)) {
            $this->writeRoles[] = $writeRole;
        }

        return $this;
    }

    /**
     * Remove writeRole
     *
     * @param \Incolab\ForumBundle\Entity\ForumRole $writeRole
     */
    public function removeWriteRole(\Incolab\ForumBundle\Entity\ForumRole $writeRole)
    {
        $this->writeRoles->removeElement($writeRole);
    }

    /**
     * Get writeRoles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWriteRoles()
    {
        return $this->writeRoles;
    }
    
    /**
     * Check writeRole
     *
     * @param \Incolab\ForumBundle\Entity\ForumRole $writeRole
     *
     * @return boolean
     */
    public function hasWriteRole(\Incolab\ForumBundle\Entity\ForumRole $writeRole)
    {
        foreach ($this->writeRoles as $role) {
            if ($role == $writeRole) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check writeRole
     *
     * @param \Incolab\ForumBundle\Entity\ForumRole $writeRole
     *
     * @return boolean
     */
    public function childHasWriteRoleBySlug(\Incolab\ForumBundle\Entity\ForumRole $writeRole, $slug)
    {
        $child = $this->getChildBySlug($slug);
        return $child->hasWriteRole($writeRole);
    }
}
