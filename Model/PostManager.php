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
use Incolab\ForumBundle\Entity\Post;

/**
 * Description of PostManager
 *
 * @author David Salbei
 */
class PostManager
{
    
    private $database;
    
    public function __construct(DBALService $database)
    {
        $this->database = $database;
    }
    
    public function add(Post $post)
    {
        $postRepository = $this->database->getRepository("IncolabForumBundle:Post");
        $postUp = $postRepository->persist($post);
        
        $topic = $postUp->getTopic();
        
        $topic->incrementNumPosts();
        $topic->setLastPost($postUp);
        $topicRepository = $this->database->getRepository("IncolabForumBundle:Topic");
        $topicUp = $topicRepository->persist($topic);
        
        $category = $topic->getCategory();
        $category->incrementNumPosts()
                ->setLastPost($postUp);
        
        $parent = $category->getParent();
        $parent->incrementNumPosts()
                ->setLastPost($postUp);
        
        $categoryRepository = $this->database->getRepository("IncolabForumBundle:Category");
        $categoryRepository->persist($parent);
        $categoryRepository->persist($category);
    }
    
    public function delete(Post $post)
    {
        $this->database->getRepository("IncolabForumBundle:Post")->remove($post);
        /*
        $topicRepository = $this->database->getRepository('IncolabForumBundle:Topic');
        $topic = $topicRepository->getTopic($slugTopic, $slugCat, $slugParentCat);
        
         * 
         */
        
        $topic = $post->getTopic();
        $category = $topic->getCategory();
    }
}
