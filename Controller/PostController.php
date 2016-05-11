<?php

namespace Incolab\ForumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Incolab\ForumBundle\Form\Type\PostType;

class PostController extends Controller
{
    public function postEditAction($slugParentCat, $slugCat, $slugTopic, $postId, Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }
        
        $post = $this->getDoctrine()->getRepository('IncolabForumBundle:Post')
            ->getPostForEdit($slugParentCat, $slugCat, $slugTopic, $postId, $this->getUser());
        
        if ($post === null) {
            throw $this->createNotFoundException('This post don\'t exists or you aren\'t allowed to edit.');
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
                'incolab_forum_topic_show',
                array(
                    'slugParentCat' => $post->getTopic()->getCategory()->getParent()->getSlug(),
                    'slugCat' => $post->getTopic()->getCategory()->getSlug(),
                    'slugTopic' => $post->getTopic()->getSlug()
                    )
            );
            
        } else {
            return $this->render(
                'IncolabForumBundle:Post:edit.html.twig',
                array('postForm' => $postForm->createView())
            );
        }

        
    }
}
