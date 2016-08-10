<?php

namespace Incolab\ForumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\CallbackTransformer;

class PostType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('topic')
            ->add('message', TextareaType::class)
            //->add('createdAt', 'datetime')
            //->add('updatedAt', 'datetime')
            //->add('author')
        ;
        
        $builder->get('message')
            ->addModelTransformer(
                new CallbackTransformer(
                    // Before send form
                    function ($originalContent) {
                        return $originalContent;
                    },
                    // Before persist
                    function ($submittedContent) {
                        // delete all html tags autoclose tags and strip_tags argument
                        dump($submittedContent);
                        $dom = new \DOMDocument();
                        $dom->loadHTML($submittedContent);
                        if (!$dom) {
                            return "<p>This post is not valid.</p>";
                        }
                        // on retaille youtube
                        $iframes = $dom->getElementsByTagName("iframe");
                        foreach ($iframes as $iframe) {
                            $iframe->setAttribute("height", "252");
                            $iframe->setAttribute("width", "448");
                        }
                        
                        //on retaille les images
                        $imgs = $dom->getElementsByTagName("img");
                        foreach ($imgs as $element) {
                            $element->setAttribute("width", "448");
                        }
                        
                        $cleaned = strip_tags($dom->saveHTML(), '<br><p><a><ul><ol><li><strong><em><span><iframe><img>');
                        return $cleaned;
                    }
                )
            );
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Incolab\ForumBundle\Entity\Post'
        ));
    }
}
