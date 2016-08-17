<?php

namespace Incolab\ForumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Incolab\ForumBundle\Entity\Topic;
use Incolab\ForumBundle\Entity\Post;
use Incolab\ForumBundle\Form\Type\TopicType;
use Incolab\ForumBundle\Form\Type\PostType;

class CategoryController extends Controller {

    public function parentCategoryAction($slugParentCat) {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->get('security.token_storage')->getToken()->getUser();
            if (!$user->getForumRoles()->isEmpty()) {
                $readRoles = $user->getForumRoles();
            }
        }
        if (!isset($readRoles)) {
            $readRoles = $this->getDoctrine()
                            ->getRepository('IncolabForumBundle:ForumRole')->findByName('ROLE_PUBLIC');
        }

        $parentCategory = $this->getDoctrine()->getrepository('IncolabForumBundle:Category')
                ->getParentCategoryBySlug($readRoles, $slugParentCat);

        if ($parentCategory === null) {
            throw $this->createNotFoundException('Aucune Catégorie pour cette adresse.');
        }

        return $this->render(
                        'IncolabForumBundle:Category:parentCategory.html.twig', array('parentCategory' => $parentCategory)
        );
    }

    public function categoryAction($slugParentCat, $slugCat) {
        $category = $this->getDoctrine()->getrepository('IncolabForumBundle:Category')
                ->getCategoryBySlugAndParentSlug($slugCat, $slugParentCat);

        if ($category === null) {
            throw $this->createNotFoundException('Aucune Catégorie pour cette adresse.');
        }

        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $role = $this->getDoctrine()
                            ->getRepository('IncolabForumBundle:ForumRole')->findOneByName('ROLE_PUBLIC');

            if (!$category->hasReadRole($role)) {
                throw $this->createAccessDeniedException("Permission denied");
            }

            return $this->render(
                            'IncolabForumBundle:Category:category.html.twig', array(
                        'category' => $category,
                        'topics' => $category->getTopics(),
                            )
            );
        }

        if (!$this->userCanPost($this->getUser()->getForumRoles(), $category)) {
            return $this->render(
                            'IncolabForumBundle:Category:category.html.twig', array(
                        'category' => $category,
                        'topics' => $category->getTopics(),
                            )
            );
        }

        $form = $this->createForm(
                TopicType::class, new Topic(), array(
            'action' => $this->generateUrl(
                    'incolab_forum_category_topic_create', array('slugParentCat' => $category->getParent()->getSlug(),
                'slugCat' => $category->getSlug())
            ),
            'method' => 'POST'
                )
        );

        return $this->render(
                        'IncolabForumBundle:Category:category.html.twig', array(
                    'category' => $category,
                    'topics' => $category->getTopics(),
                    'topicForm' => $form->createView()
                        )
        );
    }

    public function topicShowAction(Request $request, $slugParentCat, $slugCat, $slugTopic) {
        
        $page = 1;
        if ($request->query->has('page')) {
            $page = $request->query->get('page');
            if ($page < 1) {
                throw $this->createAccessDeniedException("Invalid Page");
            }
        }

        $topic = $this->getDoctrine()->getRepository('IncolabForumBundle:Topic')
                ->getTopic($slugTopic, $slugCat, $slugParentCat, $page, 10);
        
        if ($topic === null) {
            throw $this->createNotFoundException('This topic don\'t exists');
        }

        $pagination = [
            "nbPages" => round($this->getDoctrine()->getRepository("IncolabForumBundle:Post")
                    ->getNbPostsByTopic($topic) / 10),
            "current" => $page
        ];

        $paramsRender['topic'] = $topic;
        
        if ($pagination["nbPages"] > 1) {
            $paramsRender["pagination"] = $pagination;
        }
        

        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $role = $this->getDoctrine()
                            ->getRepository('IncolabForumBundle:ForumRole')->findOneByName('ROLE_PUBLIC');

            if (!$topic->getCategory()->hasReadRole($role)) {
                throw $this->createAccessDeniedException("Permission denied");
            }

            return $this->render('IncolabForumBundle:Topic:show.html.twig', $paramsRender);
        }

        if (!$this->userCanPost($this->getUser()->getForumRoles(), $topic->getCategory())) {
            return $this->render('IncolabForumBundle:Topic:show.html.twig', $paramsRender);
        }


        $form = $this->createForm(
                PostType::class, new Post(), array(
            'action' => $this->generateUrl(
                    'incolab_forum_post_create', array(
                'slugParentCat' => $topic->getCategory()->getParent()->getSlug(),
                'slugCat' => $topic->getCategory()->getSlug(),
                'slugTopic' => $topic->getSlug()
                    )
            ),
            'method' => 'POST'
                )
        );
        
        $paramsRender["postForm"] = $form->createView();
        
        return $this->render('IncolabForumBundle:Topic:show.html.twig', $paramsRender);
    }

    private function userCanPost($userRoles, $category) {
        if ($category === null) {
            return false;
        }

        foreach ($userRoles as $role) {
            if ($category->hasWriteRole($role)) {
                return true;
            }
        }
        return false;
    }

    public function topicNewAction($slugParentCat, $slugCat) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $parentCat = $this->getDoctrine()->getRepository('IncolabForumBundle:Category')
                ->getCategoryForInsertTopic($slugCat, $slugParentCat);

        // le form sera la [GET]
        if ($parentCat === null) {
            throw $this->createNotFoundException('La catégorie n\'existe pas');
        }

        if (!$this->userCanPost($this->getUser()->getForumRoles(), $parentCat->getChildBySlug($slugCat))) {
            throw $this->createAccessDeniedException("You are not authorized to  create a topic here.");
        }

        $form = $this->createForm(TopicType::class, new Topic());

        return $this->render(
                        'IncolabForumBundle:Topic:add.html.twig', array(
                    'childCatForm' => $form->createView(),
                    'parentCat' => $parentCat
                        )
        );
    }

    public function topicCreateAction($slugParentCat, $slugCat, Request $request) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $parentCat = $this->getDoctrine()->getRepository('IncolabForumBundle:Category')
                ->getCategoryForInsertTopic($slugCat, $slugParentCat);

        // traitement du [POST]
        if ($parentCat === null) {
            throw $this->createNotFoundException('La catégorie n\'existe pas');
        }

        if (!$this->userCanPost($this->getUser()->getForumRoles(), $parentCat->getChildBySlug($slugCat))) {
            throw $this->createAccessDeniedException("You are not authorized to create a topic here.");
        }

        $topic = new Topic();
        $form = $this->createForm(TopicType::class, $topic);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $topic->setAuthor($this->get('security.token_storage')->getToken()->getUser());
            $category = $parentCat->getChildBySlug($slugCat);
            $topic->setCategory($category);
            $this->container->get('incolab_forum.topic_manager')->add($topic);

            return $this->redirectToRoute(
                            'incolab_forum_topic_show', array(
                        'slugParentCat' => $topic->getCategory()->getParent()->getSlug(),
                        'slugCat' => $topic->getCategory()->getSlug(),
                        'slugTopic' => $topic->getSlug()
                            )
            );
        }
        return $this->render(
                        'IncolabForumBundle:Topic:add.html.twig', array('childCatForm' => $form->createView(), 'parentCat' => $parentCat)
        );
    }

    public function postAddAction($slugParentCat, $slugCat, $slugTopic, Request $request) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $topic = $this->getDoctrine()->getRepository('IncolabForumBundle:Topic')
                ->getTopicForInsertPost($slugTopic, $slugCat, $slugParentCat);

        if ($topic === null) {
            throw $this->createNotFoundException('This topic don\'t exists');
        }

        if (!$this->userCanPost($this->getUser()->getForumRoles(), $topic->getCategory())) {
            throw $this->createAccessDeniedException("You are not authorized to add a post here.");
        }

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $post->setTopic($topic);
            $post->setAuthor($this->get('security.token_storage')->getToken()->getUser());
            $this->container->get('incolab_forum.post_manager')->add($post);

            $this->addFlash('success', 'Post added with success');

            return $this->redirectToRoute(
                            'incolab_forum_topic_show', array(
                        'slugParentCat' => $topic->getCategory()->getParent()->getSlug(),
                        'slugCat' => $topic->getCategory()->getSlug(),
                        'slugTopic' => $topic->getSlug()
                            )
            );
        }

        return $this->render(
                        'IncolabForumBundle:Topic:show.html.twig', array('topic' => $topic, 'postForm' => $form->createView())
        );
    }

}
