<?php

namespace Incolab\ForumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $readRoles = $this->getDoctrine()->getRepository('IncolabForumBundle:ForumRole')->findByName('ROLE_PUBLIC');
            $categories = $this->getDoctrine()->getRepository('IncolabForumBundle:Category')->getIndex($readRoles);
            
            return $this->render('IncolabForumBundle:Default:index.html.twig', array('categories' => $categories));
        }
        
        $user = $this->getUser();
        if ($user->getForumRoles()->isEmpty()) {
            $user->addForumRole(
                $this->getDoctrine()->getRepository('IncolabForumBundle:ForumRole')
                    ->findOneByName('ROLE_PUBLIC')
            );
        }
        $categories = $this->getDoctrine()->getRepository('IncolabForumBundle:Category')
            ->getIndex($user->getForumRoles());
        
        return $this->render('IncolabForumBundle:Default:index.html.twig', array('categories' => $categories));
    }
}
