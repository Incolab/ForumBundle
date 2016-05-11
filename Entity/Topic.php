<?php

namespace Incolab\ForumBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Topic
 */
class Topic
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 10,
     *      minMessage = "Votre réponse doit contenir {{ limit }} caractères minimum"
     * )
     */
    private $subject;

    /**
     * @var int
     */
    private $author;

    /**
     * @var string
     */
    private $slug;

    /**
     * @var int
     */
    private $numViews;

    /**
     * @var int
     */
    private $numPosts;

    /**
     * @var bool
     */
    private $isClosed;

    /**
     * @var bool
     */
    private $isPinned;

    /**
     */
    private $isBuried;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $pulledAt;

    /**
     * @var int
     */
    private $category;

    /**
     * @var \stdClass
     */
    private $firstPost;

    /**
     * @var \stdClass
     */
    private $lastPost;
    
    /**
     * @var \stdClass
     */
    private $replies;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->numViews = 0;
        $this->numPosts = 1;
        $this->isClosed = false;
        $this->isBuried = false;
        $this->isPinned = false;
        $this->createdAt = new \DateTime();
        $this->replies = new \Doctrine\Common\Collections\ArrayCollection();
        
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
     * Set subject
     *
     * @param string $subject
     *
     * @return Topic
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set author
     *
     * @param integer $author
     *
     * @return Topic
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return integer
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return Topic
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
     * Set numViews
     *
     * @param integer $numViews
     *
     * @return Topic
     */
    public function setNumViews($numViews)
    {
        $this->numViews = $numViews;

        return $this;
    }

    /**
     * Get numViews
     *
     * @return integer
     */
    public function getNumViews()
    {
        return $this->numViews;
    }

    /**
     * Set numPosts
     *
     * @param integer $numPosts
     *
     * @return Topic
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
     */
    public function incrementNumPosts()
    {
        $this->numPosts++;
    }
    
    /**
     * Decrements the number of posts
     */
    public function decrementNumPosts()
    {
        $this->numPosts--;
    }
    
    /**
     * Set isClosed
     *
     * @param boolean $isClosed
     *
     * @return Topic
     */
    public function setClosed($isClosed)
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    /**
     * Get isClosed
     *
     * @return boolean
     */
    public function isClosed()
    {
        return $this->isClosed;
    }

    /**
     * Set isPinned
     *
     * @param boolean $isPinned
     *
     * @return Topic
     */
    public function setPinned($isPinned)
    {
        $this->isPinned = $isPinned;

        return $this;
    }

    /**
     * Get isPinned
     *
     * @return boolean
     */
    public function isPinned()
    {
        return $this->isPinned;
    }

    /**
     * Set isBuried
     *
     * @param boolean $isBuried
     *
     * @return Topic
     */
    public function setBuried($isBuried)
    {
        $this->isBuried = $isBuried;

        return $this;
    }

    /**
     * Get isBuried
     *
     * @return boolean
     */
    public function isBuried()
    {
        return $this->isBuried;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Topic
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
     * Set pulledAt
     *
     * @param \DateTime $pulledAt
     *
     * @return Topic
     */
    public function setPulledAt($pulledAt)
    {
        $this->pulledAt = $pulledAt;

        return $this;
    }

    /**
     * Get pulledAt
     *
     * @return \DateTime
     */
    public function getPulledAt()
    {
        return $this->pulledAt;
    }

    /**
     * Set firstPost
     *
     * @param \stdClass $firstPost
     *
     * @return Topic
     */
    public function setFirstPost($firstPost)
    {
        $this->firstPost = $firstPost;

        return $this;
    }

    /**
     * Get firstPost
     *
     * @return \stdClass
     */
    public function getFirstPost()
    {
        return $this->firstPost;
    }

    /**
     * Set lastPost
     *
     * @param \stdClass $lastPost
     *
     * @return Topic
     */
    public function setLastPost($lastPost)
    {
        $this->lastPost = $lastPost;

        return $this;
    }

    /**
     * Get lastPost
     *
     * @return \stdClass
     */
    public function getLastPost()
    {
        return $this->lastPost;
    }

    /**
     * Set category
     *
     * @param \Incolab\ForumBundle\Entity\Category $category
     *
     * @return Topic
     */
    public function setCategory(\Incolab\ForumBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Incolab\ForumBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Add reply
     *
     * @param \Incolab\ForumBundle\Entity\Post $reply
     *
     * @return Topic
     */
    public function addReply(\Incolab\ForumBundle\Entity\Post $reply)
    {
        $this->replies[] = $reply;

        return $this;
    }

    /**
     * Remove reply
     *
     * @param \Incolab\ForumBundle\Entity\Post $reply
     */
    public function removeReply(\Incolab\ForumBundle\Entity\Post $reply)
    {
        $this->replies->removeElement($reply);
    }

    /**
     * Get replies
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReplies()
    {
        return $this->replies;
    }
}
