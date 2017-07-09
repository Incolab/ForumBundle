<?php

namespace Incolab\ForumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Incolab\ForumBundle\Entity\Category;
use Incolab\ForumBundle\Entity\ForumRole;
use Incolab\ForumBundle\Form\Type\CategoryType;
use Incolab\ForumBundle\Form\Type\ForumRoleType;

class AdminController extends Controller {

    public function indexAction(Request $request) {
        if ($request->isMethod('POST')) {

            return $this->forward('IncolabForumBundle:Admin:parentCategoryAdd', array('request' => $request));
        }

        $categories = $this->get("db")->getRepository('IncolabForumBundle:Category')->getParentsWithChilds();

        $form = $this->createForm(CategoryType::class, new Category());

        return $this->render(
                        'IncolabForumBundle:Admin:index.html.twig', array('categories' => $categories, 'parentCatForm' => $form->createView())
        );
    }

    public function parentCategoryAddAction(Request $request) {
        $categories = $this->get("db")->getRepository('IncolabForumBundle:Category')->getParentsWithChilds();

        $insertParentCat = new Category();
        $form = $this->createForm(CategoryType::class, $insertParentCat);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->container->get('incolab_forum.category_manager')->addParent($categories, $insertParentCat);

            $this->addFlash('notice', 'Category was added');

            return $this->redirectToRoute('incolab_forum_admin_homepage');
        }

