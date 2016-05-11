<?php

namespace Incolab\ForumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Incolab\ForumBundle\Form\Type\PostType;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class TopicType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', TextType::class)
            //->add('author')
            //->add('slug')
            //->add('numViews')
            //->add('numPosts')
            //->add('isClosed')
            //->add('isPinned')
            //->add('isBuried')
            //->add('createdAt', 'datetime')
            //->add('pulledAt', 'datetime')
            ->add('firstPost', PostType::class)
            //->add('lastPost')
            //->add('category')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Incolab\ForumBundle\Entity\Topic'
        ));
    }
}
