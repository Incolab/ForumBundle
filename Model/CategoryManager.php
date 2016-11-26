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

use Doctrine\Common\Collections\ArrayCollection;
use Incolab\DBALBundle\Service\DBALService;
use Incolab\CoreBundle\Transliterator\Transliterator;

use Incolab\ForumBundle\Entity\Category;

/**
 * Description of CategoryManager
 *
 * @author David Salbei
 */
class CategoryManager
{
    private $transliterator;
    private $database;
    
    public function __construct(Transliterator $transliterator, DBALService $database)
    {
        $this->transliterator = $transliterator;
        $this->database = $database;
    }
    
    public function addParent($categories, Category $category)
    {
        $this->save($category, $categories);
    }
    
    public function addChild(Category $category)
    {
         $this->save($category);
    }
    
    public function save(Category $category, $categories = [])
    {
        $category->setSlug($this->transliterator->urlize($category->getName()));
        
        $categoryRepository = $this->database->getRepository("IncolabForumBundle:Category");
        $categoryRepository->persist($category);
        
        $forumRoleRepository = $this->database->getRepository("IncolabForumBundle:ForumRole");
        $forumRoleRepository->persistCategoryRoles($category);
        
        // on change les positions des autres cat
        foreach ($categories as $element) {
            if ($category->getPosition() <= $element->getPosition()) {
                $element->incrementPosition();
                $categoryRepository->persist($element);
            }
        }
    }
}
