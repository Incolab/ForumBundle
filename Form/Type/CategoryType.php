<?php

namespace Incolab\ForumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CategoryType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class)
            //->add('slug')
            ->add(
                'readRoles',
                CollectionType::class,
                array(
                    'entry_type'   => EntityType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_options'  => array(
                        'class' => 'IncolabForumBundle:ForumRole',
                        'choice_label' => 'name'
                    )
                )
            )
            ->add(
                'writeRoles',
                CollectionType::class,
                array(
                    'entry_type'   => EntityType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_options'  => array(
                        'class' => 'IncolabForumBundle:ForumRole',
                        'choice_label' => 'name'
                    )
                )
            )
            ->add('position', IntegerType::class)
            /*
            ->add('numTopics')
            ->add('numPosts')
            ->add('lastPost')
            ->add('lastTopic')
            */
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Incolab\ForumBundle\Entity\Category'
        ));
    }
}
