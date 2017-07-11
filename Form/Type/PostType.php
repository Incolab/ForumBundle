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
                        $parser = new \Incolab\CoreBundle\BBCodeParser\BBCodeParser();
                        return $parser->parse($submittedContent);
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
