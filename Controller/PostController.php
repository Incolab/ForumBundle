<?php

namespace Incolab\ForumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Incolab\ForumBundle\Entity\Post;
use Incolab\ForumBundle\Form\Type\PostType;
use Incolab\ForumBundle\IncolabForumEvents;
use Incolab\ForumBundle\Event\PostEvent;

class PostController extends Controller {

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

    public function postEditAction($slugParentCat, $slugCat, $slugTopic, $postId, Request $request) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $postRepository = $this->get("db")->getRepository('IncolabForumBundle:Post');
        $post = $postRepository->getPost($slugParentCat, $slugCat, $slugTopic, $postId);

        if ($post === null) {
            throw $this->createNotFoundException('This post don\'t exists.');
        }

        $user = $this->getUser();
        if ($post->getAuthor()->getId() != $user->getId() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException("You aren't allowed to edit this post");
        }

        if ($post->getTopic()->isBuried() || $post->getTopic()->isClosed()) {
            throw $this->createAccessDeniedException('This topic is buried or closed');
        }

        $postForm = $this->createForm(PostType::class, $post);

        $postForm->handleRequest($request);

        if ($postForm->isSubmitted() && $postForm->isValid()) {
            $post->setUpdatedAt(new \DateTime());
            $event = new PostEvent($post);
            $this->get("event_dispatcher")->dispatch(IncolabForumEvents::ON_COMMENT_POST, $event);
            if (!$event->isValid()) {
                $this->addFlash('error', $event->getMessageStatus());
                return $this->redirectToTopicByPost($post);
            }

            $postRepository->persist($post);
            $this->addFlash('success', 'Your post has been edited.');
            return $this->redirectToTopicByPost($post);
        }
        return $this->render(
                        'IncolabForumBundle:Post:edit.html.twig', array('postForm' => $postForm->createView())
        );
    }

    private function redirectToTopicByPost(Post $post) {
        return $this->redirectToRoute(
                        'incolab_forum_topic_show', array(
                    'slugParentCat' => $post->getTopic()->getCategory()->getParent()->getSlug(),
                    'slugCat' => $post->getTopic()->getCategory()->getSlug(),
                    'slugTopic' => $post->getTopic()->getSlug()
                        )
        );
    }

    public function permalinkAction($slugParentCat, $slugCat, $slugTopic, $postId) {
        $elmtsByPage = 10;

        $pagination["current"] = ceil($this->get("db")->getRepository("IncolabForumBundle:Post")
                        ->getNbPostsUntilIdByTopicSlug($slugTopic, $postId) / $elmtsByPage);
        if ($pagination["current"] < 1) {
            $pagination["current"] = 1;
        }

        $topic = $this->get("db")->getRepository('IncolabForumBundle:Topic')
                ->getTopic($slugTopic, $slugCat, $slugParentCat, $pagination["current"], $elmtsByPage);

        if ($topic === null) {
            throw $this->createNotFoundException('This topic don\'t exists');
        }
        
        // compute post's numbers of topic - 1 (the firstpost)
        $pagination["nbPages"] = ceil(($this->get("db")->getRepository("IncolabForumBundle:Post")
                        ->getNbPostsByTopic($topic) - 1) / $elmtsByPage);
        $paramsRender["topic"] = $topic;

        if ($pagination["nbPages"] > 1) {
            $paramsRender["pagination"] = $pagination;
        }

        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
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

        if (!$this->userCanPost($userForumRoles, $topic->getCategory())) {
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

}
