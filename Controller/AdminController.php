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

        $categories = $this->getDoctrine()->getRepository('IncolabForumBundle:Category')->getParentsWithChilds();

        $form = $this->createForm(CategoryType::class, new Category());

        return $this->render(
                        'IncolabForumBundle:Admin:index.html.twig', array('categories' => $categories, 'parentCatForm' => $form->createView())
        );
    }

    public function parentCategoryAddAction(Request $request) {
        $categories = $this->getDoctrine()->getRepository('IncolabForumBundle:Category')->getParents();

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
        $category = $this->getDoctrine()->getRepository('IncolabForumBundle:Category')->findOneBySlug($slug);
        $form = $this->createForm(CategoryType::class, $category);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($category);
                $em->flush();
                $this->addFlash('notice', 'Category "' . $category->getName() . '" was modified');

                return $this->redirectToRoute('incolab_forum_admin_homepage');
            }
        }

        return $this->render(
                        'IncolabForumBundle:Admin:categoryModify.html.twig', array('catForm' => $form->createView())
        );
    }

    public function childCategoryAddAction($slugParent, Request $request) {
        $parentCat = $this->getDoctrine()->getRepository('IncolabForumBundle:Category')->getParentBySlug($slugParent);

        if ($parentCat === null) {
            throw $this->createNotFoundException('Aucune catégorie à cette adresse');
        }
        if ($request->isMethod('POST')) {
            $category = new Category();
            $form = $this->createForm(CategoryType::class, $category);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $category->setParent($parentCat);
                $this->container->get('incolab_forum.category_manager')->addChild($category);

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
        $category = $this->getDoctrine()->getRepository('IncolabForumBundle:Category')
                ->getCategoryBySlugAndParentSlug($slug, $parentSlug);

        if ($category === null) {
            throw $this->createNotFoundException('Aucune Catégorie pour cette adresse.');
        }

        $form = $this->createForm(CategoryType::class, $category);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($category);
                $em->flush();
                $this->addFlash('notice', 'Category "' . $category->getName() . '" was modified');

                return $this->redirectToRoute('incolab_forum_admin_homepage');
            }
        }

        return $this->render(
                        'IncolabForumBundle:Admin:categoryModify.html.twig', array('catForm' => $form->createView())
        );
    }

    public function topicDeleteAction($slugParentCat, $slugCat, $slugTopic) {
        $topic = $this->getDoctrine()->getRepository("IncolabForumBundle:Topic")
                ->getTopicBySlugTopicCatParentCat($slugTopic, $slugCat, $slugParentCat);

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

        $em = $this->getDoctrine()->getManager();
        $em->remove($topic);
        $em->persist($category);
        $em->persist($parentCat);
        $em->flush();

        $this->addFlash("success", "The topic has been deleted");

        return $this->redirectToRoute('incolab_forum_admin_homepage');
    }

    public function postDeleteAction($slugParentCat, $slugCat, $slugTopic, $postId) {
        $post = $this->getDoctrine()->getRepository('IncolabForumBundle:Post')
                ->getPost($slugParentCat, $slugCat, $slugTopic, $postId);

        if ($post === null) {
            throw $this->createNotFoundException('This post don\'t exists.');
        }
        $topic = $post->getTopic();
        $category = $topic->getCategory();
        $parentCat = $category->getParent();
        
        $topic->decrementNumPosts();
        $category->decrementNumPosts();
        $parentCat->decrementNumPosts();

        $em = $this->getDoctrine()->getManager();
        
        $em->persist($topic);
        $em->persist($category);
        $em->persist($parentCat);
        $em->remove($post);
        $em->flush();
        
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
                $em = $this->getDoctrine()->getManager();
                $em->persist($role);
                $em->flush();

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
