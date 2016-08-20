<?php

namespace Incolab\ForumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Incolab\ForumBundle\Entity\Post;
use Incolab\ForumBundle\Form\Type\PostType;

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

        $post = $this->getDoctrine()->getRepository('IncolabForumBundle:Post')
                ->getPost($slugParentCat, $slugCat, $slugTopic, $postId);

        if ($post === null) {
            throw $this->createNotFoundException('This post don\'t exists.');
        }
        
        if ($post->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException("You aren't allowed to edit this post");
        }
        
        if ($post->getTopic()->isBuried() || $post->getTopic()->isClosed()) {
            throw $this->createAccessDeniedException('The topic\'s post is buried or closed');
        }

        $postForm = $this->createForm(PostType::class, $post);

        $postForm->handleRequest($request);

        if ($postForm->isSubmitted() && $postForm->isValid()) {
            $post->setUpdatedAt(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Your post has been edited.');

            return $this->redirectToRoute(
                            'incolab_forum_topic_show', array(
                        'slugParentCat' => $post->getTopic()->getCategory()->getParent()->getSlug(),
                        'slugCat' => $post->getTopic()->getCategory()->getSlug(),
                        'slugTopic' => $post->getTopic()->getSlug()
                            )
            );
        } else {
            return $this->render(
                            'IncolabForumBundle:Post:edit.html.twig', array('postForm' => $postForm->createView())
            );
        }
    }

    public function permalinkAction($slugParentCat, $slugCat, $slugTopic, $postId) {
        $elmtsByPage = 10;

        $pagination["current"] = ceil($this->getDoctrine()->getRepository("IncolabForumBundle:Post")
                            ->getNbPostsUntilIdByTopicSlug($slugTopic, $postId) / $elmtsByPage);
        
        $topic = $this->getDoctrine()->getRepository('IncolabForumBundle:Topic')
                ->getTopic($slugTopic, $slugCat, $slugParentCat, $pagination["current"], $elmtsByPage);
        
        if ($topic === null) {
            throw $this->createNotFoundException('This topic don\'t exists');
        }
        
        $pagination["nbPages"] = ceil($this->getDoctrine()->getRepository("IncolabForumBundle:Post")
                            ->getNbPostsByTopic($topic) / $elmtsByPage);
        $paramsRender["topic"] = $topic;
        
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

        $user = $this->getUser();

        if (!$this->userCanRead($user->getForumRoles(), $topic->getCategory())) {
            throw $this->createAccessDeniedException("Permission denied");
        }

        if (!$this->userCanPost($user->getForumRoles(), $topic->getCategory())) {
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
