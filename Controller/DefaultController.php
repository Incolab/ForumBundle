<?php

namespace Incolab\ForumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {

    public function indexAction() {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {

            $readRoles[] = $this->get("db")->getRepository('IncolabForumBundle:ForumRole')->findByName('ROLE_PUBLIC');

            $categories = $this->get("db")->getRepository('IncolabForumBundle:Category')->getIndex($readRoles);

            return $this->render('IncolabForumBundle:Default:index.html.twig', array('categories' => $categories));
        }

        $user = $this->getUser();
        $userRoles = $this->get("db")->getRepository('IncolabForumBundle:ForumRole')->findByUser($user);

        if (empty($userRoles)) {
            $userRoles[] = $this->get("db")->getRepository('IncolabForumBundle:ForumRole')
                    ->findByName('ROLE_PUBLIC');
        }
        $categories = $this->get("db")->getRepository('IncolabForumBundle:Category')
                ->getIndex($userRoles);

        return $this->render('IncolabForumBundle:Default:index.html.twig', array('categories' => $categories));
    }

}