        return $this->render(
                        'IncolabForumBundle:Admin:parentCategoryAdd.html.twig', array('parentCatForm' => $form->createView())
        );
    }

    public function parentCategoryModifyAction($slug, Request $request) {
        $category = $this->get("db")->getRepository('IncolabForumBundle:Category')->findParentBySlug($slug);
        $form = $this->createForm(CategoryType::class, $category);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->get("incolab_forum.category_manager")->save($category);
                $this->addFlash('notice', 'Category "' . $category->getName() . '" was modified');

                return $this->redirectToRoute('incolab_forum_admin_homepage');
            }
        }

        return $this->render(
                        'IncolabForumBundle:Admin:categoryModify.html.twig', array('catForm' => $form->createView())
        );
    }

    public function childCategoryAddAction($slugParent, Request $request) {
        $parentCat = $this->get("db")->getRepository('IncolabForumBundle:Category')->getParentCategoryBySlug($slugParent);

        if ($parentCat === null) {
            throw $this->createNotFoundException('Aucune catégorie à cette adresse');
        }
        if ($request->isMethod('POST')) {
            $category = new Category();
            $form = $this->createForm(CategoryType::class, $category);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $category->setParent($parentCat);
                $this->container->get('incolab_forum.category_manager')->save($category);

                return $this->redirectToRoute(
                                'incolab_forum_admin_child_category_add', array('slugParent' => $parentCat->getSlug())
                );
            }

            return $this->render(
                            'IncolabForumBundle:Admin:childCategoryAdd.html.twig', array('category' => $parentCat, 'childCatForm' => $form->createView())
            );
        }

        $form = $this->createForm(CategoryType::class, new Category());
        return $this->render(
                        'IncolabForumBundle:Admin:childCategoryAdd.html.twig', array('category' => $parentCat, 'childCatForm' => $form->createView())
        );
    }

    public function childCategoryModifyAction($slug, $parentSlug, Request $request) {
        $category = $this->get("db")->getRepository('IncolabForumBundle:Category')
                ->getCategory($slug, $parentSlug);

        if ($category === null) {
            throw $this->createNotFoundException('Aucune Catégorie pour cette adresse.');
        }

        $form = $this->createForm(CategoryType::class, $category);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->container->get('incolab_forum.category_manager')->save($category);
                $this->addFlash('notice', 'Category "' . $category->getName() . '" was modified');

                return $this->redirectToRoute('incolab_forum_admin_homepage');
            }
        }

        return $this->render(
                        'IncolabForumBundle:Admin:categoryModify.html.twig', array('catForm' => $form->createView())
        );
    }

    public function categoryDeleteAction($slugCat) {
        
    }

    public function topicDeleteAction($slugParentCat, $slugCat, $slugTopic) {
        $topicRepository = $this->get("db")->getRepository("IncolabForumBundle:Topic");
        $topic = $topicRepository->getTopic($slugTopic, $slugCat, $slugParentCat);

        if ($topic === null) {
            throw $this->createNotFoundException("This topic don\'t exists");
        }

        $category = $topic->getCategory();
        $parentCat = $category->getParent();
        $nbposts = $topic->getNumPosts();

        $category->setNumPosts($category->getNumPosts() - $nbposts);
        $category->setNumTopics($category->getNumTopics() - 1);

        $parentCat->setNumPosts($parentCat->getNumPosts() - $nbposts);
        $parentCat->setNumTopics($parentCat->getNumTopics() - 1);

        /*
         * FOREIGN KEY (topic_id) REFERENCES forum_topic(id) ON DELETE CASCADE
         * Donc on ne s'occupe pas des posts
         */
        $topicRepository->remove($topic);

        if ($category->getLastTopic() && $category->getLastTopic()->getId() === $topic->getId()) {
            $newLastTopic = $topicRepository->findLastOfCategory($category);
            $category->setLastTopic($newLastTopic);
        }
        if ($parentCat->getLastTopic() && $parentCat->getLastTopic()->getId() === $topic->getId()) {
            $newLastParentTopic = $topicRepository->findLastOfParentCategory($parentCat);
            $parentCat->setLastTopic($newLastParentTopic);
        }

        // on checke le lastpost
        $postRepository = $this->get("db")->getRepository('IncolabForumBundle:Post');
        $lastPostTopic = $topic->getLastPost();
        if ($category->getLastPost() && $category->getLastPost()->getId() === $lastPostTopic->getId()) {
            $newLastPost = $postRepository->findLastOfCategory($category);
            $category->setLastPost($newLastPost);
        }
        if ($parentCat->getLastPost() && $parentCat->getLastPost()->getId() === $lastPostTopic->getId()) {
            $parentCat->setLastPost(null);
        }

        $this->get("incolab_forum.category_manager")->save($category);
        $this->get("incolab_forum.category_manager")->save($parentCat);

        $this->addFlash("success", "The topic has been deleted");

        return $this->redirectToRoute('incolab_forum_admin_homepage');
    }

    public function postDeleteAction($slugParentCat, $slugCat, $slugTopic, $postId) {
        $postRepository = $this->get("db")->getRepository('IncolabForumBundle:Post');
        $post = $postRepository->getPost($slugParentCat, $slugCat, $slugTopic, $postId);

        if ($post === null) {
            throw $this->createNotFoundException('This post don\'t exists.');
        }

        $topicRepository = $this->get("db")->getRepository('IncolabForumBundle:Topic');
        $topic = $topicRepository->getTopic($slugTopic, $slugCat, $slugParentCat);
        $category = $topic->getCategory();
        $parentCat = $category->getParent();

        $topic->decrementNumPosts();
        $category->decrementNumPosts();
        $parentCat->decrementNumPosts();
        $this->get("incolab_forum.post_manager")->delete($post);
        if ($topic->getLastPost()->getId() === $post->getId()) {
            $newLastPost = $postRepository->findLastOfTopic($topic);
            $topic->setLastPost($newLastPost);
        }

        if ($category->getLastPost() && $category->getLastPost()->getId() === $post->getId()) {
            $newLastPost = $postRepository->findLastOfCategory($category);
            $category->setLastPost($newLastPost);
        }
        if ($parentCat->getLastPost() && $parentCat->getLastPost()->getId() === $post->getId()) {
            $parentCat->setLastPost(null);
        }

        $this->get("incolab_forum.category_manager")->save($category);
        $this->get("incolab_forum.category_manager")->save($parentCat);
        $this->get("incolab_forum.topic_manager")->save($topic);

        $this->addFlash('success', 'This post has been deleted');
        return $this->redirectToRoute(
                        'incolab_forum_topic_show', array(
                    'slugParentCat' => $post->getTopic()->getCategory()->getParent()->getSlug(),
                    'slugCat' => $post->getTopic()->getCategory()->getSlug(),
                    'slugTopic' => $post->getTopic()->getSlug()
                        )
        );
    }

    public function roleAddAction(Request $request) {
        $role = new ForumRole();
        $roleForm = $this->createForm(ForumRoleType::class, $role);
        if ($request->isMethod('POST')) {
            $roleForm->handleRequest($request);
            if ($roleForm->isSubmitted() && $roleForm->isValid()) {
                $forumRoleRepository = $this->get("db")->getRepository("IncolabForumBundle:ForumRole");
                $forumRoleRepository->persist($role);

                $this->addFlash("notice", "Le role " . $role->getName() . " à bien été ajouté.");
            }
        }
        return $this->render(
                        "IncolabForumBundle:Admin:forumRoleAdd.html.twig", array("form" => $roleForm->createView())
        );
    }

    public function roleDeleteAction() {
        
    }

}
