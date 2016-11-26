<?php

/*
 * The MIT License
 *
 * Copyright 2016 David Salbei.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Incolab\ForumBundle\Model;

use Incolab\DBALBundle\Service\DBALService;
use Incolab\CoreBundle\Transliterator\Transliterator;
use Incolab\ForumBundle\Entity\Topic;
use Incolab\ForumBundle\Repository\TopicRepository;
use Incolab\ForumBundle\Repository\PostRepository;
use Incolab\ForumBundle\Repository\CategoryRepository;
/**
 * Description of TopicManager
 *
 * @author David Salbei
 */

class TopicManager
{
    private $transliterator;
    private $database;
    
    public function __construct(Transliterator $transliterator, DBALService $database)
    {
        $this->transliterator = $transliterator;
        $this->database = $database;
    }
    
    public function add(Topic $topic)
    {
        $topic->setSlug($this->transliterator->urlize($topic->getSubject()))
                ->setLastPost(null);
        $topicRepository = $this->database->getRepository("IncolabForumBundle:Topic");
        $topicUp = $topicRepository->persist($topic);
        
        $firstPost = $topicUp->getFirstPost();
        $firstPost->setTopic($topicUp)
                ->setAuthor($topic->getAuthor())
                ->setCreatedAt(new \DateTime());
        $postRepository = $this->database->getRepository("IncolabForumBundle:Post");
        $firstPostUp = $postRepository->persist($firstPost);
        $topicUp->setFirstPost($firstPostUp);
        $topicRepository->persist($topicUp);
        
        $category = $topicUp->getCategory();
        $category->setLastTopic($topicUp)
                ->incrementNumTopics()
                ->incrementNumPosts();
        
        $parentCat = $category->getParent();
        $parentCat->setLastTopic($topicUp)
                ->incrementNumTopics()
                ->incrementNumPosts();
        
        $categoryRepository = $this->database->getRepository("IncolabForumBundle:Category");
        $categoryRepository->persist($parentCat);
        $categoryRepository->persist($category);
    }
    
    public function save(Topic $topic)
    {
        $topicRepository = $this->database->getRepository("IncolabForumBundle:Topic");
        $topicRepository->persist($topic);
    }
}
