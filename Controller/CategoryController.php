<?php

namespace Incolab\ForumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Incolab\ForumBundle\Entity\Topic;
use Incolab\ForumBundle\Entity\Post;
use Incolab\ForumBundle\Form\Type\TopicType;
use Incolab\ForumBundle\Form\Type\PostType;
use Incolab\ForumBundle\IncolabForumEvents;
use Incolab\ForumBundle\Event\TopicEvent;
use Incolab\ForumBundle\Event\PostEvent;

class CategoryController extends Controller {

    public function parentCategoryAction($slugParentCat) {
        $readRoles = [];
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $readRoles = $this->get("db")->getRepository('IncolabForumBundle:ForumRole')->findByUser($user);
        }

        if (empty($readRoles)) {
            $readRoles[] = $this->get("db")
                            ->getRepository('IncolabForumBundle:ForumRole')->findByName('ROLE_PUBLIC');
        }

        $parentCategory = $this->get("db")->getrepository('IncolabForumBundle:Category')
                ->getParentCategoryBySlug($slugParentCat, $readRoles);

        if ($parentCategory === null) {
            throw $this->createNotFoundException('Aucune Catégorie pour cette adresse.');
        }

        return $this->render(
                        'IncolabForumBundle:Category:parentCategory.html.twig', array('parentCategory' => $parentCategory)
        );
    }

    public function categoryAction(Request $request, $slugParentCat, $slugCat) {
        $page = 1;
        $elmtsByPage = 10;

        if ($request->query->has('page')) {
            $page = $request->query->get('page');
            if ($page < 1) {
                throw $this->createAccessDeniedException("Invalid Page");
            }
        }

        $category = $this->get("db")->getrepository('IncolabForumBundle:Category')
                ->getCategory($slugCat, $slugParentCat, true, $page, $elmtsByPage);

        if ($category === null) {
            throw $this->createNotFoundException('Aucune Catégorie pour cette adresse.');
        }

        $paramsRender = [
            'category' => $category,
            'topics' => $category->getTopics()
        ];

        $pagination = [
            "nbPages" => ceil($this->get("db")->getRepository("IncolabForumBundle:Topic")
                            ->getNbTopicByCat($category) / $elmtsByPage),
            "current" => $page
        ];
        if ($pagination["nbPages"] > 1) {
            $paramsRender["pagination"] = $pagination;
        }

        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $role = $this->get("db")
                            ->getRepository('IncolabForumBundle:ForumRole')->findByName('ROLE_PUBLIC');

            if (!$category->hasReadRole($role)) {
                throw $this->createAccessDeniedException("Permission denied");
            }

            return $this->render('IncolabForumBundle:Category:category.html.twig', $paramsRender);
        }

        $user = $this->getUser();
        $userForumRoles = $this->get("db")->getRepository('IncolabForumBundle:ForumRole')->findByUser($user);

        if (!$this->userCanRead($userForumRoles, $category)) {
            throw $this->createAccessDeniedException("Permission denied");
        }

        if (!$this->userCanPost($userForumRoles, $category)) {
            return $this->render('IncolabForumBundle:Category:category.html.twig', $paramsRender);
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

        $paramsRender["topicForm"] = $form->createView();

        return $this->render('IncolabForumBundle:Category:category.html.twig', $paramsRender);
    }

    public function topicShowAction(Request $request, $slugParentCat, $slugCat, $slugTopic) {
        $page = 1;
        $elmtsByPage = 10;

        if ($request->query->has('page')) {
            $page = $request->query->get('page');
            if ($page < 1) {
                throw $this->createAccessDeniedException("Invalid Page");
            }
        }

        $topic = $this->get("db")->getRepository('IncolabForumBundle:Topic')
                ->getTopic($slugTopic, $slugCat, $slugParentCat, $page, $elmtsByPage);

        if ($topic === null) {
            throw $this->createNotFoundException('This topic don\'t exists');
        }

        if ($topic->isBuried()) {
            throw $this->createAccessDeniedException('This topic is buried');
        }

        $paramsRender['topic'] = $topic;

        $pagination = [
            // compute post's numbers of topic - 1 (the firstpost)
            "nbPages" => ceil(($this->get("db")->getRepository("IncolabForumBundle:Post")
                            ->getNbPostsByTopic($topic) - 1) / $elmtsByPage),
            "current" => $page
        ];

        if ($pagination["nbPages"] > 1) {
            $paramsRender["pagination"] = $pagination;
        }


        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $role = $this->get("db")
                            ->getRepository('IncolabForumBundle:ForumRole')->findByName('ROLE_PUBLIC');

            if (!$topic->getCategory()->hasReadRole($role)) {
                throw $this->createAccessDeniedException("Permission denied");
            }

            return $this->render('IncolabForumBundle:Topic:show.html.twig', $paramsRender);
        }

        $user = $this->getUser();
        $userForumRoles = $this->get("db")->getRepository('IncolabForumBundle:ForumRole')->findByUser($user);
        if (!$this->userCanRead($userForumRoles, $topic->getCategory())) {
            throw $this->createAccessDeniedException("Permission denied");
        }

        if (!$this->userCanPost($userForumRoles, $topic->getCategory()) || $topic->isClosed()) {
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

    private function userCanRead($userRoles, $category) {
        if ($category === null) {
            return false;
        }

        foreach ($userRoles as $role) {
            if ($category->hasReadRole($role)) {
                return true;
            }
        }
        return false;
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
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        $parentCat = $this->get("db")->getRepository('IncolabForumBundle:Category')
                ->getCategoryForInsertTopic($slugCat, $slugParentCat);

        // le form sera la [GET]
        if ($parentCat === null) {
            throw $this->createNotFoundException('La catégorie n\'existe pas');
        }

        $userForumRoles = $this->get("db")->getRepository('IncolabForumBundle:ForumRole')->findByUser($this->getUser());
        if (!$this->userCanPost($userForumRoles, $parentCat->getChildBySlug($slugCat))) {
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
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        $parentCat = $this->get("db")->getRepository('IncolabForumBundle:Category')
                ->getCategoryForInsertTopic($slugCat, $slugParentCat);

        // traitement du [POST]
        if ($parentCat === null) {
            throw $this->createNotFoundException('La catégorie n\'existe pas');
        }

        $userForumRoles = $this->get("db")->getRepository('IncolabForumBundle:ForumRole')->findByUser($this->getUser());
        if (!$this->userCanPost($userForumRoles, $parentCat->getChildBySlug($slugCat))) {
            throw $this->createAccessDeniedException("You are not authorized to create a topic here.");
        }

        $topic = new Topic();
        $form = $this->createForm(TopicType::class, $topic);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $topic->setAuthor($this->get('security.token_storage')->getToken()->getUser());
            $category = $parentCat->getChildBySlug($slugCat);
            $category->setParent($parentCat);
            $topic->setCategory($category);

            $event = new TopicEvent($topic);
            $this->get("event_dispatcher")->dispatch(IncolabForumEvents::ON_TOPIC_POST, $event);
            if (!$event->isValid()) {
                $this->addFlash('error', $event->getMessageStatus());
                return $this->redirectToCategory($topic);
            }

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

    private function redirectToTopic(Topic $topic) {
        return $this->redirectToRoute(
                        'incolab_forum_topic_show', [
                    'slugParentCat' => $topic->getCategory()->getParent()->getSlug(),
                    'slugCat' => $topic->getCategory()->getSlug(),
                    'slugTopic' => $topic->getSlug()
                        ]
        );
    }

    private function redirectToCategory(Topic $topic) {
        return $this->redirectToRoute(
                        'incolab_forum_category_show', [
                    'slugParentCat' => $topic->getCategory()->getParent()->getSlug(),
                    'slugCat' => $topic->getCategory()->getSlug()
                        ]
        );
    }

    public function postAddAction($slugParentCat, $slugCat, $slugTopic, Request $request) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        $topic = $this->get("db")->getRepository('IncolabForumBundle:Topic')
                ->getTopic($slugTopic, $slugCat, $slugParentCat);

        if ($topic === null) {
            throw $this->createNotFoundException('This topic don\'t exists');
        }

        if ($topic->isBuried() || $topic->isClosed()) {
            throw $this->createAccessDeniedException('The topic is buried or closed');
        }

        $userForumRoles = $this->get("db")->getRepository('IncolabForumBundle:ForumRole')->findByUser($user);
        if (!$this->userCanPost($userForumRoles, $topic->getCategory())) {
            throw $this->createAccessDeniedException("You are not authorized to add a post here.");
        }

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $post->setTopic($topic);
            $post->setAuthor($user);

            $event = new PostEvent($post);
            $this->get("event_dispatcher")->dispatch(IncolabForumEvents::ON_COMMENT_POST, $event);
            if (!$event->isValid()) {
                $this->addFlash('error', $event->getMessageStatus());
                return $this->redirectToTopic($topic);
            }


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
