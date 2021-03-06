<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;

class EntityChoiceType extends AbstractType
{
    const NAME = 'oro_entity_choice';

    /** @var EntityProvider */
    protected $provider;

    /** @var array */
    protected $itemsCache;

    /**
     * @param EntityProvider $provider
     */
    public function __construct(EntityProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaultConfigs = [
            'placeholder'             => 'oro.entity.form.choose_entity',
            'result_template_twig'    => 'OroEntityBundle:Choice:entity/result.html.twig',
            'selection_template_twig' => 'OroEntityBundle:Choice:entity/selection.html.twig',
        ];

        $resolver->setDefaults(
            [
                'choices'              => function (Options $options) {
                    return $this->getChoices($options['show_plural']);
                },
                'choice_attr'          => function ($choice) {
                    return $this->getChoiceAttributes($choice);
                },
                'empty_value'          => '',
                'show_plural'          => false,
                'configs'              => $defaultConfigs,
                'translatable_options' => false
            ]
        );
        $resolver->setNormalizers(
            [
                // this normalizer allows to add/override config options outside
                'configs' => function (Options $options, $configs) use ($defaultConfigs) {
                    return array_merge($defaultConfigs, $configs);
                }
            ]
        );
    }

    /**
     * Returns a list of entities
     *
     * @param bool $showPlural If true a plural label will be used as a choice text; otherwise, a label will be used
     *
     * @return array [{full class name} => [{attr1} => {val1}, ...], ...]
     */
    protected function getEntities($showPlural)
    {
        if (null === $this->itemsCache) {
            $this->itemsCache = [];

            foreach ($this->provider->getEntities($showPlural) as $entity) {
                $entityClass = $entity['name'];
                unset($entity['name']);
                $this->itemsCache[$entityClass] = $entity;
            }
        }

        return $this->itemsCache;
    }

    /**
     * Returns a list of choices
     *
     * @param bool $showPlural If true a plural label will be used as a choice text; otherwise, a label will be used
     *
     * @return array
     */
    protected function getChoices($showPlural)
    {
        $choices = [];
        foreach ($this->getEntities($showPlural) as $entityClass => $entity) {
            $choices[$entityClass] = $showPlural ? $entity['plural_label'] : $entity['label'];
        }

        return $choices;
    }

    /**
     * Returns a list of choice attributes for the given entity
     *
     * @param string $entityClass
     *
     * @return array
     */
    protected function getChoiceAttributes($entityClass)
    {
        $attributes = [];
        foreach ($this->itemsCache[$entityClass] as $key => $val) {
            $attributes['data-' . $key] = $val;
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
