IncolabForumBundle | Documentation | Index
==========================================

Install
--------------------

 Install the bundle and dependencies with composer  
`composer require incolab/forum-bundle`

Register the bundles
--------------------

Register both `IncolabCoreBundle` (if not already registered) and `IncolabForumBundle` in your
application kernel:

    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new Incolab\CoreBundle\IncolabCoreBundle(),
            new Incolab\ForumBundle\IncolabForumBundle(),
        );
    }
